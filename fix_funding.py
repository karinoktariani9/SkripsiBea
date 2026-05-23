content = open('app/Http/Controllers/ChatbotController.php').read()

old = "            // 1.7.6 VALIDASI PENDANAAN (Konteks Follow-up: Apakah ini fully funded?)\n            if ($ragEnabled && (session()->has('selected_scholarship') || session()->has('last_search_results'))) {\n                $fundingRegex = '/\\b(beasiswa\\s+)?(ini|itu|tersebut|no\\s+\\d+|nomor\\s+\\d+|ke\\s+\\d+)?\\s*(apakah|tipe)?\\s*(fully\\s+funded|full\\s+funded|partially\\s+funded|partial\\s+funded|dana\\s+penuh|dana\\s+sebagian|biaya\\s+penuh|biaya\\s+sebagian)\\b/i';\n                if (preg_match($fundingRegex, $message, $matches)) {\n                    $ref = trim($matches[2] ?? '');\n                    $fundingFound = trim($matches[4]);\n                    return $this->handleFundingValidation($fundingFound, $normalizedData, $ref);\n                }\n            }"

new = "            // 1.7.6 VALIDASI PENDANAAN (Konteks Follow-up: Apakah ini fully funded?)\n            // Skip jika pesan mengandung konteks pencarian baru (negara, jenjang, dll)\n            $isNewSearch = preg_match('/\\b(beasiswa|cari|tampilkan|kasih|ada|mau|coba|list|daftar)\\b/i', $message) ||\n                preg_match('/\\b(s1|s2|s3|d3|d4|luar negeri|dalam negeri|fully funded|partially funded)\\b/i', $message) &&\n                !preg_match('/\\b(ini|itu|tersebut|apakah|tipe)\\b/i', $message);\n            if ($ragEnabled && !$isNewSearch && (session()->has('selected_scholarship') || session()->has('last_search_results'))) {\n                $fundingRegex = '/\\b(beasiswa\\s+)?(ini|itu|tersebut|no\\s+\\d+|nomor\\s+\\d+|ke\\s+\\d+)?\\s*(apakah|tipe)?\\s*(fully\\s+funded|full\\s+funded|partially\\s+funded|partial\\s+funded|dana\\s+penuh|dana\\s+sebagian|biaya\\s+penuh|biaya\\s+sebagian)\\b/i';\n                if (preg_match($fundingRegex, $message, $matches)) {\n                    $ref = trim($matches[2] ?? '');\n                    $fundingFound = trim($matches[4]);\n                    return $this->handleFundingValidation($fundingFound, $normalizedData, $ref);\n                }\n            }"

if old in content:
    content = content.replace(old, new)
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE - funding validation fix applied')
else:
    idx = content.find('1.7.6 VALIDASI PENDANAAN')
    print('NOT FOUND - preview:')
    print(repr(content[idx:idx+400]))
