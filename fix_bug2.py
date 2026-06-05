content = open('app/Http/Controllers/ChatbotController.php').read()
old = "selectedNumber = null;\n            if (preg_match('/\\\\b(?:nomor|no|pilih|nmr|#)\\\\s*([0-9]+)\\\\b/i', \, \) || \n                preg_match('/^(?:pilih\\\\s+|nomor\\\\s+|no\\\\s+|nmr\\\\s+|#)?([0-9]+)\$/i', trim(\), \)) {\n                \ = (int)\[1];\n            } else if (\ && preg_match('/\\\\b([0-9]+)\\\\b/', \, \)) {\n                \ = (int)\[1];\n            }"
new = "selectedNumber = null;\n            \ = preg_match('/\\\\b(?:nomor|no|pilih|nmr|#)\\\\s*([0-9]+)\\\\b/i', \, \) ||\n                preg_match('/^(?:pilih\\\\s+|nomor\\\\s+|no\\\\s+|nmr\\\\s+|#)?([0-9]+)\$/i', trim(\), \);\n            if (\) {\n                \ = (int)\[1];\n                if (\ >= 1 && \ <= 50) \ = \;\n            } else if (\ && preg_match('/\\\\b([0-9]+)\\\\b/', \, \)) {\n                \ = (int)\[1];\n                if (\ >= 1 && \ <= 20 && strlen(trim(\)) <= 15) {\n                    \ = \;\n                }\n            }"
if old in content:
    content = content.replace(old, new, 1)
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE')
else:
    print('NOT FOUND')
