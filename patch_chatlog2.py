content = open('app/Http/Controllers/ChatbotController.php', encoding='utf-8').read()

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
    open('app/Http/Controllers/ChatbotController.php', 'w', encoding='utf-8').write(content.replace(old, new, 1))
    print('DONE')
else:
    print('NOT FOUND')
    # debug
    idx = content.find('Log::info("Chatbot Response Time')
    print(repr(content[idx:idx+200]))
