<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $scholarship['title'] ?? 'Scholarship Detail' }} - ScholarBot</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="font-sans bg-[#F8FAFC] text-gray-800 antialiased flex flex-col min-h-screen relative">
  <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur-xl shadow-sm">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <a href="{{ url('/') }}" class="flex items-center gap-2.5">
        <span class="text-2xl font-bold text-gray-800 tracking-tight">🤖ScholarBot</span>
      </a>

      <nav class="hidden items-center gap-8 text-sm font-semibold md:flex text-gray-500">
        <a href="{{ url('/') }}" class="hover:text-blue-600 transition-colors">Home</a>
        <a href="{{ route('scholarship') }}" class="text-blue-600">Scholarships</a>
        <a href="{{ route('chatbot') }}" class="hover:text-blue-600 transition-colors">Chatbot</a>
        <a href="{{ route('bookmarks') }}" class="hover:text-blue-600 transition-colors">Saved</a>
      </nav>

      <div>
        @auth
          <div x-data="{ open: false }" class="relative">
            <button x-on:click="open = !open" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white shadow-md hover:bg-slate-800 transition-all">
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
          <a href="{{ route('login') }}" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition-all">Login</a>
        @endauth
      </div>
    </div>
  </header>

  <main class="flex-grow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <a href="{{ route('scholarship') }}" class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 mb-8 transition-colors font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Scholarships
      </a>

      @if(!$scholarship)
        <div class="flex flex-col items-center justify-center py-20">
          <div class="text-6xl mb-4">🔍</div>
          <h2 class="text-2xl font-bold text-gray-800 mb-2">Scholarship not found</h2>
          <a href="{{ route('scholarship') }}" class="text-blue-600 hover:underline">Browse all scholarships</a>
        </div>
      @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          <!-- Main Content -->
          <div class="lg:col-span-2 space-y-6">
            <div class="bg-[#F1F5F9] rounded-2xl overflow-hidden shadow-sm border border-slate-200">
              
              <!-- Banner Image -->
              <div class="relative aspect-[21/9] overflow-hidden bg-indigo-100 flex items-center justify-center text-indigo-300">
                <img src="https://picsum.photos/seed/detail-{{ $scholarship->id }}/1200/600" 
                     alt="{{ $scholarship->nama_beasiswa }}" 
                     class="w-full h-full object-cover" 
                     onerror="this.src='https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1200&auto=format&fit=crop'" />
                <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                  <span class="px-4 py-2 bg-green-500 text-white rounded-full text-sm font-medium shadow-sm">
                    Fully Funded
                  </span>
                  <span class="px-4 py-2 bg-indigo-500 text-white rounded-full text-sm font-medium shadow-sm">
                    {{ str_contains(strtolower($scholarship->negara), 'indonesia') ? 'Domestic' : 'International' }}
                  </span>
                </div>
              </div>

              <!-- Content Body -->
              <div class="p-8 bg-white">
                <h1 class="text-3xl font-extrabold text-gray-800 mb-3 leading-tight">{{ $scholarship->nama_beasiswa }}</h1>
                <p class="text-xl text-gray-500 mb-6 font-medium">{{ $scholarship->negara }}</p>

                <div class="flex flex-wrap items-center gap-6 text-gray-500 mb-8 pb-8 border-b border-slate-200">
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ $scholarship->negara }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Deadline: {{ $scholarship->deadline ?? 'TBA' }}</span>
                  </div>
                  <div class="px-4 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-sm font-bold border border-blue-100">
                    {{ $scholarship->jenjang }}
                  </div>
                </div>

                <div class="prose max-w-none text-lg">
                  <h2 class="text-2xl font-bold text-gray-800 mb-4">Description</h2>
                  <div class="text-gray-600 leading-relaxed mb-10">{!! nl2br(e($scholarship->deskripsi)) !!}</div>

                  @if($scholarship->jurusan)
                  <div class="bg-[#F8FAFC] rounded-2xl p-6 mb-10 border border-slate-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Majors / Bidang</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach(explode(',', $scholarship->jurusan) as $major)
                          <span class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-gray-700 shadow-sm">
                            {{ trim($major) }}
                          </span>
                        @endforeach
                    </div>
                  </div>
                  @endif

                  @if($scholarship->persyaratan)
                  <h2 class="text-2xl font-bold text-gray-800 mb-4">Requirements</h2>
                  <div class="text-gray-600 mb-10 leading-relaxed">
                    {!! nl2br(e($scholarship->persyaratan)) !!}
                  </div>
                  @endif

                  @if($scholarship->benefit)
                  <h2 class="text-2xl font-bold text-gray-800 mb-4">Benefits</h2>
                  <div class="text-gray-600 leading-relaxed mb-10">
                    {!! nl2br(e($scholarship->benefit)) !!}
                  </div>
                  @endif

                  <h2 class="text-2xl font-bold text-gray-800 mb-4 mt-10">🌐 Scholarship Links</h2>
                  <div class="bg-blue-50/50 rounded-2xl p-6 border border-blue-100 mb-8">
                    <ul class="space-y-4">
                      @if($scholarship->url)
                      <li>
                        <a href="{{ $scholarship->url }}" target="_blank" class="flex items-center gap-3 text-blue-600 hover:text-blue-700 font-medium group transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                          </svg>
                          <span class="group-hover:underline">Official Scholarship Website</span>
                        </a>
                      </li>
                      @endif
                      @if($scholarship->url_asli)
                      <li>
                        <a href="{{ $scholarship->url_asli }}" target="_blank" class="flex items-center gap-3 text-blue-600 hover:text-blue-700 font-medium group transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                          </svg>
                          <span class="group-hover:underline">Original Source Link</span>
                        </a>
                      </li>
                      @endif
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar (Quick Actions) -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 sticky top-24">
              <h3 class="font-bold text-xl text-gray-800 mb-6">Quick Actions</h3>
              
              @auth
                <form action="{{ route('bookmarks.toggle', $scholarship->id) }}" method="POST">
                  @csrf
                  <button type="submit" 
                          class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-semibold transition-all mb-6 {{ $isBookmarked ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm shadow-indigo-100' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $isBookmarked ? 'fill-current' : 'fill-none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span>{{ $isBookmarked ? 'Saved to Bookmarks' : 'Save Scholarship' }}</span>
                  </button>
                </form>
              @else
                <a href="{{ route('login') }}" 
                   class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all mb-6">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  <span>Login to Save</span>
                </a>
              @endauth

              <div class="pt-6 border-t border-slate-100">
                <h4 class="font-bold text-sm text-gray-400 uppercase tracking-wider mb-4">Scholarship Details</h4>
                <div class="space-y-5">
                  <div class="pb-3 border-b border-slate-50">
                    <div class="text-[11px] font-bold text-gray-400 uppercase mb-1">Continent</div>
                    <div class="font-bold text-gray-800 leading-tight">{{ $scholarship->benua ?? '-' }}</div>
                  </div>
                  <div class="pb-3 border-b border-slate-50">
                    <div class="text-[11px] font-bold text-gray-400 uppercase mb-1">Country</div>
                    <div class="font-bold text-gray-800 leading-tight">{{ $scholarship->negara }}</div>
                  </div>
                  <div class="pb-3 border-b border-slate-50">
                    <div class="text-[11px] font-bold text-gray-400 uppercase mb-1">Degree</div>
                    <div class="font-bold text-gray-800">{{ $scholarship->jenjang }}</div>
                  </div>
                  <div>
                    <div class="text-[11px] font-bold text-gray-400 uppercase mb-1">Funding</div>
                    <div class="font-bold text-green-600">Fully Funded</div>
                  </div>
                </div>
              </div>
              

            </div>
          </div>

        </div>
      @endif
    </div>
  </main>

  <footer class="w-full bg-[#FFFFFF] py-16 text-gray-500 flex-grow-0 border-t border-slate-200 mt-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-12 md:grid-cols-3 md:gap-8">
        <div>
          <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            <span class="text-2xl font-bold text-gray-800 tracking-tight">🤖ScholarBot</span>
          </a>
          <p class="mt-4 text-sm leading-relaxed text-gray-500 max-w-xs">
            Platform pintar untuk menemukan beasiswa yang tepat untuk masa depanmu.
          </p>
        </div>
        
        <div>
          <h3 class="text-sm font-bold tracking-wider text-gray-800 uppercase">Navigation</h3>
          <div class="mt-4 flex flex-col gap-3 text-sm font-medium">
            <a href="{{ url('/') }}" class="hover:text-blue-600 transition-colors">Home</a>
            <a href="{{ route('scholarship') }}" class="text-blue-600">Scholarships</a>
            <a href="{{ route('chatbot') }}" class="hover:text-blue-600 transition-colors">Chatbot</a>
            <a href="{{ route('bookmarks') }}" class="hover:text-blue-600 transition-colors">Saved</a>
          </div>
        </div>
        
        <div>
          <h3 class="text-sm font-bold tracking-wider text-gray-800 uppercase">Mulai Sekarang</h3>
          <p class="mt-4 text-sm text-gray-500 mb-4 font-medium">Punya pertanyaan soal beasiswa? Tanya AI sekarang.</p>
          <a href="{{ route('chatbot') }}" class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 transition-all">
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
