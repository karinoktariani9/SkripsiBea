content = open('app/Http/Controllers/ChatbotController.php').read()
fixes = 0

# FIX 1: Tambah sinonim lnjutin, lnjut, lanjutin, terusin ke 'yang lain'
# dan fully fund (tanpa -ed) ke 'fully funded'
old1 = "            'dana penuh' => 'fully funded', 'beasiswa penuh' => 'fully funded', 'pendanaan penuh' => 'fully funded',"
new1 = "            'dana penuh' => 'fully funded', 'beasiswa penuh' => 'fully funded', 'pendanaan penuh' => 'fully funded',\n            'fully fund' => 'fully funded', 'full funded' => 'fully funded', 'full fund' => 'fully funded',\n            'lnjutin' => 'yang lain', 'lnjut' => 'yang lain', 'lanjutin' => 'yang lain', 'terusin' => 'yang lain', 'lanjut' => 'yang lain',"

if old1 in content:
    content = content.replace(old1, new1)
    print('Fix 1 applied: synonym fully fund + lnjutin')
    fixes += 1
else:
    print('Fix 1 NOT FOUND')

# FIX 2: Tambah 'lanjut' dan 'lanjutin' ke pattern isNextPage
old2 = "$isNextPage = preg_match('/\\b(lainnya|yang lain|selanjutnya|berikutnya|lagi|next)\\b/i', $message);"
new2 = "$isNextPage = preg_match('/\\b(lainnya|yang lain|selanjutnya|berikutnya|lagi|next|lanjut|lanjutin|terusin|lnjut|lnjutin)\\b/i', $message);"

if old2 in content:
    content = content.replace(old2, new2)
    print('Fix 2 applied: isNextPage pattern expanded')
    fixes += 1
else:
    print('Fix 2 NOT FOUND')

# FIX 3: "link nomor X" / "link no X" - ambil nomor dari display number (pagination aware)
# Cek dulu apakah ada handler untuk "link nomor"
if 'link nomor' in content or 'link no' in content.lower():
    print('Fix 3: link nomor handler already exists, skipping')
else:
    # Tambah handler "link nomor X" sebelum isNextPage check
    old3 = "            // 4.5 NEXT PAGE (YANG LAIN)"
    new3 = """            // 4.4.5 LINK NOMOR X - ambil URL langsung dari hasil pencarian
            if (preg_match('/\\b(link|url|tautan)\\b.*\\b(nomor|no|nmr|#)\\s*(\\d+)/i', $message, $linkMatch) ||
                preg_match('/\\b(nomor|no|nmr|#)\\s*(\\d+).*\\b(link|url|tautan)\\b/i', $message, $linkMatch2)) {
                $displayNum = isset($linkMatch[3]) ? (int)$linkMatch[3] : (int)$linkMatch2[2];
                $allResults = session()->get('last_search_all_results', []);
                $idx = $displayNum - 1;
                if ($idx >= 0 && $idx < count($allResults)) {
                    $s = (array)$allResults[$idx];
                    $name = $s['nama_beasiswa'] ?? 'Beasiswa ini';
                    $urlAsli = $s['url_asli'] ?? null;
                    $urlSchoters = $s['url'] ?? null;
                    $urlInternal = route('scholarship.detail', ['id' => $s['id']]);
                    $ans = "Berikut link untuk **$name**:\\n\\n";
                    if ($urlAsli) $ans .= "\xf0\x9f\x8c\x90 [**Website Resmi**]($urlAsli)\\n\\n";
                    if ($urlSchoters) $ans .= "\xf0\x9f\x93\x8b [**Info di Schoters**]($urlSchoters)\\n\\n";
                    $ans .= "\xf0\x9f\x91\x89 [**Halaman Detail**]($urlInternal)";
                    return $this->finalizeResponse($ans, $normalizedData);
                }
            }

            // 4.5 NEXT PAGE (YANG LAIN)"""

    if old3 in content:
        content = content.replace(old3, new3)
        print('Fix 3 applied: link nomor X handler added')
        fixes += 1
    else:
        print('Fix 3 NOT FOUND')

if fixes > 0:
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print(f'DONE - {fixes} fixes applied')
else:
    print('No changes made')
