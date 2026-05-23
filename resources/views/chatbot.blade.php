<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1">
  <meta name="csrf-token"
        content="{{ csrf_token() }}">
  <title>Chatbot - ScholarBot</title>

  <link rel="preconnect"
        href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap"
        rel="stylesheet" />

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen relative">
  <!-- Global background pattern -->
  <div class="fixed inset-0 z-[-1] bg-gradient-to-br from-indigo-50 via-slate-50 to-purple-50"></div>
  <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
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
           class="rounded-full bg-indigo-50 px-3 py-1.5 text-indigo-600">Chatbot</a>
        <a href="{{ route('bookmarks') }}"
           class="rounded-full px-3 py-1.5 text-slate-500 hover:text-slate-700">Saved</a>
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

  <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <section class="grid min-h-[calc(100vh-8rem)] grid-cols-1 overflow-hidden rounded-2xl border border-slate-200 bg-white lg:grid-cols-12">
      <aside class="border-b border-slate-200 bg-slate-50 p-4 lg:col-span-3 lg:border-b-0 lg:border-r">
        <button onclick="clearSession()" class="mb-6 w-full rounded-xl bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-600 transition-all">✨ New Chat</button>

        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Chat History</h3>
        <div class="mt-3 space-y-4">
          <div>
            <p class="text-sm font-semibold text-slate-700">Fully funded S2 scholarships</p>
            <p class="text-xs text-slate-400">Today</p>
          </div>
          <div>
            <p class="text-sm font-semibold text-slate-700">Beasiswa luar negeri</p>
            <p class="text-xs text-slate-400">Yesterday</p>
          </div>
          <div>
            <p class="text-sm font-semibold text-slate-700">LPDP requirements</p>
            <p class="text-xs text-slate-400">Apr 15</p>
          </div>
        </div>
      </aside>

      <div class="flex flex-col lg:col-span-9">
        <div class="flex-1 p-4 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">✦</span>
            <div class="max-w-3xl rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
              Hello! I'm your scholarship assistant. I can help you find the perfect scholarship based on your preferences. What kind of scholarship are you looking for?
            </div>
          </div>

          <p class="mt-8 text-center text-sm text-slate-400">Quick suggestions</p>
          <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <button class="rounded-xl border border-slate-200 bg-white px-4 py-4 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">🌐 Beasiswa luar negeri</button>
            <button class="rounded-xl border border-slate-200 bg-white px-4 py-4 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">🎓 Fully funded S1</button>
            <button class="rounded-xl border border-slate-200 bg-white px-4 py-4 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">🕒 Deadline terdekat</button>
          </div>
        </div>

        <div class="border-t border-slate-200 bg-white p-4">
          <div class="mx-auto flex w-full max-w-3xl items-center gap-3">
            <input type="text"
                   placeholder="Ask about scholarships (e.g. fully funded S2 in Japan)"
                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
            <button class="rounded-xl bg-indigo-500 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-600 transition-all">Send</button>
          </div>
        </div>
      </div>
    </section>
  </main>

<script>
function clearSession() {
    fetch('/chatbot/clear', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    }).then(() => {
        // Reload halaman untuk reset tampilan chat
        window.location.reload();
    });
}
</script>
</body>

</html>
