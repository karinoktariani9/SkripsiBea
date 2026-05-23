<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1">
  <meta name="csrf-token"
        content="{{ csrf_token() }}">
  <title>Scholarships - ScholarBot</title>

  <link rel="preconnect"
        href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap"
        rel="stylesheet" />

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen relative">
  <!-- Global background pattern -->
  <div class="fixed inset-0 z-[-1] bg-gradient-to-br from-indigo-50 via-slate-50 to-purple-50"></div>
  
  <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl shadow-sm border-b border-slate-100">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <a href="{{ url('/') }}" class="flex items-center gap-2.5">
        <span class="text-2xl font-bold text-gray-800 tracking-tight">🤖ScholarBot</span>
      </a>

      <!-- Desktop Navigation -->
      <nav class="hidden items-center gap-2 text-sm font-medium md:flex">
        <a href="{{ url('/') }}" class="rounded-full px-4 py-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all">Home</a>
        <a href="{{ route('scholarship') }}" class="rounded-full bg-indigo-50 px-4 py-2 text-indigo-600 font-semibold">Scholarships</a>
        <a href="{{ route('chatbot') }}" class="rounded-full px-4 py-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all">Chatbot</a>
        <a href="{{ route('bookmarks') }}" class="rounded-full px-4 py-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all">Saved</a>
      </nav>

      <div class="flex items-center gap-3">
        <!-- User Profile (Desktop & Mobile) -->
        @auth
          <div x-data="{ open: false }" class="relative">
            <button x-on:click="open = !open" class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-600 shadow-sm hover:bg-indigo-100 transition-all">
              {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </button>
            <div x-show="open" x-on:click.outside="open = false" style="display: none;" class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
              <div class="border-b border-slate-100 px-4 py-3 bg-slate-50">
                <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
              </div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full px-4 py-3 text-left text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors">Logout</button>
              </form>
            </div>
          </div>
        @else
          <a href="{{ route('login') }}" class="hidden md:inline-flex rounded-xl bg-indigo-50 px-6 py-2.5 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100 transition-all">Login</a>
        @endauth

        <!-- Mobile Menu Button -->
        <button x-on:click="mobileMenuOpen = !mobileMenuOpen" class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-600 md:hidden">
          <svg x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
          <svg x-show="mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         style="display: none;"
         class="md:hidden bg-white border-b border-slate-100 px-4 py-6 shadow-xl">
      <nav class="flex flex-col gap-2">
        <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-base font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
          Home
        </a>
        <a href="{{ route('scholarship') }}" class="flex items-center gap-3 rounded-xl bg-indigo-50 px-4 py-3 text-base font-semibold text-indigo-600 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
          Scholarships
        </a>
        <a href="{{ route('chatbot') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-base font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
          Chatbot
        </a>
        <a href="{{ route('bookmarks') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-base font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
          Saved
        </a>
        @guest
          <a href="{{ route('login') }}" class="mt-2 flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-base font-bold text-white shadow-lg shadow-indigo-200 transition-all">Login</a>
        @endguest
      </nav>
    </div>
  </header>

  <main class="flex-grow mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-4xl font-bold text-slate-800">Yuk, Jelajahi Beasiswa! ✨</h1>
    <p class="mt-2 text-slate-500">Ada {{ $scholarships->total() }} beasiswa yang bisa kamu cek sekarang</p>

    <section class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-12">
      <aside class="lg:col-span-3">
        <form action="{{ route('scholarship') }}" method="GET" class="sticky top-24 rounded-3xl border border-slate-100 bg-white p-8 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-800 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Filters
          </h2>

          <!-- Search Bar -->
          <div class="mt-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">🔍 Cari Beasiswa</h3>
            <div class="relative">
              <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau Negara..." 
                     class="w-full rounded-xl border-slate-200 pl-4 pr-10 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
              <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </button>
            </div>
            @if(request('search'))
              <a href="{{ route('scholarship', request()->except('search')) }}" class="mt-1 inline-block text-[10px] text-rose-500 hover:underline">Hapus pencarian</a>
            @endif
          </div>

          <div class="mt-8">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">🌏 Negara Tujuan</h3>
            <div class="mt-3 space-y-3 text-sm text-slate-600">
              <label class="flex items-center gap-2"><input type="radio" name="destination" value="" onchange="this.form.submit()" {{ request('destination') == '' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500"> All</label>
              <label class="flex items-center gap-2"><input type="radio" name="destination" value="domestic" onchange="this.form.submit()" {{ request('destination') == 'domestic' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500"> Domestic (Dalam Negeri)</label>
              <label class="flex items-center gap-2"><input type="radio" name="destination" value="international" onchange="this.form.submit()" {{ request('destination') == 'international' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500"> International (Luar Negeri)</label>
            </div>
          </div>

          <div class="mt-8">
            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">🎓 Jenjang Studi</h3>
            <div class="mt-3 space-y-3 text-sm text-slate-600">
              <label class="flex items-center gap-2"><input type="radio" name="degree" value="" onchange="this.form.submit()" {{ request('degree') == '' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500"> Semua Jenjang</label>
              <label class="flex items-center gap-2"><input type="radio" name="degree" value="S1" onchange="this.form.submit()" {{ request('degree') == 'S1' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500"> S1</label>
              <label class="flex items-center gap-2"><input type="radio" name="degree" value="S2" onchange="this.form.submit()" {{ request('degree') == 'S2' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500"> S2</label>
              <label class="flex items-center gap-2"><input type="radio" name="degree" value="S3" onchange="this.form.submit()" {{ request('degree') == 'S3' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500"> S3</label>
            </div>
          </div>

          <a href="{{ route('scholarship') }}" class="mt-8 block w-full text-center rounded-xl border border-indigo-200 px-4 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">Reset Filters</a>
        </form>
      </aside>

      <div class="lg:col-span-9">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
          @foreach ($scholarships as $item)
            <div class="block group overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-indigo-500/10 relative">
              <div class="h-44 w-full overflow-hidden relative bg-slate-100">
                <a href="{{ route('scholarship.detail', $item->id) }}" class="block h-full w-full">
                  <img src="https://picsum.photos/seed/scholarship-{{ $item->id }}/800/600" 
                       alt="{{ $item->nama_beasiswa }}" 
                       class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                       onerror="this.src='https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=800&auto=format&fit=crop'" />
                </a>
                <div class="absolute top-3 right-3 z-20">
                  <form action="{{ route('bookmarks.toggle', $item->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-400 backdrop-blur-sm hover:text-rose-500 shadow-md transition-all">
                      @if(in_array($item->id, $bookmarkedIds))
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-rose-500" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                        </svg>
                      @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                      @endif
                    </button>
                  </form>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent pointer-events-none"></div>
              </div>
              <a href="{{ route('scholarship.detail', $item->id) }}" class="p-4 block">
                <div class="mb-3 flex gap-2 text-[11px] font-semibold text-white">
                  <span class="rounded-full bg-emerald-500 px-2 py-0.5">{{ $item->kategori ?? 'Fully Funded' }}</span>
                  <span class="rounded-full bg-violet-500 px-2 py-0.5">{{ str_contains(strtolower($item->negara), 'indonesia') ? 'Domestic' : 'International' }}</span>
                </div>
                <div class="flex items-start justify-between gap-3">
                  <div class="overflow-hidden">
                    <h3 class="text-lg font-bold text-slate-800 line-clamp-2 leading-tight h-12">{{ $item->nama_beasiswa }}</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ $item->negara }}</p>
                  </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                  <span class="font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">{{ $item->jenjang }}</span>
                  <span>{{ $item->deadline ?? 'No deadline' }}</span>
                </div>
              </a>
            </div>
          @endforeach
        </div>
        
        <div class="mt-10">
          {{ $scholarships->links() }}
        </div>
      </div>
    </section>
  </main>
  <footer class="w-full bg-[#FFFFFF] py-16 text-gray-500 flex-grow-0 border-t border-slate-200 mt-20 relative z-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-12 md:grid-cols-3 md:gap-8 text-center md:text-left">
        <div>
          <a href="{{ url('/') }}" class="flex items-center gap-2.5 justify-center md:justify-start text-gray-800">
            <span class="text-2xl font-bold tracking-tight">🤖ScholarBot</span>
          </a>
          <p class="mt-4 text-sm leading-relaxed text-gray-500 max-w-xs mx-auto md:mx-0">
            Platform pintar untuk menemukan beasiswa yang tepat untuk masa depanmu.
          </p>
        </div>
        
        <div class="flex flex-col items-center">
          <h3 class="text-sm font-bold tracking-wider text-gray-800 uppercase mb-4">Navigation</h3>
          <div class="flex flex-col gap-3 text-sm font-medium">
            <a href="{{ url('/') }}" class="hover:text-blue-600 transition-colors">Home</a>
            <a href="{{ route('scholarship') }}" class="text-blue-600 transition-colors">Scholarships</a>
            <a href="{{ route('chatbot') }}" class="hover:text-blue-600 transition-colors">Chatbot</a>
            <a href="{{ route('bookmarks') }}" class="hover:text-blue-600 transition-colors">Saved</a>
          </div>
        </div>
        
        <div class="flex flex-col items-center md:items-end">
          <h3 class="text-sm font-bold tracking-wider text-gray-800 uppercase mb-4">Mulai Sekarang</h3>
          <p class="text-sm text-gray-500 mb-4 font-medium">Punya pertanyaan soal beasiswa? Tanya AI sekarang.</p>
          <a href="{{ route('chatbot') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-600 transition-all">
            Tanya AI
          </a>
        </div>
      </div>
      <div class="mt-16 border-t border-slate-200 pt-8 text-center">
        <p class="text-sm text-gray-400">© 2026 ScholarBot. All rights reserved.</p>
      </div>
    </div>
  </footer>
</body>

</html>
