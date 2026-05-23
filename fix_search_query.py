content = open('app/Http/Controllers/ChatbotController.php').read()

old = "            'beasiswa', 'scholarship', 'kuliah', 'studi', 'daftar', 'apply', 'registrasi',\n            'pendaftaran', 's1', 's2', 's3', 'd3', 'd4', 'jenjang', 'sarjana', 'magister', 'doktor'"
new = "            'beasiswa', 'scholarship', 'kuliah', 'studi', 'daftar', 'apply', 'registrasi',\n            'pendaftaran', 's1', 's2', 's3', 'd3', 'd4', 'jenjang', 'sarjana', 'magister', 'doktor',\n            'fully funded', 'partially funded', 'fully fund', 'partial fund', 'dana penuh', 'dana sebagian'"

if old in content:
    content = content.replace(old, new)
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE - funding keywords added to isSearchQuery')
else:
    print('NOT FOUND')
