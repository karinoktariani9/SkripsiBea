content = open('app/Http/Controllers/ChatbotController.php').read()

old = "            $isFollowUp = strlen($message) < 20 || preg_match('/\\b(doang|cuma|hanya|berapa|itu|tadi|lagi|aja|saja)\\b/i', $message);\n            \n            if ($isFollowUp && !$isNewMajor && !$isNewLevel && !$isNewCountry) {"

new = "            $hasFunding = !empty($criteria['funding']);\n            $isFollowUp = (strlen($message) < 20 || preg_match('/\\b(doang|cuma|hanya|berapa|itu|tadi|lagi|aja|saja)\\b/i', $message)) && !$hasFunding;\n            \n            if ($isFollowUp && !$isNewMajor && !$isNewLevel && !$isNewCountry) {"

if old in content:
    content = content.replace(old, new)
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE')
else:
    print('NOT FOUND')
    idx = content.find('isFollowUp')
    print(repr(content[idx-5:idx+250]))
