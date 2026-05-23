<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1">
  <meta name="csrf-token"
        content="{{ csrf_token() }}">
  <title>Saved Scholarships - ScholarBot</title>

  <link rel="preconnect"
        href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap"
        rel="stylesheet" />

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen relative">
  <!-- Global background pattern -->
  <div class="fixed inset-0 z-[-1] bg-gradient-to-br from-indigo-50 via-slate-50 to-purple-50"></div>
  
  <header class="sticky top-0 z-50 border-b border-white/50 bg-white/70 backdrop-blur-xl shadow-sm">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <a href="{{ url('/') }}" class="flex items-center gap-2.5">
        <span class="text-2xl font-bold text-gray-800 tracking-tight">🤖ScholarBot</span>
      </a>

      <nav class="hidden items-center gap-2 text-sm font-medium md:flex">
        <a href="{{ url('/') }}"
           class="rounded-full px-3 py-1.5 text-slate-500 hover:text-slate-700">Home</a>
        <a href="{{ route('scholarship') }}"
           class="rounded-full px-3 py-1.5 text-slate-500 hover:text-slate-700">Scholarships</a>
        <a href="{{ route('chatbot') }}"
           class="rounded-full px-3 py-1.5 text-slate-500 hover:text-slate-700">Chatbot</a>
        <a href="{{ route('bookmarks') }}"
           class="rounded-full bg-indigo-50 px-3 py-1.5 text-indigo-600">Saved</a>
      </nav>

      <div>
        @auth
          <div x-data="{ open: false }"
               class="relative">
            <button x-on:click="open = !open"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-600 shadow-sm hover:bg-indigo-100 transition-all">
              {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </button>

            <div x-show="open"
                 x-on:click.outside="open = false"
                 style="display: none;"
                 class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
              <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
              </div>
              <form method="POST"
                    action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-3 text-left text-sm font-semibold text-rose-600 hover:bg-rose-50">Logout</button>
              </form>
            </div>
          </div>
        @else
          <a href="{{ route('login') }}"
             class="rounded-xl bg-indigo-50 px-6 py-2.5 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100 transition-all">Login</a>
        @endauth
      </div>
    </div>
  </header>

  <main class="flex-grow mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg"
           class="h-8 w-8 text-indigo-600"
           viewBox="0 0 24 24"
           fill="none"
           stroke="currentColor"
           stroke-width="2">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 21l-1.45-1.32C5.4 15.04 2 11.95 2 8.15 2 5.06 4.42 2.65 7.5 2.65c1.74 0 3.41.81 4.5 2.09 1.09-1.28 2.76-2.09 4.5-2.09 3.08 0 5.5 2.41 5.5 5.5 0 3.8-3.4 6.89-8.55 11.54L12 21z" />
      </svg>
      <h1 class="text-4xl font-bold text-slate-800">Saved Scholarships</h1>
    </div>
    <p class="mt-2 text-slate-500">Kamu punya {{ $savedScholarships->count() }} beasiswa tersimpan</p>

    <section class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
      @forelse ($savedScholarships as $item)
        <article class="group overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-indigo-500/10">
          <div class="h-32 w-full bg-gradient-to-r from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg px-4 text-center">
            {{ $item->nama_beasiswa }}
          </div>
          <div class="p-4">
            <div class="mb-3 flex gap-2 text-[11px] font-semibold text-white">
                @if($item->kategori)
                    <span class="rounded-full bg-emerald-500 px-2 py-0.5">{{ $item->kategori }}</span>
                @endif
                <span class="rounded-full bg-violet-500 px-2 py-0.5">{{ $item->negara ?? 'International' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
              <div>
                <h3 class="text-xl font-bold text-slate-800 line-clamp-2 leading-tight h-14">{{ $item->nama_beasiswa }}</h3>
                <p class="text-sm text-slate-500 mt-1">{{ Str::limit($item->deskripsi, 50) }}</p>
              </div>
              <form action="{{ route('bookmarks.toggle', $item->id) }}" method="POST">
                @csrf
                <button type="submit" class="mt-1 text-indigo-600 hover:text-rose-600 transition-colors" title="Remove from Saved">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z" />
                    </svg>
                </button>
              </form>
            </div>
            <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
              <span class="flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ $item->negara }}
              </span>
              <span class="flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  {{ $item->deadline }}
              </span>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('scholarship.detail', $item->id) }}" class="flex-1 text-center py-2 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-bold hover:bg-indigo-100 transition-colors">
                    Lihat Detail
                </a>
            </div>
          </div>
        </article>
      @empty
        <div class="col-span-full py-20 text-center">
            <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-800">Belum ada beasiswa tersimpan</h3>
            <p class="text-slate-500 mt-2">Cari beasiswa favoritmu dan klik tombol simpan untuk melihatnya di sini.</p>
            <a href="{{ route('scholarship') }}" class="mt-6 inline-block bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                Cari Beasiswa
            </a>
        </div>
      @endforelse
    </section>
  </main>
  <footer class="w-full bg-slate-800 py-16 text-slate-400 flex-grow-0 relative z-10 mt-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-12 md:grid-cols-3 md:gap-8 text-center md:text-left">
        <div>
          <a href="{{ url('/') }}" class="flex items-center gap-2.5 justify-center md:justify-start text-white">
            <span class="text-2xl font-bold tracking-tight">🤖ScholarBot</span>
          </a>
          <p class="mt-4 text-sm leading-relaxed text-slate-400 max-w-xs mx-auto md:mx-0">
            Platform pintar untuk menemukan beasiswa yang tepat untuk masa depanmu.
          </p>
        </div>
        
        <div class="flex flex-col items-center">
          <h3 class="text-sm font-bold tracking-wider text-white uppercase mb-4">Navigation</h3>
          <div class="flex flex-col gap-3 text-sm font-medium">
            <a href="{{ url('/') }}" class="hover:text-indigo-400 transition-colors">Home</a>
            <a href="{{ route('scholarship') }}" class="hover:text-indigo-400 transition-colors">Scholarships</a>
            <a href="{{ route('chatbot') }}" class="hover:text-indigo-400 transition-colors">Chatbot</a>
            <a href="{{ route('bookmarks') }}" class="hover:text-indigo-400 transition-colors">Saved</a>
          </div>
        </div>
        
        <div class="flex flex-col items-center md:items-end">
          <h3 class="text-sm font-bold tracking-wider text-white uppercase mb-4">Mulai Sekarang</h3>
          <p class="text-sm text-slate-400 mb-4 font-medium">Punya pertanyaan soal beasiswa? Tanya AI sekarang.</p>
          <a href="{{ route('chatbot') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 transition-all">
            Tanya AI
          </a>
        </div>
      </div>
      <div class="mt-16 border-t border-slate-700 pt-8 text-center">
        <p class="text-sm text-slate-500">© 2026 ScholarBot. All rights reserved.</p>
      </div>
    </div>
  </footer>
</body>

</html>
