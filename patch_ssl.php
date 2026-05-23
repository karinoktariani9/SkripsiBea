<?php
$file = __DIR__ . '/app/Http/Controllers/ChatbotController.php';
$content = file_get_contents($file);

// Fix embeddings
$old = "Http::withToken(\$apiKey)\n            ->connectTimeout(30)";
$new = "Http::withToken(\$apiKey)\n            ->withoutVerifying()\n            ->connectTimeout(30)";
if (str_contains($content, $old)) {
    $content = str_replace($old, $new, $content);
    echo "Fix embeddings: OK\n";
} else {
    echo "Fix embeddings: SKIP (sudah ada atau pola beda)\n";
}

file_put_contents($file, $content);
echo "Selesai\n";
