content = open('app/Http/Controllers/ChatbotController.php').read()
fixes = 0

# FIX 1: Tambah negara ke targetWords levenshtein - cari format yang ada di file
old1 = "            'detail', 'boleh', 'terimakasih', 'makasih', 'negara', 'benua', 'kapan', 'gimana', 'dimana', 'bagaimana', 'cara'"
new1 = "            'detail', 'boleh', 'terimakasih', 'makasih', 'negara', 'benua', 'kapan', 'gimana', 'dimana', 'bagaimana', 'cara',\n            'jepang', 'korea', 'inggris', 'jerman', 'belanda', 'kanada', 'australia', 'malaysia', 'singapura', 'thailand', 'rusia', 'turki', 'mesir', 'prancis', 'amerika'"

if old1 in content:
    content = content.replace(old1, new1)
    print('Fix 1 applied: negara added to levenshtein targets')
    fixes += 1
else:
    # Cari format yang ada
    idx = content.find("'detail', 'boleh'")
    if idx >= 0:
        print('Fix 1 NOT FOUND - teks sekitar targetWords:')
        print(repr(content[idx:idx+200]))
    else:
        print('Fix 1 NOT FOUND - targetWords tidak ketemu')

# FIX 2: Link handler harus jalan SEBELUM nomor selection handler
# Caranya: di link handler, cek "link" keyword SEBELUM normalisasi 'no' => 'nomor'
# Solusi: tambah pre-check di synonym map - jika ada 'link' jangan ubah 'no' jadi 'nomor'
# Cara paling simple: tambah 'link nomor' dan 'link no' ke synonym map sebagai phrase khusus
# sehingga diproses sebelum 'no' => 'nomor' merusak konteks

# Cek posisi link handler vs nomor handler
idx_link = content.find('// 4.4.5 LINK NOMOR X')
idx_nomor = content.find('// 2.1 Deteksi Pemilihan Nomor')
print('Link handler pos: ' + str(idx_link))
print('Nomor handler pos: ' + str(idx_nomor))

if idx_link > idx_nomor:
    print('MASALAH: Link handler ('+str(idx_link)+') SETELAH nomor handler ('+str(idx_nomor)+') - perlu dipindah')
    
    # Extract blok link handler
    start = content.find('            // 4.4.5 LINK NOMOR X')
    end = content.find('\n\n            // 4.5 NEXT PAGE', start) + len('\n\n            // 4.5 NEXT PAGE')
    # Ambil sampai akhir blok link handler
    end2 = content.find('            // 4.5 NEXT PAGE (YANG LAIN)', start)
    link_block = content[start:end2]
    
    print('Link block preview: ' + link_block[:100])
    
    # Hapus dari posisi lama
    content_without = content[:start] + content[end2:]
    
    # Sisipkan SEBELUM nomor handler
    insert_before = '            // 2.1 Deteksi Pemilihan Nomor'
    insert_pos = content_without.find(insert_before)
    
    if insert_pos >= 0:
        content = content_without[:insert_pos] + link_block + '\n\n' + content_without[insert_pos:]
        print('Fix 2 applied: link handler moved before nomor handler')
        fixes += 1
    else:
        print('Fix 2 NOT FOUND: insert position not found')
else:
    print('Fix 2: link handler already before nomor handler, OK')

if fixes > 0:
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE - ' + str(fixes) + ' fixes applied')
else:
    print('No changes written')
