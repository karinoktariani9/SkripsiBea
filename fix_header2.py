content = open('app/Http/Controllers/ChatbotController.php').read()

old = "            elseif (!empty($criteria['lokasi_tipe'])) {\n                $headerParts[] = ($criteria['lokasi_tipe'] === 'luar') ? \"khusus di Luar Negeri\" : \"khusus di Dalam Negeri\";\n            }\n\n            $resp = \"Berikut beberapa beasiswa \" . implode(' ', $headerParts) . \":\\n\\n\";"

new = "            elseif (!empty($criteria['lokasi_tipe'])) {\n                $headerParts[] = ($criteria['lokasi_tipe'] === 'luar') ? \"khusus di Luar Negeri\" : \"khusus di Dalam Negeri\";\n            }\n            if (!empty($criteria['funding'])) $headerParts[] = \"kategori \" . $criteria['funding'];\n\n            $resp = \"Berikut beberapa beasiswa \" . implode(' ', $headerParts) . \":\\n\\n\";"

if old in content:
    content = content.replace(old, new)
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE')
else:
    print('NOT FOUND')
