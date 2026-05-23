content = open('app/Http/Controllers/ChatbotController.php').read()
fixes = 0

# FIX 1: Tambah 'jepng', 'jepan', 'jpang' ke targetWords levenshtein
# dan tambah 'jepang' ke synonym map
old1 = "        $targetWords = [\n            'benefit', 'benefitnya', 'persyaratan', 'syaratnya', 'syarat', 'deadline', 'deadlinenya',\n            'apply', 'daftar', 'daftarnya', 'pendaftaran', 'beasiswa', 'beasiswanya', 'pendanaan',\n            'detail', 'boleh', 'terimakasih', 'makasih', 'negara', 'benua', 'kapan', 'gimana', 'dimana', 'bagaimana', 'cara'\n        ];"
new1 = "        $targetWords = [\n            'benefit', 'benefitnya', 'persyaratan', 'syaratnya', 'syarat', 'deadline', 'deadlinenya',\n            'apply', 'daftar', 'daftarnya', 'pendaftaran', 'beasiswa', 'beasiswanya', 'pendanaan',\n            'detail', 'boleh', 'terimakasih', 'makasih', 'negara', 'benua', 'kapan', 'gimana', 'dimana', 'bagaimana', 'cara',\n            'jepang', 'korea', 'inggris', 'jerman', 'belanda', 'kanada', 'prancis', 'turki', 'mesir', 'rusia',\n            'australia', 'malaysia', 'singapura', 'thailand', 'china', 'india', 'amerika', 'perancis'\n        ];"

if old1 in content:
    content = content.replace(old1, new1)
    print('Fix 1 applied: negara added to levenshtein targets')
    fixes += 1
else:
    print('Fix 1 NOT FOUND')

# FIX 2: "link no 3" kena handler nomor sebelum link handler
# Pindahkan link nomor check SEBELUM nomor selection handler
# Cek apakah link handler sudah di posisi yang benar
idx_link = content.find('// 4.4.5 LINK NOMOR X')
idx_next = content.find('// 4.5 NEXT PAGE')
idx_detail = content.find('getDetailIntent')

print('Link handler pos: ' + str(idx_link))
print('Next page pos: ' + str(idx_next))
print('Detail intent pos: ' + str(idx_detail))

# Cari handler nomor selection (pilih nomor X)
if 'selected_scholarship' in content:
    idx_select = content.find('selected_scholarship')
    print('Nomor selection pos: ' + str(idx_select))

# Fix: tambah 'link' ke synonym agar "link no 3" diproses sebagai link dulu
# Cek apakah ada handler nomor sebelum link handler
# Cari pattern nomor handler
import re
matches = [(m.start(), m.group()) for m in re.finditer(r'// \d+\.\d*\.?\d* .*nomor|pilih.*nomor|nomor.*pilih', content, re.IGNORECASE)]
for pos, txt in matches[:5]:
    print('Nomor handler at ' + str(pos) + ': ' + txt[:60])

open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
if fixes > 0:
    print('DONE - ' + str(fixes) + ' fixes applied')
