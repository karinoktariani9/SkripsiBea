content = open('app/Http/Controllers/ChatbotController.php').read()

old = """            case 'apply':
                $url = route('scholarship.detail', ['id' => $selected['id']]);
                $ans = \"Untuk melihat informasi lengkap dan melakukan pendaftaran **$name**, silakan kunjungi halaman detail beasiswa berikut:\\n\\n\xf0\x9f\x91\x89 [**Buka Halaman Detail Beasiswa**]($url)\";
                break;"""

new = """            case 'apply':
                $urlInternal = route('scholarship.detail', ['id' => $selected['id']]);
                $urlSchoters = $selected['url'] ?? null;
                $urlAsli = $selected['url_asli'] ?? null;
                $ans = \"Untuk mendaftar beasiswa **$name**, berikut link yang bisa kamu akses:\\n\\n\";
                if ($urlAsli) $ans .= \"\xf0\x9f\x8c\x90 [**Website Resmi Beasiswa**]($urlAsli)\\n\\n\";
                if ($urlSchoters) $ans .= \"\xf0\x9f\x93\x8b [**Info Lengkap di Schoters**]($urlSchoters)\\n\\n\";
                $ans .= \"\xf0\x9f\x91\x89 [**Halaman Detail Internal**]($urlInternal)\";
                break;"""

if old in content:
    content = content.replace(old, new)
    open('app/Http/Controllers/ChatbotController.php', 'w').write(content)
    print('DONE - apply case updated')
else:
    # Cari teks sekitar apply untuk debug
    idx = content.find("case 'apply':")
    if idx >= 0:
        print('NOT FOUND - teks sekitar apply:')
        print(repr(content[idx:idx+300]))
    else:
        print('case apply tidak ditemukan sama sekali')
