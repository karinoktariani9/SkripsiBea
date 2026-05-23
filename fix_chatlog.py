import io

f = io.open('app/Http/Controllers/ChatbotController.php', encoding='utf-8')
content = f.read()
f.close()

# Cari bagian yang rusak dan ganti dengan yang benar
old = """    try {
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
            'user_message'       => $normalizedData['text'] ?? '',
            'bot_response'       => $answer,
            'intent'             => $normalizedData['intent'] ?? null,
            'response_time'      => $responseTime ? round($responseTime / 1000, 4) : null,
            'vector_search_time' => 0,
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
    print('NOT FOUND')
    idx = content.find("try {\n            'message'")
    if idx >= 0:
        print(repr(content[idx:idx+300]))
    else:
        print('Pattern juga tidak ketemu, dump finalizeResponse:')
        idx2 = content.find('private function finalizeResponse')
        print(repr(content[idx2:idx2+600]))
