import re

# 1. Tambah route ke web.php
content = open('routes/web.php').read()
old = "Route::post('/chatbot/ask', [ChatbotController::class, 'ask']);"
new = "Route::post('/chatbot/ask', [ChatbotController::class, 'ask']);\nRoute::post('/chatbot/clear', [ChatbotController::class, 'clearSession'])->name('chatbot.clear');"

if old in content:
    content = content.replace(old, new)
    open('routes/web.php', 'w').write(content)
    print('Fix 1 applied: route clear session added')
else:
    print('Fix 1 NOT FOUND')

# 2. Tambah method clearSession ke ChatbotController
content = open('app/Http/Controllers/ChatbotController.php').read()
old2 = "    public function ask(Request $request)"
new2 = """    public function clearSession()
    {
        session()->forget([
            'last_search_all_results',
            'last_search_criteria',
            'last_search_page',
            'last_search_results',
            'selected_scholarship',
        ]);
        return response()->json(['success' => true, 'message' => 'Session cleared']);
    }

    public function ask(Request $request)"""

if old2 in content:
    content = content.replace(old2, new2)
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('Fix 2 applied: clearSession method added')
else:
    print('Fix 2 NOT FOUND')

# 3. Tambah JS ke chatbot.blade.php - tombol New Chat call /chatbot/clear
content = open('resources/views/chatbot.blade.php').read()
old3 = '<button class="mb-6 w-full rounded-xl bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-600 transition-all">\xe2\x9c\xa8 New Chat</button>'
new3 = '<button onclick="clearSession()" class="mb-6 w-full rounded-xl bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-600 transition-all">\xe2\x9c\xa8 New Chat</button>'

if old3 in content:
    content = content.replace(old3, new3)
    open('resources/views/chatbot.blade.php', 'w').write(content)
    print('Fix 3 applied: New Chat button connected')
else:
    print('Fix 3 NOT FOUND - cek tombol:')
    idx = content.find('New Chat')
    print(repr(content[idx-100:idx+50]))

# 4. Tambah JS function clearSession sebelum </script> atau </body>
content = open('resources/views/chatbot.blade.php').read()
clear_js = """
<script>
function clearSession() {
    fetch('/chatbot/clear', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
        }
    }).then(() => {
        // Reload halaman untuk reset tampilan chat
        window.location.reload();
    });
}
</script>
"""

if 'function clearSession' not in content:
    # Sisipkan sebelum </body>
    if '</body>' in content:
        content = content.replace('</body>', clear_js + '</body>')
        open('resources/views/chatbot.blade.php', 'w').write(content)
        print('Fix 4 applied: clearSession JS added')
    else:
        print('Fix 4 NOT FOUND: </body> not found')
else:
    print('Fix 4: clearSession JS already exists')
