<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Chatbot Accuracy & Performance Measurement
 * Mengukur: Accuracy Rate, Response Time, Intent Detection Rate
 *
 * Jalankan: php artisan test --filter=ChatbotAccuracyTest --verbose
 */
class ChatbotAccuracyTest extends TestCase
{
    /**
     * Dataset evaluasi:
     * ['query' => ..., 'expected_keywords' => [...], 'should_oot' => bool, 'label' => ...]
     */
    private array $evalDataset = [
        // Greeting
        ['query' => 'Halo',                                     'expected_keywords' => ['beasiswa'],        'should_oot' => false, 'label' => 'Greeting umum'],
        ['query' => 'Selamat pagi',                             'expected_keywords' => ['pagi'],            'should_oot' => false, 'label' => 'Greeting pagi'],
        // Pencarian
        ['query' => 'Beasiswa S1 di Jepang',                   'expected_keywords' => ['jepang','1.','1\\.'], 'should_oot' => false, 'label' => 'Search S1 Jepang'],
        ['query' => 'Beasiswa S2 luar negeri',                 'expected_keywords' => ['berikut','1.'],    'should_oot' => false, 'label' => 'Search S2 LN'],
        ['query' => 'Beasiswa fully funded luar negeri',       'expected_keywords' => ['fully funded'],    'should_oot' => false, 'label' => 'Search fully funded LN'],
        ['query' => 'Beasiswa S1 benua eropa',                 'expected_keywords' => ['eropa'],           'should_oot' => false, 'label' => 'Search benua Eropa'],
        ['query' => 'Beasiswa S1 benua asia',                  'expected_keywords' => ['asia'],            'should_oot' => false, 'label' => 'Search benua Asia'],
        ['query' => 'Cari beasiswa ke Korea',                  'expected_keywords' => ['korea','1.'],      'should_oot' => false, 'label' => 'Search Korea'],
        ['query' => 'Beasiswa jurusan informatika',            'expected_keywords' => ['berikut','1.'],    'should_oot' => false, 'label' => 'Search jurusan informatika'],
        ['query' => 'Beasiswa tahun 2026',                     'expected_keywords' => ['2026','berikut'],  'should_oot' => false, 'label' => 'Search tahun 2026'],
        ['query' => 'Aku nyari beasiswa yang gratis full',     'expected_keywords' => ['fully funded','berikut'], 'should_oot' => false, 'label' => 'Search gratis full → fully funded'],
        ['query' => 'Info scholarship luar negeri dong',       'expected_keywords' => ['berikut','1.'],    'should_oot' => false, 'label' => 'Search scholarship LN (Inggris)'],
        ['query' => 'Beasiswa yang masih buka bulan ini',      'expected_keywords' => ['berikut','tidak ditemukan'], 'should_oot' => false, 'label' => 'Search masih buka bulan ini'],
        ['query' => 'Beasiswa deadline paling dekat',          'expected_keywords' => ['deadline','berikut'], 'should_oot' => false, 'label' => 'Search deadline terdekat'],
        ['query' => 'Beasiswa tutup bulan Juni',               'expected_keywords' => ['juni','deadline'], 'should_oot' => false, 'label' => 'Search deadline bulan Juni'],
        ['query' => 'Beasiswa khusus perempuan ada?',          'expected_keywords' => ['berikut','tidak ditemukan','mohon maaf'], 'should_oot' => false, 'label' => 'Search khusus perempuan'],
        // Typo
        ['query' => 'Beasiswaa S1 Jepang',                    'expected_keywords' => ['jepang','1.'],     'should_oot' => false, 'label' => 'Typo beasiswaa'],
        ['query' => 'deadlien beasiswa jepang',               'expected_keywords' => ['deadline','jepang'], 'should_oot' => false, 'label' => 'Typo deadlien'],
        // OOT
        ['query' => 'Berikan resep nasi goreng',               'expected_keywords' => ['mohon maaf','toggle','rag'], 'should_oot' => true,  'label' => 'OOT resep'],
        ['query' => 'Ada bantuan kuliah gak?',                 'expected_keywords' => ['mohon maaf','toggle'],       'should_oot' => true,  'label' => 'OOT bantuan kuliah'],
        ['query' => 'Yang cover UKT doang ada?',              'expected_keywords' => ['mohon maaf','toggle'],       'should_oot' => true,  'label' => 'OOT cover UKT'],
        ['query' => 'Beasiswa Hogwarts ada?',                  'expected_keywords' => ['mohon maaf','tidak memiliki','tidak ditemukan'], 'should_oot' => true, 'label' => 'OOT Hogwarts'],
        ['query' => 'Beasiswa di Mars?',                       'expected_keywords' => ['mohon maaf','tidak memiliki'], 'should_oot' => true, 'label' => 'OOT Mars'],
        ['query' => 'Ada beasiswa tahun 2030?',                'expected_keywords' => ['mohon maaf','2026','2027'],  'should_oot' => true,  'label' => 'OOT tahun 2030'],
        // Kapan dibuka
        ['query' => 'Beasiswa LPDP kapan dibuka?',            'expected_keywords' => ['mohon maaf','rag','matikan','tidak ada'], 'should_oot' => true, 'label' => 'Kapan dibuka LPDP'],
        // Thank you
        ['query' => 'Makasih ya',                             'expected_keywords' => ['sama-sama','sukses','semangat'], 'should_oot' => false, 'label' => 'Thank you'],
    ];

