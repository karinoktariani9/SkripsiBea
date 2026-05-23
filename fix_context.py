content = open('app/Http/Controllers/ChatbotController.php').read()

old = "            $isFollowUp = strlen($message) < 20 || preg_match('/\\b(doang|cuma|hanya|berapa|itu|tadi|lagi|aja|saja)\\b/i', $message);\n            if ($isFollowUp && !$isNewMajor && !$isNewLevel && !$isNewCountry) {"

new = "            $hasFunding = !empty($criteria['funding']);\n            $isFollowUp = (strlen($message) < 20 || preg_match('/\\b(doang|cuma|hanya|berapa|itu|tadi|lagi|aja|saja)\\b/i', $message)) && !$hasFunding;\n            if ($isFollowUp && !$isNewMajor && !$isNewLevel && !$isNewCountry) {"

if old in content:
    content = content.replace(old, new)
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE - funding query no longer inherits old country context')
else:
    idx = content.find('isFollowUp')
    print('NOT FOUND - preview:')
    print(repr(content[idx:idx+300]))
