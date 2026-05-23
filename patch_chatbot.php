<?php
$file = 'C:/Users/sekut/SkripsiBea-dev/app/Http/Controllers/ChatbotController.php';
$c = file_get_contents($file);

// Hapus duplikat kedua — cari kemunculan kedua dari method
$pattern = '/(\n\s+private function getScholarshipContext\(string \$message\): string\s*\{.*?\n\s+\})/s';
preg_match_all($pattern, $c, $matches);

if (count($matches[0]) > 1) {
    // Hapus kemunculan kedua
    $second = $matches[0][1];
    $pos = strrpos($c, $second);
    $c = substr($c, 0, $pos) . substr($c, $pos + strlen($second));
    echo "Duplikat dihapus\n";
} else {
    echo "Tidak ada duplikat ditemukan\n";
}

file_put_contents($file, $c);
echo "✅ Selesai!\n";
