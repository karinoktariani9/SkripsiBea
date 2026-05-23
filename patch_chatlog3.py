import io

f = io.open('app/Http/Controllers/ChatbotController.php', encoding='utf-8')
content = f.read()
f.close()

old = """    if ($responseTime !== null) {
        Log::info("Chatbot Response Time: {$responseTime}ms | Success: " . ($success ? 'true' : 'false'));
    }
    return response()->json(["""

new = """    if ($responseTime !== null) {
        Log::info("Chatbot Response Time: {$responseTime}ms | Success: " . ($success ? 'true' : 'false'));
    }
    try {
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
    }
    return response()->json(["""

if old in content:
    print('FOUND - patching...')
    result = content.replace(old, new, 1)
    f2 = io.open('app/Http/Controllers/ChatbotController.php', 'w', encoding='utf-8')
    f2.write(result)
    f2.close()
    print('DONE')
else:
    print('NOT FOUND')
    idx = content.find('Log::info("Chatbot Response Time')
    print(repr(content[idx:idx+200]))
