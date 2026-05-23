content = open('app/Http/Controllers/ChatbotController.php').read()
fixes = 0

# FIX 1: Tambah sinonim fully fund + lnjutin
old1 = "            'dana penuh' => 'fully funded', 'beasiswa penuh' => 'fully funded', 'pendanaan penuh' => 'fully funded',"
new1 = "            'dana penuh' => 'fully funded', 'beasiswa penuh' => 'fully funded', 'pendanaan penuh' => 'fully funded',\n            'fully fund' => 'fully funded', 'full funded' => 'fully funded', 'full fund' => 'fully funded',\n            'lnjutin' => 'yang lain', 'lnjut' => 'yang lain', 'lanjutin' => 'yang lain', 'terusin' => 'yang lain', 'lanjut' => 'yang lain',"

if old1 in content:
    content = content.replace(old1, new1)
    print('Fix 1 applied: synonym fully fund + lnjutin')
    fixes += 1
else:
    print('Fix 1 NOT FOUND')

# FIX 2: Expand isNextPage pattern
old2 = "$isNextPage = preg_match('/\\b(lainnya|yang lain|selanjutnya|berikutnya|lagi|next)\\b/i', $message);"
new2 = "$isNextPage = preg_match('/\\b(lainnya|yang lain|selanjutnya|berikutnya|lagi|next|lanjut|lanjutin|terusin|lnjut|lnjutin)\\b/i', $message);"

if old2 in content:
    content = content.replace(old2, new2)
    print('Fix 2 applied: isNextPage pattern expanded')
    fixes += 1
else:
    print('Fix 2 NOT FOUND')

# FIX 3: link nomor X handler
if '// 4.4.5' in content:
    print('Fix 3: already exists, skipping')
else:
    old3 = "            // 4.5 NEXT PAGE (YANG LAIN)"
    new3 = "            // 4.4.5 LINK NOMOR X\n            if (preg_match('/\\b(link|url|tautan)\\b.*\\b(nomor|no|nmr|#)\\s*(\\d+)/i', $message, $linkMatch) ||\n                preg_match('/\\b(nomor|no|nmr|#)\\s*(\\d+).*\\b(link|url|tautan)\\b/i', $message, $linkMatch2)) {\n                $displayNum = isset($linkMatch[3]) ? (int)$linkMatch[3] : (int)$linkMatch2[2];\n                $allResults = session()->get('last_search_all_results', []);\n                $idx = $displayNum - 1;\n                if ($idx >= 0 && $idx < count($allResults)) {\n                    $s = (array)$allResults[$idx];\n                    $name = $s['nama_beasiswa'] ?? 'Beasiswa ini';\n                    $urlAsli = $s['url_asli'] ?? null;\n                    $urlSchoters = $s['url'] ?? null;\n                    $urlInternal = route('scholarship.detail', ['id' => $s['id']]);\n                    $ans = \"Berikut link untuk **$name**:\\n\\n\";\n                    if ($urlAsli) $ans .= \"\xf0\x9f\x8c\x90 [**Website Resmi**]($urlAsli)\\n\\n\";\n                    if ($urlSchoters) $ans .= \"\xf0\x9f\x93\x8b [**Info di Schoters**]($urlSchoters)\\n\\n\";\n                    $ans .= \"\xf0\x9f\x91\x89 [**Halaman Detail**]($urlInternal)\";\n                    return $this->finalizeResponse($ans, $normalizedData);\n                }\n            }\n\n            // 4.5 NEXT PAGE (YANG LAIN)"

    if old3 in content:
        content = content.replace(old3, new3)
        print('Fix 3 applied: link nomor X handler added')
        fixes += 1
    else:
        print('Fix 3 NOT FOUND')

if fixes > 0:
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE - ' + str(fixes) + ' fixes applied')
else:
    print('No changes made')
