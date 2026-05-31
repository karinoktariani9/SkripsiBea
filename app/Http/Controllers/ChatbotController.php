<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    /**
     * Endpoint utama untuk chatbot (Rule-Based + AI Fallback)
     */
    public function clearSession()
    {
        session()->forget([
            'last_search_all_results',
            'last_search_criteria',
            'last_search_page',
            'last_search_results',
            'selected_scholarship',
        ]);
        return response()->json(['success' => true, 'message' => 'Session cleared']);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'rag_enabled' => 'nullable|boolean',
        ]);

        $ragEnabled = $request->input('rag_enabled', true);
	$this->startTime = microtime(true);
        $rawMessage = $request->input('message');
	$startTime = microtime(true);

        // JIKA RAG DIMATIKAN, LANGSUNG KE AI TANPA CEK DATABASE
        if (!$ragEnabled) {
            return $this->handlePureAI($rawMessage);
        }
        $normalizedData = $this->normalizeText($rawMessage);
        $message = $normalizedData['text'];

        try {
            // TYPO SEKARANG LANGSUNG DIPROSES (TIDAK BERTANYA LAGI)
            // Sistem akan menggunakan pesan yang sudah dikoreksi di $message
            

            // 1. GREETING DETECTION
            if ($this->isGreeting($message)) {
                return $this->finalizeResponse($this->getGreetingResponse($message), $normalizedData);
            }

            // 1.2 OUT OF TOPIC DETECTION (Sangat Prioritas - Agar tidak menjawab materi kuliah)
            if ($ragEnabled && $this->isOutOfTopic($message)) {
                return $this->finalizeResponse($this->getOutOfTopicResponse(), $normalizedData);
            }

            // 1.3 DEFINISI BEASISWA (Di RAG mode, pertanyaan definisi harus ditolak)
            if ($ragEnabled && preg_match('/\b(apa itu|pengertian|definisi|jelaskan apa|maksud dari|apakah itu)\b/i', $message)) {
                return $this->finalizeResponse($this->getOutOfTopicResponse(), $normalizedData);
            }
            if ($ragEnabled && preg_match('/\bbeasiswa\s+(itu|adalah|merupakan|ialah)\b/i', $message)) {
                return $this->finalizeResponse($this->getOutOfTopicResponse(), $normalizedData);
            }

            // 1.5 THANK YOU DETECTION
            if ($this->isThankYou($message) && !$this->isSearchQuery($message)) {
                return $this->finalizeResponse($this->getThankYouResponse(), $normalizedData);
            }

            // 1.7 INTENT DETECTION (Deteksi niat user)
            $detailIntent = $this->getDetailIntent($message);

            // 1.7.5 VALIDASI JURUSAN (Konteks Follow-up: Apakah beasiswa ini ada jurusan X?)
            if ($ragEnabled && (session()->has('selected_scholarship') || session()->has('last_search_results'))) {
                // Pola: "beasiswa ini ada jurusan X?", "ada jurusan Y?", "no 1 ada jurusan Z?"
                if (preg_match('/\b(beasiswa\s+)?(ini|itu|tersebut|no\s+\d+|nomor\s+\d+|ke\s+\d+)?\s*(ada|tersedia|punya|bisa)\s+(jurusan|prodi|bidang|fakultas|studi)\s+([a-z\s]+)/i', $message, $matches)) {
                    $ref = trim($matches[2] ?? '');
                    $majorFound = trim($matches[5]);
                    return $this->handleMajorValidation($majorFound, $normalizedData, $ref);
                }
            }

            // 1.7.6 VALIDASI PENDANAAN (Konteks Follow-up: Apakah ini fully funded?)
            // Skip jika pesan mengandung "apa itu" (definisi) atau konteks pencarian baru
            $isNewSearch = preg_match('/\b(beasiswa|cari|tampilkan|kasih|ada|mau|coba|list|daftar)\b/i', $message) ||
                preg_match('/\b(s1|s2|s3|d3|d4|luar negeri|dalam negeri|fully funded|partially funded)\b/i', $message) &&
                !preg_match('/\b(ini|itu|tersebut|apakah|tipe)\b/i', $message);
            $isDefinisi = preg_match('/\b(apa itu|pengertian|definisi|maksud)\b/i', $message);
            if ($ragEnabled && !$isNewSearch && !$isDefinisi && (session()->has('selected_scholarship') || session()->has('last_search_results'))) {
                $fundingRegex = '/\b(beasiswa\s+)?(ini|itu|tersebut|no\s+\d+|nomor\s+\d+|ke\s+\d+)?\s*(apakah|tipe)?\s*(fully\s+funded|full\s+funded|partially\s+funded|partial\s+funded|dana\s+penuh|dana\s+sebagian|biaya\s+penuh|biaya\s+sebagian)\b/i';
                if (preg_match($fundingRegex, $message, $matches)) {
                    $ref = trim($matches[2] ?? '');
                    $fundingFound = trim($matches[4]);
                    return $this->handleFundingValidation($fundingFound, $normalizedData, $ref);
                }
            }

            // 2. DETAIL & SELECTION LOGIC (Prioritas Tinggi)
            
            // 4.4.5 LINK TANPA NOMOR ("berikan linknya", "link beasiswanya") - pakai selected_scholarship
            if (preg_match('/\b(link|url|tautan|linknya|websitenya|situsnya)\b/i', $message) &&
                !preg_match('/\b(nomor|no|nmr|#)\s*\d+/i', $message) &&
                session()->has('selected_scholarship')) {
                $s = session()->get('selected_scholarship');
                if (is_array($s)) {
                    $name = $s['nama_beasiswa'] ?? 'Beasiswa ini';
                    $urlAsli = $s['url_asli'] ?? null;
                    $urlSchoters = $s['url'] ?? null;
                    $urlInternal = route('scholarship.detail', ['id' => $s['id']]);
                    $ans = "Berikut link untuk **$name**:\n\n";
                    if ($urlAsli) $ans .= "🌐 [**Website Resmi**]($urlAsli)\n\n";
                    if ($urlSchoters) $ans .= "📋 [**Info di Schoters**]($urlSchoters)\n\n";
                    $ans .= "👉 [**Halaman Detail**]($urlInternal)";
                    return $this->finalizeResponse($ans, $normalizedData);
                }
            }
            // Juga handle: "berikan linknya" saat ada last_search tapi belum pilih
            if (preg_match('/\b(link|url|tautan|linknya)\b/i', $message) &&
                !preg_match('/\b(nomor|no|nmr|#)\s*\d+/i', $message) &&
                !session()->has('selected_scholarship') &&
                session()->has('last_search_results')) {
                return $this->finalizeResponse("Silakan pilih nomor beasiswa terlebih dahulu untuk mendapatkan linknya. Contoh: **link nomor 1** atau **no 2**. 😊", $normalizedData);
            }

            // 4.4.5 LINK NOMOR X
            if (preg_match('/\b(link|url|tautan)\b.*\b(nomor|no|nmr|#)\s*(\d+)/i', $message, $linkMatch) ||
                preg_match('/\b(nomor|no|nmr|#)\s*(\d+).*\b(link|url|tautan)\b/i', $message, $linkMatch2)) {
                $displayNum = isset($linkMatch[3]) ? (int)$linkMatch[3] : (int)$linkMatch2[2];
                $allResults = session()->get('last_search_all_results', []);
                $idx = $displayNum - 1;
                if ($idx >= 0 && $idx < count($allResults)) {
                    $s = (array)$allResults[$idx];
                    $name = $s['nama_beasiswa'] ?? 'Beasiswa ini';
                    $urlAsli = $s['url_asli'] ?? null;
                    $urlSchoters = $s['url'] ?? null;
                    $urlInternal = route('scholarship.detail', ['id' => $s['id']]);
                    $ans = "Berikut link untuk **$name**:\n\n";
                    if ($urlAsli) $ans .= "🌐 [**Website Resmi**]($urlAsli)\n\n";
                    if ($urlSchoters) $ans .= "📋 [**Info di Schoters**]($urlSchoters)\n\n";
                    $ans .= "👉 [**Halaman Detail**]($urlInternal)";
                    return $this->finalizeResponse($ans, $normalizedData);
                }
            }



            // 2.1 Deteksi Pemilihan Nomor (Misal: "pilih no 1" atau "benefit no 2")
            $selectedNumber = null;
            // Jangan anggap tahun (2020-2035) sebagai nomor pilihan
            $hasYear = preg_match('/\b(20[2-3][0-9])\b/', $message);
            $isExplicitSelect = !$hasYear && (
                preg_match('/\b(?:nomor|no|pilih|nmr|#)\s*([0-9]+)\b/i', $message, $matches) ||
                preg_match('/^(?:pilih\s+|nomor\s+|no\s+|nmr\s+|#)?([0-9]+)$/i', trim($message), $matches)
            );
            if ($isExplicitSelect) {
                $num = (int)$matches[1];
                if ($num >= 1 && $num <= 50) $selectedNumber = $num;
            } else if (!$hasYear && $detailIntent && preg_match('/\b([0-9]+)\b/', $message, $matches)) {
                $num = (int)$matches[1];
                if ($num >= 1 && $num <= 20 && strlen(trim($message)) <= 15) {
                    $selectedNumber = $num;
                }
            }
            // 2.2 Jika ada intent detail dan sudah ada beasiswa terpilih
            if ($detailIntent && session()->has('selected_scholarship') && !$selectedNumber) {
                $criteria = $this->extractCriteria($message);
                $hasNewFilters = !empty($criteria['negara']) || !empty($criteria['benua']) ||
                                !empty($criteria['jenjang']) || !empty($criteria['bulan']) ||
                                !empty($criteria['funding']);
                // JIKA TIDAK ADA FILTER BARU, BERIKAN DETAIL BEASISWA SAAT INI
                if (!$hasNewFilters) {
                    return $this->handleDetailRequest($detailIntent, $normalizedData);
                }
            }
            if ($detailIntent && !session()->has('selected_scholarship') && !$selectedNumber) {
                $nameQuery = preg_replace('/\b(deadline|syarat|persyaratan|benefit|manfaat|cara|daftar|apply|info|detail)\b/i', '', $rawMessage);
                $nameQuery = trim($nameQuery);
                if (strlen($nameQuery) >= 3) {
                    $found = DB::table('scholarships')
                        ->where('nama_beasiswa', 'ilike', '%' . $nameQuery . '%')
                        ->first();
                    if ($found) {
                        $selected = (array) $found;
                        session()->put('selected_scholarship', $selected);
                        $normalizedData['selected_scholarship'] = $selected;
                        return $this->handleDetailRequest($detailIntent, $normalizedData);
                    }
                }
                // Nama tidak ditemukan di DB — beri arahan sesuai konteks
                if (session()->has('last_search_all_results')) {
                    return $this->finalizeResponse(
                        "Silakan pilih nomor beasiswa dari daftar di atas terlebih dahulu. Ketik angka seperti: **1**, **2**, atau **3**",
                        $normalizedData
                    );
                } else {
                    return $this->finalizeResponse(
                        "Silakan cari beasiswa terlebih dahulu, lalu pilih nomor dari daftar sebelum bertanya tentang detail beasiswa. \xf0\x9f\x98\x8a",
                        $normalizedData
                    );
                }
            }

            // 2.3 Eksekusi Pemilihan Nomor
            if ($selectedNumber) {
                $allResults = session()->get('last_search_all_results', []);
                $currentPage = session()->get('last_search_page', 1);
                // Nomor <= 5 relatif ke halaman saat ini; > 5 dianggap nomor global
                $pageStartIndex = ($currentPage - 1) * 5;
                $globalIndex = ($selectedNumber <= 5)
                    ? $pageStartIndex + ($selectedNumber - 1)
                    : ($selectedNumber - 1);

                if (isset($allResults[$globalIndex])) {
                    $selected = (array)$allResults[$globalIndex];
                    session()->put('selected_scholarship', $selected);

                    // Jika ada intent detail sekaligus (misal "benefit no 1")
                    if ($detailIntent) {
                        return $this->handleDetailRequest($detailIntent, $normalizedData);
                    }
                    return $this->finalizeResponse("Anda telah memilih **{$selected['nama_beasiswa']}**.\n\nDetail apa yang ingin Anda ketahui? (Benefit, Syarat, Deadline, atau Cara Daftar)", $normalizedData);
                } else {
                    return $this->finalizeResponse("Maaf, nomor tersebut tidak valid atau tidak ada dalam daftar pencarian terakhir Anda.", $normalizedData);
                }
            }
            
            // 3.5 KONFIRMASI (Acknowledgment: Oke/Iya/Siap)
            if ($this->isAcknowledgment($message)) {
                return $this->finalizeResponse("Baik, senang bisa membantu Anda! 😊 Jika nanti ada hal lain yang ingin ditanyakan seputar beasiswa, jangan ragu untuk kembali lagi ya. Semangat dan sukses untuk studinya! 🎓✨", $normalizedData);
            }

            // 4. PERTANYAAN UMUM (FAQ / Rule-based knowledge)
            // Di mode RAG, "apa itu X" untuk definisi umum harus ditolak kecuali sangat relevan
            if ($ragEnabled) {
                $isDefinitionQuery = preg_match('/\b(apa itu|pengertian|definisi|jelaskan apa|maksud dari)\b/i', $message);
                $isScholarshipDefinition = preg_match('/\b(beasiswa|ielts|toefl|loa|motivation letter|recommendation letter|fully funded|partially funded)\b/i', $message);
                // Hanya jawab FAQ kalau sangat spesifik tentang istilah beasiswa, bukan "beasiswa itu apa" atau "apa itu ielts" umum
                if ($isDefinitionQuery && !preg_match('/\b(perbedaan|beda|compare|vs)\b/i', $message)) {
                    return $this->finalizeResponse($this->getOutOfTopicResponse(), $normalizedData);
                }
            }
            $faqAnswer = $this->handleFAQ($message);
            if ($faqAnswer) {
                return $this->finalizeResponse($faqAnswer, $normalizedData);
            }

            // 4.5 NEXT PAGE (YANG LAIN)
            $isNextPage = preg_match('/\b(lainnya|yang lain|selanjutnya|berikutnya|lagi|next|lanjut|lanjutin|terusin|lnjut|lnjutin)\b/i', $message);
            if ($isNextPage && session()->has('last_search_all_results')) {
                $criteria = $this->extractCriteria($message);
                $lastCriteria = session()->get('last_search_criteria', []);
                $isSameCountry = empty($criteria['negara']) || $criteria['negara'] === ($lastCriteria['negara'] ?? []);
                
                if ($isSameCountry) {
                    return $this->handleNextPage($normalizedData);
                }
            }



            // 6. SEARCH & FILTER (Hanya jika RAG aktif)
            if ($ragEnabled && $this->isSearchQuery($message)) {
                return $this->handleSearch($rawMessage, $message, $normalizedData);
            }

            // 1.8 TOPIC GUARD (Penyaring Topik Ketat untuk Sidang)
            $isGreeting = preg_match('/\b(halo|hai|pagi|siang|sore|malam|tanya|nanya|makasih|thanks|thank you|mks|pilih|nomor|no|nmr|#|yang lain|selanjutnya|berikutnya)\b/i', $message);
            $isSearch = $this->isSearchQuery($message);
            $isDetail = ($detailIntent !== null);
            
            // LOGIKA UTAMA: Jika RAG Aktif, WAJIB masuk salah satu kategori di atas. 
            // Jika tidak (pertanyaan random), TOLAK LANGSUNG.
            if ($ragEnabled && !$isGreeting && !$isSearch && !$isDetail) {
                return $this->finalizeResponse($this->getOutOfTopicResponse(), $normalizedData);
            }

            // 1.9 PENGECUALIAN DEFINISI (Apa itu, Pengertian, dsb)
            if ($ragEnabled && preg_match('/\b(apa itu|pengertian|definisi|jelaskan|maksud dari)\b/i', $message)) {
                return $this->finalizeResponse($this->getOutOfTopicResponse(), $normalizedData);
            }



            // 1.10 SEARCH HANDLER
            if ($ragEnabled && $isSearch) {
                return $this->handleSearch($rawMessage, $message, $normalizedData);
            }

            // 1.11 AI FALLBACK (Hanya jika RAG OFF atau lolos filter di atas)
            return $this->handlePureAI($rawMessage, $ragEnabled, $normalizedData);

        } catch (\Exception $e) {
            Log::error("Chatbot Error: " . $e->getMessage());
            
            $errorMessage = 'Maaf, terjadi gangguan saat memproses pertanyaan kamu. Silakan coba lagi nanti.';
            if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Connection timed out')) {
                $errorMessage = 'Maaf, koneksi ke server AI kami sedang lambat. Silakan coba sesaat lagi.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function normalizeText($text)
    {
        $text = strtolower(trim($text));
        $hasTypo = false;
        $typoWord = '';
        $correctedWord = '';

        $synonyms = [
            'cara daftar' => 'apply', 'cr dftr' => 'apply', 'daftar gimana' => 'apply', 'daftar gmn' => 'apply', 'cara apply' => 'apply',
            'fasilitas' => 'benefit', 'keuntungan' => 'benefit', 'manfaat' => 'benefit',
            'dana penuh' => 'fully funded', 'beasiswa penuh' => 'fully funded', 'pendanaan penuh' => 'fully funded',
            'fully fund' => 'fully funded', 'full funded' => 'fully funded', 'full fund' => 'fully funded',
            'gratis full' => 'fully funded', 'gratis penuh' => 'fully funded', 'full gratis' => 'fully funded',
            'beasiswa gratis' => 'fully funded', 'gratis total' => 'fully funded', 'biaya gratis' => 'fully funded',
            'cover semua' => 'fully funded', 'semua biaya' => 'fully funded', 'full cover' => 'fully funded',
            'lnjutin' => 'yang lain', 'lnjut' => 'yang lain', 'lanjutin' => 'yang lain', 'terusin' => 'yang lain', 'lanjut' => 'yang lain',
            'dana sebagian' => 'partially funded', 'partial funded' => 'partially funded', 'pendanaan sebagian' => 'partially funded',
            'arab saudi' => 'arab saudi',

            // KAMUS SINGKATAN / SLANG INDONESIA (SUPER LENGKAP)
            'trs' => 'terus', 'gmn' => 'gimana', 'yg' => 'yang', 'dgn' => 'dengan', 'dg' => 'dengan',
            'pebruari' => 'februari', 'febuari' => 'februari', 'pebuari' => 'februari', 'feb' => 'februari', 'peb' => 'februari',
            'nopember' => 'november', 'okey' => 'oke', 'okei' => 'oke', 'ok' => 'oke', 'siap' => 'oke', 'baik' => 'oke',
            'design' => 'desain', 'engineering' => 'teknik', 'medicine' => 'kedokteran', 'medical' => 'kedokteran', 'doctor' => 'kedokteran',
            'law' => 'hukum', 'legal' => 'hukum', 'agriculture' => 'pertanian', 'agribusiness' => 'pertanian', 'farming' => 'pertanian',
            'forestry' => 'kehutanan', 'accounting' => 'akuntansi', 'management' => 'manajemen', 'business' => 'bisnis',
            'economics' => 'ekonomi', 'finance' => 'keuangan', 'marketing' => 'pemasaran', 'education' => 'pendidikan',
            'teaching' => 'pendidikan', 'literature' => 'sastra', 'art' => 'seni', 'arts' => 'seni', 'performing' => 'pertunjukan',
            'communication' => 'komunikasi', 'architecture' => 'arsitektur', 'environment' => 'lingkungan', 'ecology' => 'lingkungan',
            'computer' => 'komputer', 'computing' => 'komputer', 'informatics' => 'komputer', 'it' => 'komputer',
            'information technology' => 'teknologi informasi', 'data science' => 'ilmu data', 'ai' => 'kecerdasan buatan',
            'artificial intelligence' => 'kecerdasan buatan', 'biology' => 'biologi', 'bio' => 'biologi',
            'chemistry' => 'kimia', 'physics' => 'fisika', 'mathematics' => 'matematika', 'math' => 'matematika',
            'statistics' => 'statistika', 'stats' => 'statistika', 'pharmacy' => 'farmasi', 'nursing' => 'keperawatan',
            'public health' => 'kesehatan masyarakat', 'psychology' => 'psikologi', 'sociology' => 'sosiologi',
            'anthropology' => 'antropologi', 'international relations' => 'hubungan internasional', 'ir' => 'hubungan internasional',
            'political science' => 'ilmu politik', 'philosophy' => 'filsafat', 'history' => 'sejarah',
            'culinary' => 'kuliner', 'tourism' => 'pariwisata', 'hospitality' => 'perhotelan', 'sports' => 'olahraga',
            'geography' => 'geografi', 'geology' => 'geologi', 'archaeology' => 'arkeologi', 'astronomy' => 'astronomi',
            'journalism' => 'jurnalistik', 'music' => 'musik', 'nutrition' => 'gizi', 'veterinary' => 'kedokteran hewan',
            'fishery' => 'perikanan', 'fisheries' => 'perikanan', 'livestock' => 'peternakan', 'linguistics' => 'linguistik',
            'germany' => 'jerman', 'france' => 'perancis', 'netherlands' => 'belanda', 'switzerland' => 'swiss',
            'spain' => 'spanyol', 'italy' => 'italia', 'egypt' => 'mesir', 'turkey' => 'turki', 'mexico' => 'meksiko',
            'brazil' => 'brasil', 'russia' => 'rusia', 'norway' => 'norwegia', 'sweden' => 'swedia', 'finland' => 'finlandia',
            'aus' => 'australia', 'oz' => 'australia', 'jp' => 'jepang', 'japan' => 'jepang',
            'south korea' => 'korea selatan', 'uk' => 'inggris', 'england' => 'inggris', 'canada' => 'kanada',
            'china' => 'cina', 'tiongkok' => 'cina', 'singapore' => 'singapura',
            'mks' => 'terima kasih', 'makasih' => 'terima kasih', 'thx' => 'terima kasih', 'thanks' => 'terima kasih', 'kalo' => 'kalau', 'kl' => 'kalau',
            'kpn' => 'kapan', 'dmn' => 'dimana', 'spy' => 'supaya', 'utk' => 'untuk', 'mks' => 'makasih',
            'sy' => 'saya', 'km' => 'kamu', 'sm' => 'sama', 'bgt' => 'banget', 'bs' => 'bisa', 'tdk' => 'tidak',
            'nmr' => 'nomor', 'no' => 'nomor', 'brp' => 'berapa', 'blm' => 'belum', 'sdh' => 'sudah',
            'jg' => 'juga', 'jd' => 'jadi', 'dr' => 'dari', 'lg' => 'lagi', 'skrg' => 'sekarang',
            'pake' => 'pakai', 'pk' => 'pakai', 'tlg' => 'tolong', 'tlng' => 'tolong', 'lgsg' => 'langsung',
            'knp' => 'kenapa', 'bgmn' => 'bagaimana', 'ad' => 'ada', 'buat' => 'untuk',
            'cr' => 'cara', 'dftr' => 'daftar', 'pndftrn' => 'pendaftaran',
            'pengen' => 'mau', 'pingin' => 'mau', 'pen' => 'mau', 'syrt' => 'persyaratan',
            'gimana caranya' => 'apply', 'cara daftarnya' => 'apply', 'cara pendaftarannya' => 'apply', 'caranya' => 'apply', 'aps' => 'apa',
            'infonya' => 'detail', 'liat' => 'detail', 'lihat' => 'detail', 'info' => 'detail', 'detail' => 'detail', 'selengkapnya' => 'detail',
            'syaratnya' => 'persyaratan', 'benefitnya' => 'benefit', 'deadlinenya' => 'deadline', 'caranya' => 'apply',
            'cara daftar' => 'apply', 'cr dftr' => 'apply', 'cara pendaftaran' => 'apply',
            'bole' => 'boleh', 'bisakah' => 'boleh', 'mau' => 'boleh'
        ];
        foreach ($synonyms as $key => $value) {
            // Gunakan preg_replace dengan word boundary agar tidak merusak kata lain 
            // (misal: 'no' tidak merubah 'nomor' menjadi 'nomormor')
            $text = preg_replace('/\b' . preg_quote($key, '/') . '\b/i', $value, $text);
        }

        // 2. Typo Tolerance dengan Algoritma Levenshtein (Sangat Pintar)
        // Mengecek kemiripan kata untuk mentoleransi typo
        $targetWords = [
            'benefit', 'benefitnya', 'persyaratan', 'syaratnya', 'syarat', 'deadline', 'deadlinenya', 
            'apply', 'daftar', 'daftarnya', 'pendaftaran', 'beasiswa', 'beasiswanya', 'pendanaan',
            'detail', 'boleh', 'terimakasih', 'makasih', 'negara', 'benua', 'kapan', 'gimana', 'dimana', 'bagaimana', 'cara',
            'jepang', 'korea', 'inggris', 'jerman', 'belanda', 'kanada', 'australia', 'malaysia', 'singapura', 'thailand', 'rusia', 'turki', 'mesir', 'prancis', 'amerika'
        ];
        // Kata-kata yang TIDAK boleh dikoreksi Levenshtein (kata lokasi penting)
        $protectedWords = ['dalam', 'negeri', 'luar', 'buka', 'masih', 'belum', 'tutup', 'bulan', 'tahun'];
        $words = explode(' ', $text);
        foreach ($words as &$word) {
            // Skip kata yang dilindungi
            if (in_array($word, $protectedWords)) continue;
            // Periksa kata dengan panjang >= 3
            if (strlen($word) >= 3) {
                foreach ($targetWords as $target) {
                    $dist = levenshtein($word, $target);
                    if ($word !== $target && $dist > 0 && $dist <= 2) {
                        // Khusus kata sangat pendek (3 huruf), hanya toleransi 1 kesalahan agar tidak salah koreksi
                        if (strlen($word) == 3 && $dist > 1) continue;

                        $hasTypo = true;
                        $typoWord = $word;
                        $correctedWord = $target;
                        $word = $target; // Tetap dikoreksi di internal text
                        break;
                    }
                }
            }
        }
        $text = implode(' ', $words);

        return [
            'text' => $text,
            'hasTypo' => $hasTypo,
            'typoWord' => $typoWord,
            'correctedWord' => $correctedWord
        ];
    }

private function finalizeResponse($answer, $normalizedData = null, $success = true)
{
    $responseTime = isset($this->startTime) ? round((microtime(true) - $this->startTime) * 1000, 2) : null;
    if ($responseTime !== null) {
        Log::info("Chatbot Response Time: {$responseTime}ms | Success: " . ($success ? 'true' : 'false'));
    }
    try {
        \App\Models\ChatLog::create([
            'user_message'      => $normalizedData['text'] ?? '',
            'bot_response'      => $answer,
            'intent'            => $normalizedData['intent'] ?? null,
            'response_time'     => $responseTime ? round($responseTime / 1000, 4) : null,
            'vector_search_time'=> 0,
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::warning('ChatLog gagal disimpan: ' . $e->getMessage());
    }
    return response()->json([
        'success' => $success,
        'answer' => $answer,
        'response_time_ms' => $responseTime
    ]);
}

    private function isGreeting($message)
    {
        $greetings = ['halo', 'hai', 'hi', 'hello', 'pagi', 'siang', 'sore', 'malam', 'permisi', 'assalamualaikum'];
        $intents = ['mau nanya', 'tanya dong', 'boleh tanya', 'nanya dong', 'saya mau tanya', 'boleh nanya', 'bisakah saya tanya', 'ada yang mau saya tanyakan', 'tanya ngga'];
        
        // Gunakan Regex agar "p" tidak mendeteksi huruf di tengah kata (seperti depok)
        $isGreet = false;
        foreach (array_merge($greetings, $intents) as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $message)) {
                $isGreet = true;
                break;
            }
        }

        // Khusus untuk "p" sebagai salam singkat, harus berdiri sendiri
        if (!$isGreet && preg_match('/^p$/i', trim($message))) {
            $isGreet = true;
        }

        if ($isGreet) {
            $searchKeywords = ['beasiswa', 'scholarship', 's1', 's2', 's3', 'negara', 'bulan', 'deadline', 'apply', 'benefit', 'syarat'];
            foreach ($searchKeywords as $kw) {
                if (str_contains($message, $kw)) return false; 
            }
            return true;
        }
        return false;
    }

    /**
     * Memberikan jawaban salam yang dinamis berdasarkan input user
     */
    private function getGreetingResponse($message)
    {
        // Jika user minta izin bertanya
        if (str_contains($message, 'tanya') || str_contains($message, 'nanya')) {
            $responses = [
                "Tentu, silakan! Dengan senang hati saya akan membantu 😊 Apa yang ingin Anda tanyakan seputar beasiswa?",
                "Boleh banget! Apa nih yang ingin kamu tanyain seputar info beasiswa? Aku siap bantu jawab ya! 😊",
                "Silakan! ScholarBot siap membantu menjawab keraguan kamu seputar beasiswa. Mau tanya tentang apa nih? 🎓"
            ];
            return $responses[array_rand($responses)];
        }

        // Cek apakah ada sapaan waktu
        $timeGreeting = '';
        if (str_contains($message, 'pagi')) $timeGreeting = 'pagi';
        elseif (str_contains($message, 'siang')) $timeGreeting = 'siang';
        elseif (str_contains($message, 'sore')) $timeGreeting = 'sore';
        elseif (str_contains($message, 'malam')) $timeGreeting = 'malam';

        if ($timeGreeting) {
            $responses = [
                "Selamat " . $timeGreeting . " juga! 😊 Ada yang bisa saya bantu terkait informasi beasiswa?",
                "Halo, selamat " . $timeGreeting . "! 👋 Ada yang ingin Anda tanyakan seputar beasiswa hari ini?",
                "Selamat " . $timeGreeting . "! 😊 Kabar baik hari ini? Ada yang bisa saya bantu untuk mencari beasiswa?",
                "Halo! Selamat " . $timeGreeting . " juga. Ada hal yang bisa saya bantu mengenai informasi beasiswa?",
                "Hai, selamat " . $timeGreeting . "! Semangat terus ya cari beasiswanya. Ada yang mau ditanyakan?",
                "Selamat " . $timeGreeting . "! Senang sekali bisa membantu Anda hari ini. Mau cari beasiswa di negara mana nih? 🎓"
            ];
        } else {
            // Jika user sekadar menyapa umum (Halo/Hi/Assalamualaikum)
            $responses = [
                "Halo! 😊 Ada yang bisa dibantu mengenai informasi beasiswa?",
                "Halo! 👋 Ada yang bisa saya bantu terkait informasi beasiswa hari ini?",
                "Hi! 😊 Ada yang bisa saya bantu untuk mencari beasiswa yang sesuai dengan Anda?",
                "Halo! Ada yang bisa saya bantu mengenai informasi beasiswa atau studi luar negeri?",
                "Halo, pejuang beasiswa! 👋 Apa yang bisa saya bantu hari ini?",
                "Hai! ScholarBot di sini siap membantu kamu cari info beasiswa terbaik. Ada yang ingin ditanyakan? 😊"
            ];
        }

        return $responses[array_rand($responses)];
    }

    private function isAcknowledgment($message)
    {
        $acks = ['oke', 'okey', 'siap', 'sip', 'iya', 'baik', 'baiklah', 'ok', 'okei', 'paham', 'mengerti', 'yoi', 'yup', 'mantap'];
        $isAck = false;
        foreach ($acks as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $message)) {
                $isAck = true;
                break;
            }
        }
        
        if ($isAck) {
            // Jika pesan mengandung kata kunci pencarian atau cukup panjang, jangan anggap sebagai acknowledgment saja
            if ($this->isSearchQuery($message) || strlen($message) > 15) {
                return false;
            }
            // Jika ada kata kunci beasiswa/deadline/buka, bukan acknowledgment
            if (preg_match('/\b(beasiswa|scholarship|buka|dibuka|deadline|bulan|tahun|masih|belum|tutup)\b/i', $message)) {
                return false;
            }
            return true;
        }
        return false;
    }

    private function isThankYou($message)
    {
        $thanks = ['terima kasih', 'terimakasih', 'makasih', 'suwun', 'thanks', 'thx', 'thank you', 'tengkyu', 'mksh', 'maturnuwun', 'tks'];
        foreach ($thanks as $word) {
            if (str_contains($message, $word)) return true;
        }
        return false;
    }

    private function getThankYouResponse()
    {
        $responses = [
            "Sama-sama! Senang bisa membantu Anda 😊 Jika ada hal lain yang ingin ditanyakan seputar beasiswa, jangan ragu untuk bertanya ya!",
            "Terima kasih kembali! Semoga sukses dengan pendaftaran beasiswanya 🎓✨",
            "Sama-sama! Semangat terus pejuang beasiswa! 💪 Ada lagi yang bisa saya bantu?",
            "Anytime! Senang bisa menemani pencarian beasiswa Anda hari ini. Sukses terus ya! 😊"
        ];
        return $responses[array_rand($responses)];
    }

    private function isSearchQuery($message)
    {
        // Deteksi khusus: pertanyaan deadline/sorting yang implisit soal beasiswa
        $deadlineSearchPhrases = [
            'deadline terdekat', 'deadline paling dekat', 'paling dekat deadline',
            'masih buka', 'yang masih buka', 'belum tutup', 'masih dibuka',
            'tutup bulan', 'deadline bulan', 'batas bulan', 'tutup di bulan',
            'tutup bulan', 'yang tutup', 'beasiswa tutup',
            'tahun 2025', 'tahun 2026', 'tahun 2027',
            'di tahun 2025', 'di tahun 2026', 'di tahun 2027',
            'tahun ini', 'bulan ini', 'bulan depan',
        ];
        foreach ($deadlineSearchPhrases as $phrase) {
            if (str_contains($message, $phrase)) return true;
        }

        // Deteksi pola "beasiswa ... bulan [nama bulan]"
        if (preg_match('/\b(tutup|deadline|batas)\b.*\b(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\b/i', $message)) {
            return true;
        }
        // Deteksi "deadline paling dekat" tanpa kata beasiswa
        if (preg_match('/\b(deadline|tutup|batas)\s+(paling\s+)?(dekat|terdekat|segera)\b/i', $message)) {
            return true;
        }

        // Blokir query yang jelas tidak relevan dengan beasiswa (level pendidikan tidak sesuai)
        if (preg_match('/\b(smp|sd|sma|tk|anak smp|anak sd|anak sma)\b/i', $message) && 
            !preg_match('/\b(beasiswa sma|beasiswa smp)\b/i', $message)) {
            return false;
        }

        // KEYWORD WAJIB (Must Have) - Salah satu harus ada agar dianggap pencarian beasiswa
        $mustHave = [
            'beasiswa', 'scholarship', 'kuliah', 'studi', 'daftar', 'apply', 'registrasi', 
            'pendaftaran', 's1', 's2', 's3', 'd3', 'd4', 'jenjang', 'sarjana', 'magister', 'doktor',
            'fully funded', 'partially funded', 'fully fund', 'partial fund', 'dana penuh', 'dana sebagian'
        ];
        
        $hasStrongKeyword = false;
        foreach ($mustHave as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $message)) {
                // Pengecualian: 'mata kuliah' bukan beasiswa
                if ($kw === 'kuliah' && preg_match('/\bmata\s+kuliah\b/i', $message) && !str_contains($message, 'beasiswa')) {
                    continue;
                }
                // Pengecualian: 'bantuan kuliah', 'biaya kuliah', 'cover kuliah' tanpa kata beasiswa = OOT
                if ($kw === 'kuliah' && preg_match('/\b(bantuan|biaya|cover|ukt|spp|cicilan)\b/i', $message) && !preg_match('/\bbeasiswa\b/i', $message)) {
                    continue;
                }
                $hasStrongKeyword = true;
                break;
            }
        }

        // Jika tidak ada keyword wajib, cek apakah ada kombinasi (Negara/Bulan + Jurusan/Prodi)
        if (!$hasStrongKeyword) {
            $criteria = $this->extractCriteria($message);
            $hasLocationOrTime = !empty($criteria['negara']) || !empty($criteria['benua']) || !empty($criteria['bulan']);
            $hasField = preg_match('/\b(jurusan|prodi|bidang|fakultas)\b/i', $message);
            
            if (!($hasLocationOrTime && $hasField)) {
                return false;
            }
        }

        return true;
    }

    private function isQuantificationQuery($message)
    {
        $keywords = ['hanya', 'cuma', 'berapa', 'jumlah', 'total', 'sedikit', 'banyak', 'doang'];
        foreach ($keywords as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $message)) {
                return true;
            }
        }
        return false;
    }

    private function getLocContext($criteria)
    {
        $locContext = "";
        if (!empty($criteria['negara_ori'])) {
            $locContext = " di " . ucwords($criteria['negara_ori']);
        } elseif (!empty($criteria['benua'])) {
            $locContext = " di " . ucwords($criteria['benua'][0]);
        } elseif (!empty($criteria['negara'])) {
            $locContext = " di " . ucwords($criteria['negara'][0]);
        }
        return $locContext;
    }

    private function isOutOfTopic($message)
    {
        // Jika user sedang dalam konteks melihat detail beasiswa, beri sedikit toleransi untuk pertanyaan follow-up
        if (session()->has('selected_scholarship') || session()->has('last_search_results')) {
            // Tetap blokir kata-kata yang SANGAT tidak relevan meskipun ada session
            $extremeForbidden = [
                'resep', 'masak', 'makan', 'minum', 'tidur', 'politik', 'agama', 'sholat', 'doa', 'game', 
                'shopee', 'tokopedia', 'tiktok', 'cuaca', 'judi', 'slot', 'hack', 'bobol', 'musik', 'lagu', 
                'coding', 'pacar', 'menikah', 'sedih', 'happy', 'senang', 'bahagia', 'kecewa', 'bingung', 'bimbang',
                'bola', 'film', 'nonton', 'bioskop', 'loker', 'lowongan', 'cpns', 'pns', 'gaji', 'harga',
                'laptop', 'hp', 'handphone', 'iphone', 'android', 'berita', 'presiden', 'menteri',
                'mata kuliah', 'materi kuliah', 'materi pelajaran', 'tugas kuliah', 'ujian', 'skripsi',
                'algoritma', 'pengolahan paralel', 'struktur data', 'basis data', 'jaringan komputer'
            ];
            foreach ($extremeForbidden as $bad) {
                if (str_contains($message, $bad)) return true;
            }
            return false; 
        }

        // BLOKIR AWAL: Frasa yang pasti OOT meskipun ada kata 'kuliah'
        $earlyOotPhrases = [
            'bantuan kuliah', 'ada bantuan', 'bantuan biaya', 'cover ukt', 'ukt doang',
            'spp doang', 'biaya kuliah doang', 'cicilan kuliah', 'kos-kosan', 'biaya kos',
        ];
        foreach ($earlyOotPhrases as $phrase) {
            if (str_contains($message, $phrase) && !preg_match('/\bbeasiswa\b/i', $message)) {
                return true;
            }
        }

        // 1. Kata kunci utama yang WAJIB ada salah satunya agar dianggap relevan
        $scholarshipKeywords = [
            'beasiswa', 'scholarship', 'apply', 'daftar', 'pendaftaran',
            'deadline', 'ielts', 'toefl', 'loa', 's1', 's2', 's3', 'd3', 'd4', 'syarat', 'persyaratan', 
            'benefit', 'biaya', 'funding', 'pendanaan', 'negara', 'benua', 'akademik', 'edukasi',
            'fully funded', 'partially funded', 'masih buka', 'tutup', 'buka'
        ];

        // Deteksi angka tahun 2026/2027 dalam konteks beasiswa — jangan blokir
        if (preg_match('/\b(20[2-3][0-9])\b/', $message)) {
            return false;
        }

        $hasScholarshipContext = false;
        foreach ($scholarshipKeywords as $kw) {
            if (str_contains($message, $kw)) {
                $hasScholarshipContext = true;
                break;
            }
        }

        // Khusus 'kuliah' dan 'studi', cek apakah dia 'mata kuliah' atau semacamnya
        if (!$hasScholarshipContext) {
            if (preg_match('/\b(kuliah|studi)\b/i', $message)) {
                if (preg_match('/\bmata\s+kuliah\b/i', $message) || preg_match('/\b(belajar|materi|tugas)\b/i', $message)) {
                    return true; // Dianggap OOT
                }
                // "bantuan kuliah", "biaya kuliah" tanpa kata beasiswa = OOT
                if (preg_match('/\b(bantuan|biaya|cover|ukt|spp|kos|pondok)\b/i', $message) && 
                    !preg_match('/\bbeasiswa\b/i', $message)) {
                    return true;
                }
                $hasScholarshipContext = true;
            }
        }

        // Blokir "UKT", "SPP", "bantuan kuliah" tanpa konteks beasiswa
        if (preg_match('/\b(ukt|spp|biaya kuliah|bantuan kuliah|cover ukt|cicilan)\b/i', $message) &&
            !preg_match('/\bbeasiswa\b/i', $message)) {
            return true;
        }

        // 2. Jika ada kata kunci beasiswa, anggap masuk topik
        if ($hasScholarshipContext) return false;

        // 3. Deteksi Matematika (Misal: 1+1, 10 * 5, dll)
        if (preg_match('/[0-9]+[\s]*[\+\-\*\/\^=][\s]*[0-9]+/', $message)) {
            return true;
        }

        // 4. Jika tidak ada kata kunci beasiswa, cek kata kunci institusi yang sering ditanyakan (tapi berisiko OOT)
        $broadKeywords = ['universitas', 'kampus', 'institut', 'sekolah', 'jurusan', 'prodi', 'fakultas'];
        foreach ($broadKeywords as $broad) {
            if (str_contains($message, $broad)) {
                // "Universitas" tanpa kata "beasiswa/kuliah/studi" dianggap Out of Topic
                return true; 
            }
        }

        // 5. Daftar kata yang menunjukkan pertanyaan di luar topik (Blacklist Luas)
        $forbiddenKeywords = [
            // Umum & Hiburan
            'polisi', 'cuaca', 'resep', 'makan', 'masak', 'game', 'berita', 'politik', 'presiden', 'menteri', 'pemilu',
            'agama', 'sholat', 'doa', 'cinta', 'pacar', 'menikah', 'belanja', 'harga', 'shopee', 'tokopedia', 'tiktok', 'tidur',
            'makanan', 'minuman', 'restoran', 'lokasi', 'alamat', 'peta', 'film', 'bioskop', 'download', 'nonton',
            'bola', 'pemain', 'atlet', 'klub', 'skor', 'pertandingan',
            
            // Teknologi & Gadget (Non-Edukasi)
            'laptop', 'gaming', 'hp', 'handphone', 'spek', 'iphone', 'android', 'rakit pc',
            'react', 'programming', 'coding', 'laravel', 'python', 'javascript', 'html', 'css',
            
            // Personal & Emosi
            'siapa kamu', 'siapa anda', 'pencipta', 'dibuat oleh', 'nama kamu', 'umur kamu',
            'sedih', 'capek', 'lelah', 'pusing', 'galau', 'kesal', 'marah', 'bosan', 'curhat',
            
            // Keamanan & Ilegal
            'hack', 'hacking', 'bobol', 'virus', 'malware', 'phishing', 'judi', 'slot', 'gacor', 'link judi',
            
            // Karir & Pekerjaan Umum
            'loker', 'lowongan', 'kerja', 'gaji', 'cpns', 'pns', 'magang umum', 'karir',
            
            // Kata Tanya/Instruksi Umum (Tanpa Konteks Beasiswa)
            'metode', 'cara', 'tutor', 'tutorial', 'tips', 'trik', 'rekomendasi', 'apa itu'
        ];

        foreach ($forbiddenKeywords as $bad) {
            if (str_contains($message, $bad)) return true;
        }

        // 6. Kata tanya umum di awal kalimat tanpa konteks beasiswa
        if (preg_match('/^(siapa|dimana|kapan|mengapa|bagaimana|apa)\b/i', trim($message))) {
            return true;
        }

        // 7. Default: Jika pesan lumayan panjang tapi tidak mengandung kata kunci beasiswa sama sekali
        return strlen($message) > 10; 
    }

    private function handleFAQ($message)
    {
        if (str_contains($message, 'perbedaan') && str_contains($message, 'fully funded') && str_contains($message, 'partially funded')) {
            return "Perbedaan utamanya:\n- **Fully Funded** menanggung seluruh biaya (kuliah, hidup, tiket, dll).\n- **Partially Funded** hanya menanggung sebagian biaya (misal hanya biaya kuliah atau uang saku saja).";
        }
        if (str_contains($message, 'apa itu fully funded') || (str_contains($message, 'fully funded') && str_contains($message, 'apa itu'))) {
            return "Fully Funded adalah jenis beasiswa yang menanggung seluruh biaya studi, biasanya mencakup biaya kuliah (tuition fee), biaya hidup (living allowance), asuransi kesehatan, hingga tiket pesawat.";
        }
        if (str_contains($message, 'apa itu partially funded') || (str_contains($message, 'partially funded') && str_contains($message, 'apa itu'))) {
            return "Partially Funded adalah beasiswa pendanaan sebagian. Beasiswa ini hanya menanggung sebagian biaya, misalnya hanya biaya kuliah saja, atau hanya memberikan uang saku tertentu tanpa menanggung tiket pesawat.";
        }
        if (str_contains($message, 'apa itu ielts')) {
            return "IELTS (International English Language Testing System) adalah tes standar internasional untuk mengukur kemampuan bahasa Inggris bagi mereka yang ingin kuliah atau bekerja di negara berbahasa Inggris.";
        }
        if (str_contains($message, 'apa itu loa')) {
            return "LoA (Letter of Acceptance) adalah surat resmi dari universitas yang menyatakan bahwa Anda telah diterima sebagai mahasiswa di universitas tersebut. LoA sering menjadi salah satu syarat mendaftar beasiswa.";
        }
        if (str_contains($message, 'apa itu motivation letter')) {
            return "Motivation Letter adalah surat esai yang menjelaskan alasan Anda mendaftar beasiswa/universitas, latar belakang, serta rencana masa depan Anda. Ini sangat penting untuk meyakinkan panitia seleksi.";
        }
        if (str_contains($message, 'apa itu recommendation letter') || str_contains($message, 'surat rekomendasi')) {
            return "Recommendation Letter (Surat Rekomendasi) adalah surat dari dosen, atasan, atau tokoh akademis yang memberikan testimoni positif tentang kemampuan dan karakter Anda untuk mendukung pendaftaran beasiswa.";
        }
        return null;
    }

    // Fungsi handleSelection dihapus karena logikanya sudah terintegrasi di ask()
    
    private function getDetailIntent($message)
    {
        $m = strtolower($message);

        // Jika pesan mengandung kata pencarian atau filter (negara, jenjang, bulan, funding), ini BUKAN detail intent
        $isSearchPhrase = preg_match('/\b(beasiswa|cari|tampilkan|berikan|kasih|ada|list|daftar beasiswa|mau cari|mau tanya|info beasiswa)\b/i', $m);
        $hasFilter = preg_match('/\b(s1|s2|s3|d3|d4|luar negeri|dalam negeri|fully funded|partially funded|bulan|januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|jepang|korea|australia|eropa|asia|amerika|inggris|jerman|belanda|kanada|rusia|turki|mesir|prancis|singapura|malaysia|thailand|cina|china)\b/i', $m);

        // Jika ada filter atau kata pencarian, ini pencarian baru — bukan detail intent
        if ($isSearchPhrase || $hasFilter) {
            // Pengecualian: "benefit no X" atau "syarat no X" tetap dianggap detail intent + nomor
            if (!preg_match('/\b(benefit|syarat|persyaratan|deadline|apply|cara daftar)\b/i', $m)) {
                return null;
            }
        }

        // Prioritas: Cara Daftar / Apply
        if (str_contains($m, 'apply') || str_contains($m, 'registrasi') || str_contains($m, 'gabung') || str_contains($m, 'join')) {
            return 'apply';
        }
        // "cara daftar" atau "pendaftaran" tanpa konteks pencarian
        if (!$isSearchPhrase && !$hasFilter && (str_contains($m, 'daftar') || str_contains($m, 'pendaftaran'))) {
            return 'apply';
        }
        if (str_contains($m, 'benefit') || str_contains($m, 'cakupan') || str_contains($m, 'manfaat')) return 'benefit';
        if (str_contains($m, 'syarat') || str_contains($m, 'persyaratan') || str_contains($m, 'kualifikasi')) return 'persyaratan';
        // "deadline" hanya jika bukan query pencarian dengan filter bulan
        if (!$isSearchPhrase && !$hasFilter && (str_contains($m, 'deadline') || str_contains($m, 'batas') || str_contains($m, 'tutup'))) return 'deadline';
        if (str_contains($m, 'dana') || str_contains($m, 'biaya') || str_contains($m, 'funding')) return 'funding';
        if (!$isSearchPhrase && !$hasFilter && (str_contains($m, 'detail') || str_contains($m, 'info') || str_contains($m, 'lengkap'))) return 'detail';
        return null;
    }


    private function handleDetailRequest($intent, $normalizedData)
    {
        $selected = session()->get('selected_scholarship');
        $name = $selected['nama_beasiswa'];
        $ans = "";

        switch ($intent) {
            case 'benefit': 
                $content = $selected['benefit'] ?? '-';
                // UJI COBA 3: Jika data di dataset kosong/minim, gunakan AI untuk melengkapi
                if ($content === '-' || strlen($content) < 20) {
                    return $this->handlePureAI("Tolong jelaskan apa saja benefit atau cakupan beasiswa dari {$name} secara detail.", true, $normalizedData, true);
                }
                $translated = $this->translateToIndonesian($content);
                $ans = "Benefit **$name**:\n" . $translated; 
                break;
            case 'persyaratan': 
            case 'syarat': 
                $content = $selected['persyaratan'] ?? '-';
                // UJI COBA 3: Jika data di dataset kosong/minim, gunakan AI untuk melengkapi
                if ($content === '-' || strlen($content) < 20) {
                    return $this->handlePureAI("Tolong jelaskan apa saja syarat pendaftaran beasiswa {$name} secara detail.", true, $normalizedData, true);
                }
                $translated = $this->translateToIndonesian($content);
                $ans = "Persyaratan **$name**:\n" . $translated; 
                break;
            case 'deadline': 
                $ans = "Deadline **$name**: " . ($selected['deadline'] ?? '-'); 
                break;
            case 'funding':
                $ans = "Kategori Pendanaan **$name**: " . ($selected['kategori'] ?? '-');
                break;
            case 'apply':
                $urlInternal = route('scholarship.detail', ['id' => $selected['id']]);
                $urlSchoters = $selected['url'] ?? null;
                $urlAsli = $selected['url_asli'] ?? null;
                $ans = "Untuk mendaftar beasiswa **$name**, berikut link yang bisa kamu akses:\n\n";
                if ($urlAsli) $ans .= "🌐 [**Website Resmi Beasiswa**]($urlAsli)\n\n";
                if ($urlSchoters) $ans .= "📋 [**Info Lengkap di Schoters**]($urlSchoters)\n\n";
                $ans .= "👉 [**Halaman Detail Internal**]($urlInternal)";
                break;
            case 'detail':
                $ans = "Berikut ringkasan informasi untuk **$name**:\n\n" .
                       "📍 **Negara**: " . ($selected['negara'] ?? '-') . "\n" .
                       "🎓 **Jenjang**: " . ($selected['jenjang'] ?? '-') . "\n" .
                       "📅 **Deadline**: " . ($selected['deadline'] ?? '-') . "\n" .
                       "💰 **Pendanaan**: " . ($selected['kategori'] ?? '-') . "\n\n" .
                       "Detail apa lagi yang ingin Anda ketahui? (Ketik: **Benefit**, **Syarat**, atau **Cara Daftar**)";
                break;
        }
        return $this->finalizeResponse($ans, $normalizedData);
    }

    private function handleNextPage($normalizedData)
    {
        $allResults = session()->get('last_search_all_results', []);
        $page = session()->get('last_search_page', 1);
        
        $nextPage = $page + 1;
        $startIndex = ($nextPage - 1) * 5;
        
        if ($startIndex >= count($allResults)) {
            return $this->finalizeResponse("Maaf, sudah tidak ada lagi daftar beasiswa lainnya untuk pencarian tersebut.", $normalizedData);
        }

        $limitedResults = array_slice($allResults, $startIndex, 5);
        session()->put('last_search_page', $nextPage);
        session()->put('last_search_results', $limitedResults);
        session()->forget('selected_scholarship');

        $resp = "Berikut daftar beasiswa selanjutnya:\n\n";
        $lastCriteria = session()->get('last_search_criteria', []);
        $showDeadline = !empty($lastCriteria['bulan']) || !empty($lastCriteria['sort_deadline']);
        foreach ($limitedResults as $i => $s) {
            $s = (array) $s;
            $namaBeasiswa = trim($s['nama_beasiswa']);
            // Gunakan penomoran berlanjut (misal: 6. 7. 8...)
            $displayNumber = $startIndex + $i + 1;
            if ($showDeadline && !empty($s['deadline']) && $s['deadline'] !== '-') {
                $deadlineFormatted = $this->formatDeadline($s['deadline']);
                $resp .= $displayNumber . "\. **{$namaBeasiswa}** - Deadline: " . $deadlineFormatted . "\n\n";
            } else {
                $resp .= $displayNumber . "\. **{$namaBeasiswa}** - " . ($s['negara'] ?? 'Luar Negeri') . " (" . ($s['jenjang'] ?? '-') . ")\n\n";
            }
        }

        if (count($allResults) > $startIndex + 5) {
            $resp .= "Masih ada beasiswa lainnya. Ketik **'yang lain'** untuk melihat daftar selanjutnya, atau silakan pilih nomor beasiswa untuk melihat detail.";
        } else {
            $resp .= "Silakan pilih nomor beasiswa untuk melihat detail seperti benefit, syarat, deadline, atau cara daftar.";
        }
        
        return $this->finalizeResponse($resp, $normalizedData);
    }

    private function handleSearch($rawText, $message, $normalizedData)
    {
        $criteria = $this->extractCriteria($message);
        // Merge dengan kriteria dari rawText untuk menangkap kata yang berubah saat normalisasi
        $rawCriteria = $this->extractCriteria(strtolower($rawText));
        if ($criteria['lokasi_tipe'] === null && $rawCriteria['lokasi_tipe'] !== null) {
            $criteria['lokasi_tipe'] = $rawCriteria['lokasi_tipe'];
        }
        if (empty($criteria['negara']) && !empty($rawCriteria['negara'])) {
            $criteria['negara'] = $rawCriteria['negara'];
            $criteria['negara_ori'] = $rawCriteria['negara_ori'];
        }
        if (empty($criteria['bulan']) && !empty($rawCriteria['bulan'])) {
            $criteria['bulan'] = $rawCriteria['bulan'];
        }
        // Jika tidak ada kriteria apapun dan tidak ada konteks sebelumnya, minta perjelas
        $hasAnyCriteria = !empty($criteria['negara']) || !empty($criteria['benua']) || !empty($criteria['jenjang']) ||
            !empty($criteria['bulan']) || !empty($criteria['funding']) || !empty($criteria['bidang']) || 
            $criteria['lokasi_tipe'] !== null || !empty($criteria['tahun']) || !empty($criteria['masih_buka']) ||
            !empty($criteria['sort_deadline']);

        // Deteksi lokasi fiktif/tidak valid: "beasiswa di [kata]" dimana kata bukan negara/tempat di DB
        // Contoh: "beasiswa di Mars", "beasiswa Hogwarts", "beasiswa di Atlantis"
        if (!$hasAnyCriteria || empty($criteria['negara'])) {
            $locationMatch = null;
            $skipLocationWords = [
                'tahun', 'bulan', 'deadline', 'tutup', 'buka', 'dekat', 'terdekat', 'paling',
                'masih', 'belum', 'terbaru', 'terbuka', 'segera', 'sarjana', 'magister', 'doktor',
                'fully', 'funded', 'partial', 'funded', 'khusus', 'gratis', 'penuh', 'sebagian',
                '2025', '2026', '2027', '2024', '2028', '2029', '2030',
            ];
            if (preg_match('/\b(?:di|dari|untuk|negara|kota)\s+([a-z][a-z\s]{2,20})\b/i', $rawText, $lm)) {
                $candidate = strtolower(trim($lm[1]));
                $isSkip = false;
                foreach ($skipLocationWords as $skip) {
                    if (str_contains($candidate, $skip)) { $isSkip = true; break; }
                }
                if (!$isSkip) $locationMatch = $candidate;
            } elseif (preg_match('/\bbeasiswa\s+([a-z][a-z]{3,20})\b/i', $rawText, $lm)) {
                // "beasiswa Hogwarts" - kata setelah beasiswa yang bukan keyword umum
                $skipWords = ['ini', 'itu', 'yang', 'untuk', 'luar', 'dalam', 'negeri', 'tanpa', 'dengan',
                              'khusus', 'semua', 'bisa', 'ada', 'dari', 'terbaik', 'terbaru', 'populer',
                              's1', 's2', 's3', 'd3', 'd4', 'fully', 'funded', 'partial',
                              'tutup', 'buka', 'deadline', 'masih', 'belum', 'dekat', 'paling',
                              'sarjana', 'magister', 'doktor', 'gratis', 'penuh'];
                $candidate = strtolower(trim($lm[1]));
                if (!in_array($candidate, $skipWords)) {
                    $locationMatch = $candidate;
                }
            }

            if ($locationMatch) {
                // Cek apakah kata ini ada di DB negara
                $allCountriesRaw = DB::table('scholarships')->distinct()->whereNotNull('negara')->pluck('negara')->toArray();
                $allCountriesFlat = [];
                foreach ($allCountriesRaw as $raw) {
                    $parts = explode(',', str_replace(['luar negeri (', 'dalam negeri (', ')'], '', strtolower($raw)));
                    foreach ($parts as $p) {
                        $allCountriesFlat[] = trim($p);
                    }
                }
                $allCountriesFlat = array_unique(array_filter($allCountriesFlat));
                // Kata-kata yang valid dan bukan nama tempat (jangan salah deteksi)
                $validKeywords = ['luar', 'dalam', 'negeri', 'negara', 'internasional', 'local', 'semua', 'jenjang'];
                $isKnownCountry = in_array($locationMatch, $allCountriesFlat) || in_array($locationMatch, $validKeywords);
                // Cek juga partial match (misal "jepang" ada di "luar negeri (jepang)")
                if (!$isKnownCountry) {
                    foreach ($allCountriesFlat as $c) {
                        if (str_contains($c, $locationMatch) || str_contains($locationMatch, $c)) {
                            $isKnownCountry = true;
                            break;
                        }
                    }
                }
                if (!$isKnownCountry) {
                    $displayName = ucwords($locationMatch);
                    return $this->finalizeResponse("Mohon maaf, ScholarBot tidak memiliki data beasiswa untuk **$displayName**. ScholarBot hanya menyediakan data beasiswa tahun **2025**, **2026**, dan **2027** untuk lokasi yang tersedia. Coba cari negara lain ya! 😊", $normalizedData);
                }
            }
        }

        if (!$hasAnyCriteria && !session()->has('last_search_criteria')) {
            return $this->finalizeResponse("Silakan sebutkan kriteria beasiswa yang kamu cari, misalnya negara, jenjang (S1/S2/S3), atau jenis pendanaan. Contoh: **Beasiswa S2 di Jepang** atau **Beasiswa Fully Funded luar negeri**. 😊", $normalizedData);
        }
        // CONTEXT MERGING: Jika kriteria baru minim, gunakan kriteria dari pencarian sebelumnya
        if (session()->has('last_search_criteria')) {
            $lastCriteria = session()->get('last_search_criteria');
            $isNewMajor = !empty($criteria['bidang']);
            $isNewLevel = !empty($criteria['jenjang']);
            $isNewCountry = !empty($criteria['negara']) || !empty($criteria['benua']);
            $isNewLokasi = $criteria['lokasi_tipe'] !== null;
            $hasFunding = !empty($criteria['funding']);

            // Jika ada kriteria BARU yang eksplisit (negara baru, jurusan baru, lokasi baru) → RESET konteks lama
            $isNewSearch = $isNewCountry || $isNewMajor || $isNewLokasi || $hasFunding;
            if ($isNewSearch) {
                // Pakai kriteria baru saja, jangan merge dengan lama
                // Tapi tetap inherit jenjang kalau tidak disebutkan ulang dan bukan topik baru total
                if (!$isNewLevel && !$isNewCountry && !$isNewLokasi && $isNewMajor && !empty($lastCriteria['jenjang'])) {
                    $criteria['jenjang'] = $lastCriteria['jenjang'];
                }
            } else {
                // Follow-up: pesan pendek atau tidak ada filter baru → merge
                $isFollowUp = strlen($message) < 25 || preg_match('/\b(doang|cuma|hanya|berapa|itu|tadi|lagi|aja|saja)\b/i', $message);
                if ($isFollowUp && !$isNewLevel) {
                    if (empty($criteria['negara']) && empty($criteria['benua']) && !empty($lastCriteria['negara'])) {
                        $criteria['negara'] = $lastCriteria['negara'];
                        $criteria['negara_ori'] = $lastCriteria['negara_ori'];
                    }
                    if (empty($criteria['jenjang']) && !empty($lastCriteria['jenjang'])) {
                        $criteria['jenjang'] = $lastCriteria['jenjang'];
                    }
                }
            }
        }

        // Tentukan query untuk embedding. Jika query sangat pendek (follow-up), 
        // gunakan gabungan kriteria untuk hasil pencarian yang lebih relevan.
        $searchQuery = $rawText;
        if (strlen($message) < 15 && !empty($criteria['negara'])) {
            $searchQuery = "beasiswa " . implode(' ', $criteria['negara']);
        }

        $embedding = $this->generateEmbedding($searchQuery);
        $rawResults = DB::select("SELECT * FROM hybrid_search(?::text, ?::vector, ?::int)", [
            $searchQuery, '[' . implode(',', $embedding) . ']', 1000
        ]);

        $filtered = $this->applyStrictFilters($rawResults, $criteria);

        // Jika filter tahun 2025, 2026 atau 2027 tapi kosong, hapus filter tahun dan coba lagi tanpa filter tahun
        if (empty($filtered) && !empty($criteria['tahun']) && in_array($criteria['tahun'], ['2025', '2026', '2027'])) {
            $criteriaNoTahun = $criteria;
            unset($criteriaNoTahun['tahun']);
            $filtered = $this->applyStrictFilters($rawResults, $criteriaNoTahun);
        }

        if (empty($filtered)) {
            // Cek apakah ini query tahun yang tidak ada datanya
            if (!empty($criteria['tahun']) && !in_array($criteria['tahun'], ['2025', '2026', '2027'])) {
                return $this->finalizeResponse("Mohon maaf, ScholarBot hanya menyediakan data beasiswa tahun **2025**, **2026**, dan **2027**. Silakan coba cari dengan tahun tersebut ya! 😊", $normalizedData);
            }
            // Fallback untuk nama beasiswa/tempat yang tidak masuk akal
            if (!empty($criteria['negara'])) {
                $negaraList = implode(', ', array_map('ucwords', $criteria['negara']));
                return $this->finalizeResponse("Mohon maaf, saat ini ScholarBot belum memiliki data beasiswa untuk **$negaraList**. Coba cari beasiswa di negara lain ya! 😊", $normalizedData);
            }
            if (!empty($criteria['bulan'])) {
                $bulanList = implode(', ', array_map('ucfirst', $criteria['bulan']));
                return $this->finalizeResponse("Mohon maaf, tidak ditemukan beasiswa dengan deadline bulan **$bulanList** sesuai kriteria tersebut. Coba cari dengan kriteria lain ya! 😊", $normalizedData);
            }
            return $this->finalizeResponse("Mohon maaf, ScholarBot tidak menemukan beasiswa yang sesuai dengan pencarian tersebut. ScholarBot hanya menyediakan data beasiswa tahun **2025**, **2026**, dan **2027**. Coba ubah kriteria pencarianmu ya! 😊", $normalizedData);
        }

        // Sort by deadline jika diminta
        if (!empty($criteria['sort_deadline'])) {
            usort($filtered, function($a, $b) {
                $a = (array)$a;
                $b = (array)$b;
                $dateA = $a['deadline'] ?? '';
                $dateB = $b['deadline'] ?? '';
                try {
                    $tA = !empty($dateA) && $dateA !== '-' ? \Carbon\Carbon::parse($dateA)->timestamp : PHP_INT_MAX;
                    $tB = !empty($dateB) && $dateB !== '-' ? \Carbon\Carbon::parse($dateB)->timestamp : PHP_INT_MAX;
                    return $tA - $tB;
                } catch (\Exception $e) {
                    return 0;
                }
            });
        }

        if (!empty($criteria['benua']) && empty($criteria['negara'])) {
            shuffle($filtered);
        }

        $limitedResults = array_slice($filtered, 0, 5);
        
        session()->put('last_search_all_results', $filtered);
        session()->put('last_search_criteria', $criteria);
        session()->put('last_search_page', 1);
        session()->put('last_search_results', $limitedResults);
        session()->forget('selected_scholarship');

        $locContext = $this->getLocContext($criteria);
        $count = count($filtered);
        $isQuantification = $this->isQuantificationQuery($message);

        if ($isQuantification) {
            if (preg_match('/\b(hanya|cuma|doang)\b/i', $message)) {
                $resp = "Iya, benar. Saat ini data yang saya miliki untuk beasiswa{$locContext} memang hanya **$count** saja. 😊\n\n";
            } else {
                $resp = "Total beasiswa yang ditemukan{$locContext} adalah **$count** beasiswa.\n\n";
            }
            
            if ($count > 0) {
                $resp .= "Berikut rinciannya:\n\n";
            }
        } else {
            $headerParts = [];
            if (!empty($criteria['bidang'])) $headerParts[] = "jurusan " . implode(', ', array_map('ucwords', $criteria['bidang']));
            if (!empty($criteria['jenjang'])) $headerParts[] = "jenjang " . implode('/', $criteria['jenjang']);
            
            if (!empty($criteria['negara'])) $headerParts[] = "di " . implode(', ', array_map('ucwords', $criteria['negara']));
            elseif (!empty($criteria['benua'])) $headerParts[] = "di wilayah " . ucwords($criteria['benua'][0]);
            elseif (!empty($criteria['lokasi_tipe'])) {
                $headerParts[] = ($criteria['lokasi_tipe'] === 'luar') ? "khusus di Luar Negeri" : "khusus di Dalam Negeri";
            }
            if (!empty($criteria['funding'])) $headerParts[] = "kategori " . $criteria['funding'];
            if (!empty($criteria['tahun'])) $headerParts[] = "tahun " . $criteria['tahun'];
            if (!empty($criteria['bulan'])) $headerParts[] = "deadline bulan " . ucfirst($criteria['bulan'][0]);

            if (!empty($criteria['sort_deadline'])) {
                $resp = "Berikut beasiswa dengan **deadline paling dekat**:\n\n";
            } else {
                $resp = "Berikut beberapa beasiswa" . (empty($headerParts) ? "" : " " . implode(', ', $headerParts)) . ":\n\n";
            }
        }

        $showDeadline = !empty($criteria['bulan']) || !empty($criteria['sort_deadline']);
        foreach ($limitedResults as $i => $s) {
            $s = (array) $s;
            $namaBeasiswa = trim($s['nama_beasiswa']);
            if ($showDeadline && !empty($s['deadline']) && $s['deadline'] !== '-') {
                $deadlineFormatted = $this->formatDeadline($s['deadline']);
                $resp .= ($i + 1) . "\. **{$namaBeasiswa}** - Deadline: " . $deadlineFormatted . "\n\n";
            } else {
                $resp .= ($i + 1) . "\. **{$namaBeasiswa}** - " . ($s['negara'] ?? 'Luar Negeri') . " (" . ($s['jenjang'] ?? '-') . ")\n\n";
            }
        }

        if (count($filtered) > 5) {
            $resp .= "Masih ada beberapa beasiswa lainnya. Ketik **'yang lain'** untuk melihat selanjutnya, atau silakan pilih nomor beasiswa untuk melihat detail.";
        } elseif ($count > 0) {
            $resp .= "Silakan pilih nomor beasiswa untuk melihat detail seperti benefit, syarat, deadline, atau cara daftar.";
        }
        
        return $this->finalizeResponse($resp, $normalizedData);
    }

    private function extractCriteria($text)
    {
        $c = [
            'negara' => [], 
            'benua' => [], 
            'jenjang' => [], 
            'bulan' => [], 
            'funding' => null, 
            'negara_ori' => null, 
            'bidang' => [],
            'lokasi_tipe' => null // 'luar' atau 'dalam'
        ];
        
        // 1. Deteksi Jurusan/Bidang Studi
        $majors = [
            'matematika', 'statistika', 'fisika', 'kimia', 'biologi', 'kedokteran', 'farmasi',
            'teknik', 'arsitektur', 'komputer', 'informatika', 'hukum', 'ekonomi', 'akuntansi',
            'manajemen', 'bisnis', 'psikologi', 'pertanian', 'kehutanan', 'perikanan', 'peternakan',
            'seni', 'desain', 'komunikasi', 'sastra', 'pendidikan', 'politik', 'hubungan internasional',
            'geografi', 'lingkungan', 'sejarah', 'filsafat', 'sosiologi', 'arkeologi', 'astronomi',
            'teknologi', 'sains', 'it', 'ilmiah', 'psikiatri', 'keperawatan', 'kebidanan', 'gizi'
        ];
        foreach ($majors as $m) {
            if (preg_match('/\b' . preg_quote($m, '/') . '\b/i', $text)) {
                $c['bidang'][] = $m;
            }
        }

        // 2. Sinonim Manual Negara (Paling Prioritas)
        $syns = [
            'amerika' => 'amerika serikat',
            'usa' => 'amerika serikat',
            'as' => 'amerika serikat',
            'swiss' => 'swiss', 
            'jepang' => 'jepang', 
            'belgia' => 'belgia', 
            'belanda' => 'belanda', 
            'inggris' => 'inggris',
            'uk' => 'inggris',
            'saudi arabia' => 'arab saudi',
            'korea' => 'korea',
            'korea selatan' => 'korea selatan',
            'turki' => 'turki',
            'turkey' => 'turki'
        ];

        foreach ($syns as $key => $target) {
            if (preg_match('/\b' . preg_quote($key, '/') . '\b/i', $text)) {
                // Khusus 'amerika', jangan masukkan ke filter negara jika user menyebut 'benua'
                if ($key === 'amerika' && preg_match('/\bbenua\s+amerik[a-z]*\b/i', $text)) {
                    continue;
                }
                $c['negara'][] = $target;
                if (empty($c['negara_ori'])) $c['negara_ori'] = ucwords($key);
            }
        }

        // 2. Deteksi Negara dari Database (Tambahan jika belum ada atau untuk deteksi lebih luas)
        $allCountriesRaw = DB::table('scholarships')->distinct()->whereNotNull('negara')->pluck('negara')->toArray();
        $allCountries = [];
        foreach ($allCountriesRaw as $raw) {
            $parts = explode(',', str_replace(['luar negeri (', 'dalam negeri (', ')'], '', strtolower($raw)));
            foreach ($parts as $p) {
                $p = trim($p);
                if (!empty($p)) $allCountries[] = $p;
            }
        }
        $allCountries = array_unique($allCountries);
        
        // Sortir negara berdasarkan panjang (terpanjang dulu) agar tidak salah deteksi (misal: "India" vs "Indonesia")
        usort($allCountries, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($allCountries as $country) {
            if (preg_match('/\b' . preg_quote($country, '/') . '\b/i', $text)) {
                $c['negara'][] = $country;
                if (empty($c['negara_ori'])) $c['negara_ori'] = ucwords($country);
            }
        }
        $c['negara'] = array_unique($c['negara']);

        // 3. Deteksi Benua
        $continents = ['eropa', 'asia', 'australia', 'afrika', 'amerika'];
        foreach ($continents as $con) {
            // Gunakan Regex dengan word boundary agar "beasiswa" tidak terdeteksi sebagai "asia"
            // Mendukung pencarian "Amerika" dengan typo atau bahasa Inggris (America)
            // Mendukung pencarian "Australia" dengan Oseania
            if ($con === 'amerika') {
                $pattern = '/\b(amerik|americ)[a-z]*\b/i';
            } elseif ($con === 'australia') {
                $pattern = '/\b(australia|oseania|oceania)\b/i';
            } else {
                $pattern = '/\b' . preg_quote($con, '/') . '\b/i';
            }
            
            if (preg_match($pattern, $text)) {
                $c['benua'][] = $con;
            }
        }

        // 4. Deteksi Tipe Lokasi (Luar/Dalam Negeri)
        $lowerText = strtolower($text);
        if (preg_match('/\b(luar\s*negeri|international|abroad|luar\s+negeri)\b/i', $lowerText)) {
            $c['lokasi_tipe'] = 'luar';
        } elseif (preg_match('/\b(dalam\s*negeri|domestic|local|indonesia|indo)\b/i', $lowerText)) {
            $c['lokasi_tipe'] = 'dalam';
        }
        // Fallback: cek teks mentah (rawText) jika normalisasi mengubah kata
        if ($c['lokasi_tipe'] === null) {
            $rawLower = strtolower($text);
            if (strpos($rawLower, 'dalam negeri') !== false || strpos($rawLower, 'dalam negri') !== false) {
                $c['lokasi_tipe'] = 'dalam';
            } elseif (strpos($rawLower, 'luar negeri') !== false || strpos($rawLower, 'luar negri') !== false) {
                $c['lokasi_tipe'] = 'luar';
            }
        }

        // 5. Deteksi Jenjang, Bulan, dan Funding
        $jenjangMap = [
            's1' => ['s1', 'sarjana', 'strata 1', 'strata1', 'undergraduate', 'bachelor'],
            's2' => ['s2', 'magister', 'strata 2', 'strata2', 'master', 'pascasarjana'],
            's3' => ['s3', 'doktor', 'strata 3', 'strata3', 'phd', 'doctoral'],
            'd3' => ['d3', 'diploma 3', 'diploma3', 'diploma tiga'],
            'd4' => ['d4', 'diploma 4', 'diploma4', 'diploma empat', 'sarjana terapan'],
        ];
        foreach ($jenjangMap as $jenjang => $variants) {
            foreach ($variants as $v) {
                if (preg_match('/\b' . preg_quote($v, '/') . '\b/i', $text)) {
                    $c['jenjang'][] = strtoupper($jenjang);
                    break;
                }
            }
        }
        $c['jenjang'] = array_unique($c['jenjang']);
        $months = [
            'januari' => ['januari', 'jan'],
            'februari' => ['februari', 'pebruari', 'febuari', 'pebuari', 'feb', 'peb'],
            'maret' => ['maret'],
            'april' => ['april', 'apr'],
            'mei' => ['mei'],
            'juni' => ['juni', 'jun'],
            'juli' => ['juli', 'jul'],
            'agustus' => ['agustus', 'agu', 'agt'],
            'september' => ['september', 'sep'],
            'oktober' => ['oktober', 'okt'],
            'november' => ['november', 'nopember', 'nov'],
            'desember' => ['desember', 'des']
        ];
        foreach ($months as $m_key => $variants) {
            foreach ($variants as $v) {
                if (str_contains($text, $v)) {
                    $c['bulan'][] = $m_key;
                    break;
                }
            }
} if (str_contains($text, 'fully funded')) {
            $c['funding'] = 'Fully Funded';
        } elseif (str_contains($text, 'partially funded')) {
            $c['funding'] = 'Partially Funded';
        } elseif (preg_match('/\b(gratis|free|tanpa\s+biaya|biaya\s+gratis|full\s+gratis|gratis\s+full|gratis\s+penuh|gratis\s+total|cover\s+semua|semua\s+biaya|full\s+cover)\b/i', $text)) {
            $c['funding'] = 'Fully Funded';
        }

        // Filter tambahan edge case
        if (preg_match('/\b(tanpa|tidak|no|without|bebas)\s+(toefl|ielts|bahasa inggris|english)\b/i', $text)) {
            $c['tanpa_toefl'] = true;
        }
        if (preg_match('/\b(perempuan|wanita|putri|female|women|khusus\s+perempuan|khusus\s+wanita)\b/i', $text)) {
            $c['gender'] = 'perempuan';
        }
        if (preg_match('/\b(fresh\s*graduate|baru\s+lulus|lulusan\s+baru|tanpa\s+pengalaman)\b/i', $text)) {
            $c['fresh_graduate'] = true;
        }
        // Deteksi sorting deadline terdekat
        if (preg_match('/\b(deadline\s+terdekat|deadline\s+paling\s+dekat|paling\s+dekat\s+deadline|paling\s+dekat|segera\s+tutup|hampir\s+tutup|deadline\s+dekat|yang\s+dekat\s+deadline)\b/i', $text)) {
            $c['sort_deadline'] = true;
        }
        // Deteksi filter tahun
        if (preg_match('/\b(tahun\s+)?(20[2-3][0-9])\b/i', $text, $yearMatch)) {
            $c['tahun'] = $yearMatch[2];
        }
        // Deteksi "masih buka" / "belum tutup"
        if (preg_match('/\b(masih\s+buka|masih\s+dibuka|belum\s+tutup|yang\s+buka)\b/i', $text)) {
            $c['masih_buka'] = true;
        }

        return $c;
    }
    private function applyStrictFilters($results, $criteria)
    {
        $filtered = array_filter($results, function($r) use ($criteria) {
            $r = (array)$r;
            $rowNegara = strtolower($r['negara'] ?? '');

            // Filter Tipe Lokasi (Luar/Dalam Negeri) - MAXIMUM STRICTION
            if (!empty($criteria['lokasi_tipe'])) {
                // Indonesia dianggap dalam negeri jika mengandung kata "indonesia" atau "dalam negeri"
                $isIndo = str_contains($rowNegara, 'indonesia') || str_contains($rowNegara, 'dalam negeri');
                
                if ($criteria['lokasi_tipe'] === 'luar') {
                    // JIKA USER MINTA LUAR NEGERI, HARAM ADA INDONESIA
                    if ($isIndo) return false;
                } elseif ($criteria['lokasi_tipe'] === 'dalam') {
                    // JIKA USER MINTA DALAM NEGERI, HARUS ADA INDONESIA
                    if (!$isIndo) return false;
                }
            }

            if (!empty($criteria['negara'])) {
                $m = false;
                $rowNegara = strtolower($r['negara'] ?? '');
                foreach ($criteria['negara'] as $c) {
                    if (preg_match('/\b' . preg_quote($c, '/') . '\b/i', $rowNegara)) {
                        $m = true;
                        break;
                    }
                }
                if (!$m) return false;
            }
            if (!empty($criteria['benua'])) {
                $m = false;
                $rowBenua = strtolower($r['benua'] ?? '');
                foreach ($criteria['benua'] as $c) {
                    // Cek keberadaan benua dengan regex agar lebih presisi
                    // Mendukung padanan bahasa Inggris di database (e.g. amerika -> america)
                    $searchTerms = [$c];
                    if ($c === 'amerika') $searchTerms[] = 'america';
                    if ($c === 'eropa') $searchTerms[] = 'europe';
                    if ($c === 'australia') $searchTerms[] = 'oceania';

                    foreach ($searchTerms as $term) {
                        if (preg_match('/\b' . preg_quote($term, '/') . '\b/i', $rowBenua)) {
                            $m = true;
                            break 2;
                        }
                    }
                }
                if (!$m) return false;
            }
            if (!empty($criteria['jenjang'])) {
                $m = false;
                foreach ($criteria['jenjang'] as $l) {
                    if (str_contains(strtoupper($r['jenjang'] ?? ''), $l)) {
                        $m = true;
                        break;
                    }
                }
                if (!$m) return false;
            }
            if (!empty($criteria['bulan'])) {
                $m = false;
                $monthsConfig = [
                    'januari' => ['januari', 'jan'],
                    'februari' => ['februari', 'pebruari', 'febuari', 'pebuari', 'feb', 'peb'],
                    'maret' => ['maret'],
                    'april' => ['april', 'apr'],
                    'mei' => ['mei'],
                    'juni' => ['juni', 'jun'],
                    'juli' => ['juli', 'jul'],
                    'agustus' => ['agustus', 'agu', 'agt'],
                    'september' => ['september', 'sep'],
                    'oktober' => ['oktober', 'okt'],
                    'november' => ['november', 'nopember', 'nov'],
                    'desember' => ['desember', 'des']
                ];
                
                foreach ($criteria['bulan'] as $m_key) {
                    $rowDeadline = strtolower($r['deadline'] ?? '');
                    $variants = $monthsConfig[$m_key] ?? [$m_key];
                    foreach ($variants as $v) {
                        if (str_contains($rowDeadline, $v)) {
                            $m = true;
                            break 2;
                        }
                    }
                }
                if (!$m) return false;
            }
            if (!empty($criteria['funding'])) {
                $target = strtolower($criteria['funding']); // "fully funded" atau "partially funded"
                $actual = strtolower($r['kategori'] ?? '');
                
                // Cek apakah kategori mengandung kata kunci funding (mentoleransi typo seperti Partiallyly)
                if ($target === 'partially funded') {
                    if (!str_contains($actual, 'partially')) return false;
                } else {
                    if ($actual !== $target) return false;
                }
            }
            // Filter tanpa TOEFL
            if (!empty($criteria['tanpa_toefl'])) {
                $syarat = strtolower($r['persyaratan'] ?? '');
                $deskripsi = strtolower($r['deskripsi'] ?? '');
                $allText = $syarat . ' ' . $deskripsi;
                if (preg_match('/\b(toefl|ielts)\s*(score|skor|minimum|min|required|wajib|dibutuhkan)\b/i', $allText)) {
                    return false;
                }
            }

            // Filter khusus perempuan
            if (!empty($criteria['gender']) && $criteria['gender'] === 'perempuan') {
                $syarat = strtolower($r['persyaratan'] ?? '');
                $deskripsi = strtolower($r['deskripsi'] ?? '');
                $nama = strtolower($r['nama_beasiswa'] ?? '');
                $allText = $syarat . ' ' . $deskripsi . ' ' . $nama;
                if (!preg_match('/\b(perempuan|wanita|putri|female|women|girl)\b/i', $allText)) {
                    return false;
                }
            }

            // Filter fresh graduate
            if (!empty($criteria['fresh_graduate'])) {
                $syarat = strtolower($r['persyaratan'] ?? '');
                $deskripsi = strtolower($r['deskripsi'] ?? '');
                $allText = $syarat . ' ' . $deskripsi;
                if (!preg_match('/\b(fresh\s*graduate|baru\s+lulus|lulusan\s+baru|0[-\s]?[12]\s*tahun|tanpa\s+pengalaman)\b/i', $allText)) {
                    return false;
                }
            }

            // Filter tahun di deadline
            if (!empty($criteria['tahun'])) {
                $deadline = strtolower($r['deadline'] ?? '');
                if (!str_contains($deadline, $criteria['tahun'])) return false;
            }

            // Filter beasiswa yang masih buka (deadline >= hari ini)
            if (!empty($criteria['masih_buka'])) {
                $deadline = $r['deadline'] ?? '';
                if (!empty($deadline) && $deadline !== '-') {
                    // Coba parse tanggal, skip jika gagal parse
                    try {
                        $deadlineDate = \Carbon\Carbon::parse($deadline);
                        if ($deadlineDate->isPast()) return false;
                    } catch (\Exception $e) {
                        // Jika tidak bisa diparse, tetap tampilkan
                    }
                }
            }

            return true;
        });
        return array_values($filtered);
    }
    private function generateEmbedding($text)
    {
        $apiKey = trim(env('OPENAI_API_KEY'));
        if (!$apiKey) throw new \Exception("OpenAI API Key is missing.");

        if (str_contains($apiKey, 'sk-or')) {
            $baseUrl = 'https://openrouter.ai/api/v1';
            $model = 'openai/text-embedding-3-small';
        } elseif (str_starts_with($apiKey, 'AIza')) {
            // Google Gemini Embedding API
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
            $response = Http::post("$baseUrl/models/text-embedding-004:embedContent?key=$apiKey", [
                'content' => ['parts' => [['text' => $text]]]
            ]);
            if ($response->successful()) return $response->json()['embedding']['values'];
            throw new \Exception("Gagal koneksi ke Gemini Embedding.");
        } else {
            $baseUrl = 'https://api.openai.com/v1';
            $model = 'text-embedding-3-small';
        }
        
        $response = Http::withToken($apiKey)
            ->withoutVerifying()
            ->connectTimeout(30)
            ->timeout(120) 
            ->retry(3, 1000)
            ->post("$baseUrl/embeddings", [
                'model' => 'openai/text-embedding-3-small',
                'input' => $text,
            ]);

        if ($response->failed()) {
            Log::error("Embedding API Error: " . $response->body());
            throw new \Exception("Gagal koneksi ke server AI (HTTP " . $response->status() . ").");
        }

        $data = $response->json();
        if (!isset($data['data'][0]['embedding'])) {
            Log::error("Embedding API Invalid Response: " . json_encode($data));
            throw new \Exception("Format respon AI tidak valid.");
        }

        return $data['data'][0]['embedding'];
    }

    /**
     * Menerjemahkan teks ke Bahasa Indonesia menggunakan AI (OpenRouter)
     */
    private function translateToIndonesian($text)
    {
        if (empty($text) || $text === '-' || strlen($text) < 10) return $text;

        // Cek apakah teks mengandung banyak kata bahasa Inggris (deteksi lebih luas)
        $englishKeywords = ['scholarship', 'requirements', 'eligibility', 'benefits', 'citizenship', 'degree', 'deadline', 'tuition', 'award', 'allowance', 'internship', 'public', 'service', 'fees', 'maintenance', 'the', 'and', 'of', 'for', 'with'];
        $isEnglish = false;
        foreach ($englishKeywords as $kw) {
            if (str_contains(strtolower($text), $kw)) {
                $isEnglish = true;
                break;
            }
        }

        // Jika tidak terdeteksi bahasa Inggris, kirim apa adanya (hemat kuota)
        if (!$isEnglish) return $text;

        try {
            $apiKey = trim(env('OPENAI_API_KEY'));
            $baseUrl = str_contains($apiKey, 'sk-or') ? 'https://openrouter.ai/api/v1' : 'https://api.openai.com/v1';
            
            // Gunakan model yang murah/cepat untuk translasi
            $model = str_contains($apiKey, 'sk-or') ? 'google/gemini-2.0-flash-lite-001' : 'gpt-3.5-turbo';

            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post("$baseUrl/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => 'Anda adalah asisten informasi beasiswa yang profesional. Tugas Anda adalah menerjemahkan (jika teks berbahasa Inggris) dan merapikan teks informasi beasiswa berikut agar sangat mudah dibaca. 
                            Gunakan aturan berikut:
                            1. Gunakan Bahasa Indonesia yang formal dan sopan.
                            2. Gunakan Bold (teks tebal) untuk kategori atau judul kecil.
                            3. Gunakan list/bullet points untuk poin-poin informasi.
                            4. Berikan jarak antar kategori agar tidak menumpuk.
                            5. Hapus bagian yang tidak perlu jika ada pengulangan.
                            Berikan hasil akhirnya saja tanpa komentar tambahan.'
                        ],
                        ['role' => 'user', 'content' => $text]
                    ],
                    'temperature' => 0.3
                ]);

            if ($response->successful()) {
                $result = $response->json()['choices'][0]['message']['content'];
                return trim($result) . "\n\n*(Terjemahan Otomatis)*";
            }
        } catch (\Exception $e) {
            Log::error("Translation Error: " . $e->getMessage());
        }

        return $text; // Balikkan teks asli jika gagal
    }
    /**
     * Menangani pertanyaan langsung ke AI tanpa bantuan Database (Pure AI Mode)
     */
    private function handlePureAI($message, $ragEnabled = false, $normalizedData = null, $forceContext = false)
    {
        try {
            $apiKey = trim(env('OPENAI_API_KEY'));
            
            if (empty($apiKey)) {
                return $this->finalizeResponse("Maaf, konfigurasi API Key tidak ditemukan. Silakan hubungi admin.", $normalizedData, false);
            }

            if (str_contains($apiKey, 'sk-or')) {
                $baseUrl = 'https://openrouter.ai/api/v1';
                $model = 'google/gemini-2.0-flash-lite-001';
            } elseif (str_starts_with($apiKey, 'AIza')) {
                // Google Gemini Direct API
                $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/openai';
                $model = 'gemini-1.5-flash';
            } else {
                $baseUrl = 'https://api.openai.com/v1';
                $model = 'gpt-3.5-turbo';
            }

            $systemPrompt = 'Anda adalah ScholarBot, asisten AI informasi beasiswa. ';
            
            if ($forceContext) {
                $systemPrompt .= 'Anda adalah ahli beasiswa. Berikan jawaban yang sangat detail dan akurat mengenai beasiswa yang ditanyakan oleh user. Gunakan pengetahuan luas Anda karena data di database kami sedang tidak lengkap.';
            } elseif ($ragEnabled) {
                $systemPrompt .= 'Saat ini Anda beroperasi dalam MODE RAG. 
                Tugas Anda:
                1. Berikan informasi beasiswa berdasarkan Context Beasiswa yang diberikan di bawah.
                2. Jika informasi tidak ada di context, gunakan pengetahuan luas Anda untuk menjawab asalkan tetap dalam TOPIK BEASISWA.
                3. JIKA pertanyaan user di luar topik beasiswa, pendidikan, atau universitas (misal: tanya nama, lokasi umum, hobi, dsb), Anda WAJIB menolak dengan kalimat persis seperti ini:
                   "Mohon maaf, chatbot kami tidak menerima pertanyaan diluar informasi beasiswa, jika ingin bertanya hal tersebut bisa anda off kan toggle diatas dan silahkan ulangi pertanyaannya."';
                
                // Tambahkan context beasiswa ke prompt
                $context = $this->getScholarshipContext($message);
                $systemPrompt .= "\n\nContext Beasiswa dari Dataset:\n" . $context;
            } else {
                $systemPrompt .= 'Saat ini Anda beroperasi dalam MODE STANDAR (General AI). 
                Jawablah pertanyaan user secara bebas dan ramah tentang TOPIK APAPUN. 
                Anda tidak perlu membatasi diri pada beasiswa karena Mode RAG sedang dimatikan.';
            }

            $response = Http::withToken($apiKey)
                ->withoutVerifying()
                ->timeout(60)
                ->post("$baseUrl/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => $systemPrompt
                        ],
                        ['role' => 'user', 'content' => $message]
                    ],
                    'temperature' => 0.7
                ]);

            if ($response->successful()) {
                $ans = $response->json()['choices'][0]['message']['content'];
                
                // Tambahkan label mode untuk transparansi
                if ($ragEnabled) {
                    $ans .= "\n\n*(Mode: RAG - Dataset Context)*";
                } else {
                    $ans .= "\n\n*(Mode: Pure AI - Tanpa Dataset)*";
                }

                return $this->finalizeResponse($ans, $normalizedData);
            } else {
                Log::error("Gemini API Error: " . $response->body());
                return $this->finalizeResponse("Maaf, terjadi gangguan pada server AI (Gemini). Alasan: " . ($response->json()['error']['message'] ?? 'Unknown Error'), $normalizedData, false);
            }

        } catch (\Exception $e) {
            Log::error("Pure AI Error: " . $e->getMessage());
            return $this->finalizeResponse("Terjadi kesalahan saat menghubungi server AI. Silakan periksa koneksi atau API Key Anda.", $normalizedData, false);
        }
    }

    private function handleMajorValidation($major, $normalizedData, $ref = null)
    {
        $major = strtolower(trim($major));
        $ref = strtolower(trim($ref ?? ''));

        // 1. JIKA ADA REFERENSI NOMOR SPESIFIK (Misal: "no 2 ada jurusan X?")
        if (preg_match('/\b(?:no|nomor|#|ke)\s*([0-9]+)\b/i', $ref, $refMatches)) {
            $num = (int)$refMatches[1];
            $results = session()->get('last_search_all_results', []);
            if (isset($results[$num - 1])) {
                $s = (array)$results[$num - 1];
                $bidang = strtolower($s['bidang'] ?? '');
                $persyaratan = strtolower($s['persyaratan'] ?? '');
                $benefit = strtolower($s['benefit'] ?? '');
                $nama = $s['nama_beasiswa'];
                
                if (str_contains($bidang, $major) || str_contains($persyaratan, $major) || str_contains($benefit, $major)) {
                    return $this->finalizeResponse("Iya, beasiswa nomor $num (**$nama**) tersedia untuk jurusan **" . ucwords($major) . "**. 😊\n\nSilakan pilih nomor $num untuk melihat detail lengkapnya.", $normalizedData);
                } else {
                    return $this->finalizeResponse("Mohon maaf, beasiswa nomor $num (**$nama**) sepertinya tidak tersedia untuk jurusan **" . ucwords($major) . "**. 😊", $normalizedData);
                }
            }
        }

        // 2. JIKA ADA BEASISWA YANG SEDANG DIPILIH (Dan bukan nanya nomor lain)
        if (session()->has('selected_scholarship') && ($ref === 'ini' || $ref === 'itu' || $ref === 'tersebut' || $ref === '')) {
            $s = session()->get('selected_scholarship');
            $bidang = strtolower($s['bidang'] ?? '');
            $persyaratan = strtolower($s['persyaratan'] ?? '');
            $benefit = strtolower($s['benefit'] ?? '');
            $nama = $s['nama_beasiswa'];
            
            // Cek di kolom bidang, persyaratan, atau benefit
            if (str_contains($bidang, $major) || str_contains($persyaratan, $major) || str_contains($benefit, $major)) {
                return $this->finalizeResponse("Iya, beasiswa **$nama** tersedia untuk jurusan **" . ucwords($major) . "**. 😊\n\nApa lagi yang ingin Anda ketahui? (Ketik: **Benefit**, **Syarat**, **Deadline**, atau **Cara Daftar**)", $normalizedData);
            } else {
                return $this->finalizeResponse("Mohon maaf, sepertinya beasiswa **$nama** tidak secara spesifik menyebutkan ketersediaan untuk jurusan **" . ucwords($major) . "**. Namun Anda bisa mencoba mengecek detail syarat lengkapnya dengan mengetik **'Syarat'** atau mencari beasiswa lain. 😊", $normalizedData);
            }
        }

        // 2. JIKA TIDAK ADA YANG DIPILIH, CEK LIST TERAKHIR (Validasi nomor)
        $results = session()->get('last_search_results', []);
        $foundIn = [];

        foreach ($results as $i => $s) {
            $s = (array)$s;
            $bidang = strtolower($s['bidang'] ?? '');
            $persyaratan = strtolower($s['persyaratan'] ?? '');
            $benefit = strtolower($s['benefit'] ?? '');
            
            if (str_contains($bidang, $major) || str_contains($persyaratan, $major) || str_contains($benefit, $major)) {
                $foundIn[] = ($i + 1);
            }
        }

        if (empty($foundIn)) {
            return $this->finalizeResponse("Mohon maaf, sepertinya dari daftar beasiswa di atas tidak tersedia jurusan **" . ucwords($major) . "**. Namun Anda bisa mencoba mencari beasiswa lain dengan mengetik 'beasiswa jurusan " . $major . "' secara global. 😊", $normalizedData);
        }

        $numbers = implode(', ', $foundIn);
        if (count($foundIn) > 1) {
            $lastComma = strrpos($numbers, ',');
            if ($lastComma !== false) {
                $numbers = substr_replace($numbers, ' dan', $lastComma, 1);
            }
        }

        return $this->finalizeResponse("Iya! Dari daftar beasiswa di atas, jurusan **" . ucwords($major) . "** tersedia di beasiswa nomor **" . $numbers . "**. \n\nSilakan pilih nomor beasiswa tersebut untuk melihat detail syarat, benefit, dan cara daftarnya ya! 😊", $normalizedData);
    }

    private function handleFundingValidation($fundingType, $normalizedData, $ref = null)
    {
        $ref = strtolower(trim($ref ?? ''));
        $target = strtolower($fundingType);
        
        // Normalisasi tipe pendanaan
        if (str_contains($target, 'full') || str_contains($target, 'penuh')) {
            $targetType = 'fully funded';
            $displayType = 'Fully Funded';
        } else {
            $targetType = 'partially funded';
            $displayType = 'Partially Funded';
        }

        // 1. CEK REFERENSI NOMOR
        if (preg_match('/\b(?:no|nomor|#|ke)\s*([0-9]+)\b/i', $ref, $refMatches)) {
            $num = (int)$refMatches[1];
            $results = session()->get('last_search_all_results', []);
            if (isset($results[$num - 1])) {
                $s = (array)$results[$num - 1];
                $actual = strtolower($s['kategori'] ?? '');
                $nama = $s['nama_beasiswa'];
                
                if (str_contains($actual, $targetType) || ($targetType === 'fully funded' && str_contains($actual, 'penuh'))) {
                    return $this->finalizeResponse("Iya, beasiswa nomor $num (**$nama**) adalah beasiswa **$displayType**. 😊", $normalizedData);
                } else {
                    $actualDisplay = str_contains($actual, 'fully') ? 'Fully Funded' : (str_contains($actual, 'partially') ? 'Partially Funded' : ucwords($actual));
                    return $this->finalizeResponse("Bukan, beasiswa nomor $num (**$nama**) kategorinya adalah **$actualDisplay**, bukan $displayType. 😊", $normalizedData);
                }
            }
        }

        // 2. CEK SELECTED SCHOLARSHIP (Konteks "ini/itu")
        if (session()->has('selected_scholarship') && ($ref === 'ini' || $ref === 'itu' || $ref === 'tersebut' || $ref === '')) {
            $s = session()->get('selected_scholarship');
            $actual = strtolower($s['kategori'] ?? '');
            $nama = $s['nama_beasiswa'];
            
            if (str_contains($actual, $targetType) || ($targetType === 'fully funded' && str_contains($actual, 'penuh'))) {
                return $this->finalizeResponse("Iya, beasiswa **$nama** ini adalah beasiswa **$displayType**. 😊", $normalizedData);
            } else {
                $actualDisplay = str_contains($actual, 'fully') ? 'Fully Funded' : (str_contains($actual, 'partially') ? 'Partially Funded' : ucwords($actual));
                return $this->finalizeResponse("Bukan, beasiswa **$nama** ini kategorinya adalah **$actualDisplay**. 😊", $normalizedData);
            }
        }
        
        return $this->finalizeResponse("Maaf, saya tidak yakin beasiswa mana yang Anda maksud untuk pertanyaan pendanaan tersebut. Silakan pilih nomor beasiswa terlebih dahulu atau tanyakan spesifik seperti 'beasiswa no 1 fully funded ngga?'.", $normalizedData);
    }
    private function getOutOfTopicResponse()
    {
        $responses = [
            "Mohon maaf, saat ini chatbot ini hanya memberikan informasi seputar **beasiswa**. Jika ingin bertanya di luar topik tersebut, silakan nonaktifkan toggle RAG di atas ya. Terima kasih! 🙏",
            "Waduh maaf ya, saya hanya bisa membantu menjawab pertanyaan terkait **info beasiswa**. Untuk pertanyaan umum lainnya, bisa matikan Mode RAG di atas. Terima kasih banyak! 🙏✨",
            "Mohon maaf sekali, pertanyaan Anda di luar topik beasiswa. ScholarBot fokus pada bantuan biaya studi dan universitas. Jika ingin bertanya hal lain, silakan off-kan toggle di atas. Terima kasih! 🙏😊",
            "Maaf ya, karena Mode RAG aktif, saya hanya bisa menjawab seputar **beasiswa**. Silakan matikan Mode RAG jika ingin bertanya hal umum lainnya. Terima kasih! 🙏",
            "Mohon maaf, untuk saat ini saya hanya melayani tanya-jawab seputar **beasiswa**. Jika Anda ingin bertanya hal di luar itu, silakan nonaktifkan Mode RAG di atas ya. Terima kasih! 🙏✨"
        ];
        return $responses[array_rand($responses)];
    }

    private function getScholarshipContext(string $message): string
    {
        // Ambil beasiswa yang sudah dipilih dulu
        if (session()->has('selected_scholarship')) {
            $selected = session()->get('selected_scholarship');
            $nama = $selected['nama_beasiswa'] ?? '-';
            $negara = $selected['negara'] ?? '-';
            $jenjang = $selected['jenjang'] ?? '-';
            $deadline = $selected['deadline'] ?? $selected['batas_pendaftaran'] ?? '-';
            $benefit = $selected['benefit'] ?? $selected['benefits'] ?? '-';
            $syarat = $selected['persyaratan'] ?? $selected['syarat'] ?? '-';
            return "Beasiswa Terpilih:\nNama: $nama\nNegara: $negara\nJenjang: $jenjang\nDeadline: $deadline\nBenefit: $benefit\nPersyaratan: $syarat";
        }

        // Jika ada hasil pencarian terakhir, gunakan itu
        if (session()->has('last_search_results')) {
            $results = session()->get('last_search_results', []);
            $lines = [];
            foreach (array_slice($results, 0, 5) as $r) {
                $r = (array) $r;
                $lines[] = "- " . ($r['nama_beasiswa'] ?? '-') . " (" . ($r['negara'] ?? '-') . ", " . ($r['jenjang'] ?? '-') . ")";
            }
            return "Hasil Pencarian Terakhir:\n" . implode("\n", $lines);
        }

        // Fallback: ambil sample dari DB berdasarkan kata kunci di pesan
        try {
            $keyword = preg_replace('/\b(beasiswa|info|syarat|deadline|benefit|cara|daftar)\b/i', '', $message);
            $keyword = trim($keyword);
            $rows = DB::table('scholarships')
                ->where('nama_beasiswa', 'ilike', '%' . $keyword . '%')
                ->limit(3)
                ->get();
            if ($rows->isNotEmpty()) {
                $lines = [];
                foreach ($rows as $r) {
                    $lines[] = "- $r->nama_beasiswa ($r->negara, $r->jenjang)";
                }
                return "Beasiswa relevan:\n" . implode("\n", $lines);
            }
        } catch (\Exception $e) {}

        return "Tidak ada context beasiswa tersedia saat ini.";
    }

    private function formatDeadline($deadline)
    {
        if (empty($deadline) || $deadline === '-') return '-';
        try {
            $bulanIndo = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            // Coba parse tanggal
            $date = \Carbon\Carbon::parse($deadline);
            return $date->day . ' ' . $bulanIndo[$date->month] . ' ' . $date->year;
        } catch (\Exception $e) {
            // Kalau tidak bisa di-parse, kembalikan apa adanya
            return $deadline;
        }
    }

}