    /** @test */
    public function test_accuracy_and_performance_report(): void
    {
        $results  = [];
        $correct  = 0;
        $total    = count($this->evalDataset);
        $times    = [];

        foreach ($this->evalDataset as $case) {
            $start = microtime(true);

            $response = $this->postJson('/chatbot/ask', [
                'message'     => $case['query'],
                'rag_enabled' => true,
            ]);

            $elapsed = round((microtime(true) - $start) * 1000, 1);
            $times[] = $elapsed;

            $data   = $response->json();
            $answer = strtolower($data['answer'] ?? '');

            // Cek apakah salah satu expected_keyword ada di jawaban
            $matched = false;
            foreach ($case['expected_keywords'] as $kw) {
                if (str_contains($answer, strtolower($kw))) {
                    $matched = true;
                    break;
                }
            }

            $status = $matched ? '✅ PASS' : '❌ FAIL';
            if ($matched) $correct++;

            $results[] = [
                'label'    => $case['label'],
                'query'    => $case['query'],
                'status'   => $status,
                'time_ms'  => $elapsed,
                'expected' => implode(' | ', $case['expected_keywords']),
                'got'      => mb_substr($answer, 0, 100) . '...',
            ];
        }

        // Hitung statistik
        $accuracy = round(($correct / $total) * 100, 1);
        $avgTime  = round(array_sum($times) / count($times), 1);
        $maxTime  = max($times);
        $minTime  = min($times);

        // Print laporan ke output test
        echo "\n\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║           SCHOLARBOT - ACCURACY & PERFORMANCE REPORT        ║\n";
        echo "╠══════════════════════════════════════════════════════════════╣\n";
        echo "║  Total Test Cases : {$total}\n";
        echo "║  Passed           : {$correct}\n";
        echo "║  Failed           : " . ($total - $correct) . "\n";
        echo "║  Accuracy Rate    : {$accuracy}%\n";
        echo "╠══════════════════════════════════════════════════════════════╣\n";
        echo "║  Avg Response Time: {$avgTime} ms\n";
        echo "║  Min Response Time: {$minTime} ms\n";
        echo "║  Max Response Time: {$maxTime} ms\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";

        echo str_pad("Label", 35) . str_pad("Status", 10) . str_pad("Time", 10) . "Expected Keywords\n";
        echo str_repeat("-", 90) . "\n";
        foreach ($results as $r) {
            echo str_pad(mb_substr($r['label'], 0, 34), 35)
               . str_pad($r['status'], 10)
               . str_pad($r['time_ms'] . "ms", 10)
               . $r['expected'] . "\n";
            if ($r['status'] === '❌ FAIL') {
                echo "  └─ Got: " . mb_substr($r['got'], 0, 80) . "\n";
            }
        }
        echo "\n";

        // Simpan ke DB jika ada tabel metrics
        try {
            DB::table('chatbot_metrics')->insert([
                'total_tests'     => $total,
                'passed'          => $correct,
                'accuracy_rate'   => $accuracy,
                'avg_response_ms' => $avgTime,
                'min_response_ms' => $minTime,
                'max_response_ms' => $maxTime,
                'created_at'      => now(),
            ]);
        } catch (\Exception $e) {
            // Tabel belum ada, skip
        }

        // Assert minimal 70% accuracy
        $this->assertGreaterThanOrEqual(70, $accuracy, "Accuracy rate di bawah 70%: {$accuracy}%");
    }
}
