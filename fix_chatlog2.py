import io

f = io.open('app/Http/Controllers/ChatbotController.php', encoding='utf-8')
content = f.read()
f.close()

old = """    try {
        \\App\\Models\\ChatLog::create([
            'message'       => $normalizedData['original'] ?? ($normalizedData['text'] ?? ''),
            'answer'        => $answer,
            'intent'        => $normalizedData['intent'] ?? null,
            'response_time' => (int) $responseTime,
            'session_id'    => session()->getId(),
            'rag_enabled'   => true,
        ]);
    } catch (\\Exception $e) {
        \\Illuminate\\Support\\Facades\\Log::warning('ChatLog gagal disimpan: ' . $e->getMessage());
    }"""

new = """    try {
        \\App\\Models\\ChatLog::create([
            'user_message'      => $normalizedData['text'] ?? '',
            'bot_response'      => $answer,
            'intent'            => $normalizedData['intent'] ?? null,
            'response_time'     => $responseTime ? round($responseTime / 1000, 4) : null,
            'vector_search_time'=> 0,
        ]);
    } catch (\\Exception $e) {
        \\Illuminate\\Support\\Facades\\Log::warning('ChatLog gagal disimpan: ' . $e->getMessage());
    }"""

if old in content:
    print('FOUND - fixing...')
    result = content.replace(old, new, 1)
    f2 = io.open('app/Http/Controllers/ChatbotController.php', 'w', encoding='utf-8')
    f2.write(result)
    f2.close()
    print('DONE')
else:
    print('NOT FOUND - trying alternate whitespace...')
    # dump raw bytes sekitar ChatLog::create
    idx = content.find("\\App\\Models\\ChatLog::create")
    if idx >= 0:
        print(repr(content[idx-20:idx+400]))
    else:
        print('ChatLog::create tidak ketemu sama sekali')
