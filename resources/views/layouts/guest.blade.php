@props([
    'leftTitle' => 'Temukan Beasiswa Impianmu',
    'leftDescription' => 'Akses ribuan peluang beasiswa dari seluruh dunia dengan bantuan AI chatbot kami.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1">
  <meta name="csrf-token"
        content="{{ csrf_token() }}">

  <title>ScholarBot</title>

  <!-- Fonts -->
  <link rel="preconnect"
        href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
        rel="stylesheet" />

  <!-- Scripts -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-slate-900 antialiased bg-slate-100">
  <div class="min-h-screen flex flex-col md:flex-row">
    <aside class="hidden md:flex md:w-1/2 bg-gradient-to-br from-indigo-500 to-violet-600 text-white p-10 xl:p-14 flex-col justify-between">
      <a href="{{ url('/') }}"
         class="inline-flex items-center gap-3">
        <span class="text-3xl font-semibold leading-none">🤖ScholarBot</span>
      </a>

      <div class="max-w-md">
        <h1 class="text-4xl xl:text-5xl font-bold leading-tight">{{ $leftTitle }}</h1>
        <p class="mt-6 text-xl text-indigo-100 leading-relaxed">{{ $leftDescription }}</p>
      </div>

      <div class="text-indigo-100 text-sm flex flex-wrap gap-8">
        <span>© {{ now()->year }} ScholarBot</span>
      </div>
    </aside>

    <main class="w-full md:w-1/2 bg-slate-100/80 flex items-center justify-center p-6 sm:p-10">
      <div class="w-full max-w-[28rem] rounded-2xl border border-slate-200 bg-white p-7 sm:p-8 shadow-xl shadow-slate-200/60">
        {{ $slot }}
      </div>
    </main>
  </div>
</body>

</html>
