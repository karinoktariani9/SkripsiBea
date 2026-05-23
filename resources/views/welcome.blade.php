<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1">
  <meta name="csrf-token"
        content="{{ csrf_token() }}">
  <title>ScholarBot</title>

  <link rel="preconnect"
        href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap"
        rel="stylesheet" />

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen relative">
  <!-- Global background pattern -->
  <div class="fixed inset-0 z-[-1] bg-gradient-to-br from-indigo-50 via-slate-50 to-purple-50"></div>
  <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur-xl shadow-sm">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <a href="{{ url('/') }}" class="flex items-center gap-2.5">
        <span class="text-2xl font-bold text-gray-800 tracking-tight">🤖ScholarBot</span>
      </a>

      <nav class="hidden items-center gap-8 text-sm font-semibold md:flex text-gray-500">
        <a href="{{ url('/') }}" class="text-blue-600">Home</a>
        <a href="{{ route('scholarship') }}" class="hover:text-blue-600 transition-colors">Scholarships</a>
        <a href="{{ route('chatbot') }}" class="hover:text-blue-600 transition-colors">Chatbot</a>
        <a href="{{ route('bookmarks') }}" class="hover:text-blue-600 transition-colors">Saved</a>
      </nav>

      <div>
        @auth
          <div x-data="{ open: false }" class="relative">
            <button x-on:click="open = !open" class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-600 shadow-sm hover:bg-indigo-100 transition-all">
              {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </button>
            <div x-show="open" x-on:click.outside="open = false" style="display: none;" class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
              <div class="border-b border-slate-100 px-4 py-3 bg-slate-50">
                <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
              </div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full px-4 py-3 text-left text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors">Logout</button>
              </form>
            </div>
          </div>
        @else
          <a href="{{ route('login') }}" class="rounded-xl bg-indigo-50 px-6 py-2.5 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100 transition-all">Login</a>
        @endauth
      </div>
    </div>
  </header>

  <!-- Educa Style Premium Hero Section -->
  <section class="relative w-full min-h-[750px] flex items-center overflow-hidden bg-white">
    <!-- Educa Background Shapes -->
    <div class="absolute top-0 right-0 w-1/2 h-full bg-indigo-50/50 rounded-l-[100px] -z-10 hidden lg:block"></div>
    <div class="absolute top-20 right-20 w-64 h-64 bg-purple-100/40 rounded-full blur-3xl -z-10"></div>
    <div class="absolute bottom-20 left-20 w-80 h-80 bg-blue-100/30 rounded-full blur-3xl -z-10"></div>

    <div class="mx-auto max-w-7xl px-6 lg:px-8 relative z-10 w-full py-12 lg:py-0">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        
        <!-- Left Content -->
        <div class="text-center lg:text-left">
          <div class="inline-flex items-center gap-2 rounded-full bg-indigo-100 px-5 py-2 text-sm font-bold text-indigo-700 mb-8 tracking-wide uppercase">
            🚀 Platform Beasiswa Cerdas
          </div>
          
          <h1 class="text-5xl font-[900] tracking-tight text-slate-900 sm:text-7xl leading-[1.1] mb-8">
            Raih Pendidikan <br> Terbaikmu dengan <span class="text-indigo-600">Beasiswa.</span>
          </h1>
          
          <p class="text-xl leading-relaxed text-slate-600 max-w-xl mx-auto lg:mx-0 mb-12">
            ScholarBot membantu ribuan mahasiswa menemukan peluang studi global melalui teknologi AI tercanggih. Mulai pencarianmu hari ini.
          </p>

          <!-- Clean Search Bar -->
          <div class="max-w-2xl mx-auto lg:mx-0">
            <form action="{{ route('scholarship') }}" method="GET" 
                  class="flex flex-col gap-3 sm:flex-row bg-white p-3 rounded-2xl shadow-[0_30px_60px_-15px_rgba(99,102,241,0.25)] border border-slate-100">
              <div class="relative flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" placeholder="Mau kuliah dimana?" 
                       class="w-full rounded-xl border-none bg-transparent py-4 pl-12 pr-4 text-lg text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0" />
              </div>
              <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-10 py-5 text-lg font-bold text-white shadow-lg hover:bg-indigo-700 hover:-translate-y-1 transition-all duration-300">
                Temukan Sekarang
              </button>
            </form>
            
            <div class="mt-10 flex flex-wrap items-center justify-center lg:justify-start gap-8">
              <a href="{{ route('chatbot') }}" class="inline-flex items-center gap-2 text-lg font-bold text-slate-900 hover:text-indigo-600 transition-colors group">
                <span>🤖 Tanya AI Chatbot</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
              </a>
              <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
              <p class="text-sm font-semibold text-slate-400 tracking-widest uppercase">Trusted by 10k+ Students</p>
            </div>
          </div>
        </div>

        <!-- Right Content: Educa Style Group Image -->
        <div class="relative flex justify-center items-center mt-12 lg:mt-0">
          <!-- Educa Circular Backdrop -->
          <div class="absolute w-[90%] h-[90%] bg-indigo-600 rounded-full animate-pulse-slow -z-10 opacity-10"></div>
          <div class="absolute w-[80%] h-[80%] border-2 border-dashed border-indigo-200 rounded-full animate-spin-slow -z-10"></div>
          
          <div class="relative z-10 w-full max-w-[550px] animate-float">
            <img src="{{ asset('students_group.png') }}" 
                 alt="Students Group" 
                 class="w-full h-auto drop-shadow-[0_20px_50px_rgba(0,0,0,0.1)] pointer-events-none">
          </div>

          <!-- Decorative floating elements -->
          <div class="absolute top-10 right-10 bg-white p-4 rounded-2xl shadow-xl animate-bounce-slow">
            <span class="text-2xl">🎓</span>
          </div>
          <div class="absolute bottom-20 left-0 bg-white p-4 rounded-2xl shadow-xl animate-bounce-slow" style="animation-delay: 1s;">
            <span class="text-2xl">📖</span>
          </div>
        </div>

      </div>
    </div>
  </section>

  <style>
    @keyframes spin-slow {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    .animate-spin-slow {
      animation: spin-slow 20s linear infinite;
    }
    @keyframes pulse-slow {
      0%, 100% { transform: scale(1); opacity: 0.1; }
      50% { transform: scale(1.1); opacity: 0.15; }
    }
    .animate-pulse-slow {
      animation: pulse-slow 8s ease-in-out infinite;
    }
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-20px); }
    }
    .animate-float {
      animation: float 6s ease-in-out infinite;
    }
    @keyframes bounce-slow {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    .animate-bounce-slow {
      animation: bounce-slow 4s ease-in-out infinite;
    }
  </style>

  <style>
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-20px); }
    }
    .animate-float {
      animation: float 6s ease-in-out infinite;
    }
  </style>

  <!-- Full Width Featured Scholarships -->
  <section class="w-full py-24 flex-grow relative z-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold tracking-tight text-gray-800 sm:text-4xl">Beasiswa Pilihan 🎓</h2>
        <p class="mt-4 text-lg text-gray-500">Jelajahi peluang beasiswa terbaik yang kami rekomendasikan untukmu.</p>
        <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($featuredScholarships as $item)
        <a href="{{ route('scholarship.detail', $item->id) }}" class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-md shadow-slate-200/50 border border-slate-100 transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-500/10">
          <div class="relative h-48 overflow-hidden bg-indigo-50 flex items-center justify-center text-indigo-200">
            <img src="https://source.unsplash.com/featured/800x600?education,university,college,lecturer&sig={{ $item->id }}" 
                 alt="{{ $item->nama_beasiswa }}" 
                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                 onerror="this.src='https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=800&auto=format&fit=crop'" />
            <div class="absolute top-4 left-4 flex gap-2">
              <span class="rounded-md bg-emerald-500/90 backdrop-blur px-2.5 py-1 text-[10px] font-bold text-white uppercase tracking-tight">Fully Funded</span>
            </div>
          </div>
          <div class="flex flex-1 flex-col p-6">
            <span class="mb-2 text-[10px] font-bold uppercase tracking-wider text-indigo-600">{{ str_contains(strtolower($item->negara), 'indonesia') ? 'Domestic' : 'International' }}</span>
            <h3 class="text-lg font-bold text-slate-900 line-clamp-2 h-14 leading-tight">{{ $item->nama_beasiswa }}</h3>
            <p class="mt-2 text-sm text-slate-500 line-clamp-1 font-medium">{{ $item->negara }}</p>
            <div class="mt-auto pt-6 flex items-center justify-between text-xs font-medium text-slate-400">
              <div class="flex items-center gap-1.5 font-bold text-indigo-500 bg-indigo-50 px-2 py-1 rounded">
                {{ $item->jenjang }}
              </div>
              <div class="flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                {{ $item->deadline ?? 'TBA' }}
              </div>
            </div>
          </div>
        </a>
        @endforeach
      </div>      </div>

      <div class="mt-12 text-center">
        <a href="{{ route('scholarship') }}" class="inline-flex rounded-xl bg-indigo-50 px-8 py-3 text-sm font-semibold text-indigo-600 hover:bg-indigo-100 transition-all">Jelajahi Semua Beasiswa</a>
      </div>
    </div>
  </section>

  <!-- Full Width Stats -->
  <section class="w-full py-20 flex-grow-0 relative z-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-8 divide-y divide-slate-200 md:grid-cols-3 md:divide-x md:divide-y-0 text-center">
        <div class="px-6 py-4 flex flex-col items-center">
          <svg class="h-10 w-10 text-indigo-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
          <p class="text-5xl font-extrabold text-indigo-600">1000+</p>
          <p class="mt-3 text-lg font-medium text-slate-500">Informasi Beasiswa</p>
        </div>
        <div class="px-6 py-4 flex flex-col items-center">
          <svg class="h-10 w-10 text-indigo-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <p class="text-5xl font-extrabold text-indigo-600">50+</p>
          <p class="mt-3 text-lg font-medium text-slate-500">Negara Tujuan</p>
        </div>
        <div class="px-6 py-4 flex flex-col items-center">
          <svg class="h-10 w-10 text-indigo-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <p class="text-5xl font-extrabold text-indigo-600">Daily</p>
          <p class="mt-3 text-lg font-medium text-slate-500">Update Berkala</p>
        </div>
      </div>
    </div>
  </section>

  <footer class="w-full bg-[#FFFFFF] py-16 text-gray-500 flex-grow-0 border-t border-slate-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-12 md:grid-cols-3 md:gap-8">
        <div>
          <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-gray-800">
            <span class="text-2xl font-bold tracking-tight">🤖ScholarBot</span>
          </a>
          <p class="mt-4 text-sm leading-relaxed text-gray-500 max-w-xs">
            Platform pintar untuk menemukan beasiswa yang tepat untuk masa depanmu.
          </p>
        </div>
        
        <div>
          <h3 class="text-sm font-bold tracking-wider text-gray-800 uppercase">Navigation</h3>
          <div class="mt-4 flex flex-col gap-3 text-sm font-medium">
            <a href="{{ url('/') }}" class="text-blue-600 transition-colors">Home</a>
            <a href="{{ route('scholarship') }}" class="hover:text-blue-600 transition-colors">Scholarships</a>
            <a href="{{ route('chatbot') }}" class="hover:text-blue-600 transition-colors">Chatbot</a>
            <a href="{{ route('bookmarks') }}" class="hover:text-blue-600 transition-colors">Saved</a>
          </div>
        </div>
        
        <div>
          <h3 class="text-sm font-bold tracking-wider text-gray-800 uppercase">Mulai Sekarang</h3>
          <p class="mt-4 text-sm text-gray-500 mb-4 font-medium">Punya pertanyaan soal beasiswa? Tanya AI sekarang.</p>
          <a href="{{ route('chatbot') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-600 transition-all">
            Tanya AI
          </a>
        </div>
      </div>
      <div class="mt-16 border-t border-slate-200 pt-8 flex items-center justify-between">
        <p class="text-sm text-gray-400">© 2026 ScholarBot. All rights reserved.</p>
      </div>
    </div>
  </footer>
</body>
</html>
