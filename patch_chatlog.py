content = open('app/Http/Controllers/ChatbotController.php').read()

old = 'private function finalizeResponse(\, \ = null, \ = true)\n{\n    \ = isset(\->startTime) ? round((microtime(true) - \->startTime) * 1000, 2) : null;\n    if (\ !== null) {\n        Log::info("Chatbot Response Time: {\}ms | Success: " . (\ ? \'true\' : \'false\'));\n    }'

new = old + '''
    try {
        \\App\\Models\\ChatLog::create([
            \'message\'       => \[\'original\'] ?? (\[\'text\'] ?? \'\'),
            \'answer\'        => \,
            \'intent\'        => \[\'intent\'] ?? null,
            \'response_time\' => \,
            \'session_id\'    => session()->getId(),
            \'rag_enabled\'   => true,
        ]);
    } catch (\\Exception \) {
        Log::warning(\'ChatLog gagal disimpan: \' . \->getMessage());
    }'''

if old in content:
    print('FOUND')
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content.replace(old, new, 1))
else:
    print('NOT FOUND')
