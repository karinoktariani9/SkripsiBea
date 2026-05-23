<x-guest-layout left-title="Temukan Beasiswa Impianmu"
                left-description="Akses ribuan peluang beasiswa dari seluruh dunia dengan bantuan AI chatbot kami.">
  <h1 class="text-4xl font-bold text-slate-800">Selamat Datang Kembali</h1>
  <p class="mt-2 text-lg text-slate-500">Masuk untuk melanjutkan pencarian beasiswa Anda</p>

  <x-auth-session-status class="mt-6 text-sm text-emerald-600"
                         :status="session('status')" />

  <form method="POST"
        action="{{ route('login') }}"
        class="mt-6 space-y-5">
    @csrf

    <div>
      <label for="email"
             class="block text-sm font-semibold text-slate-700">Email</label>
      <input id="email"
             name="email"
             type="email"
             value="{{ old('email') }}"
             required
             autofocus
             autocomplete="username"
             placeholder="nama@email.com"
             class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
      <x-input-error :messages="$errors->get('email')"
                     class="mt-2" />
    </div>

    <div x-data="{ show: false }">
      <label for="password"
             class="block text-sm font-semibold text-slate-700">Password</label>
      <div class="relative mt-2">
        <input id="password"
               name="password"
               x-bind:type="show ? 'text' : 'password'"
               required
               autocomplete="current-password"
               placeholder="Masukkan password"
               class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-12 text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
        <button type="button"
                x-on:click="show = !show"
                class="absolute inset-y-0 right-0 px-4 text-slate-400 hover:text-slate-600"
                aria-label="Toggle password visibility">
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-5 w-5"
               viewBox="0 0 20 20"
               fill="currentColor">
            <path d="M10 3c4.67 0 8.04 2.91 9.54 6.43a1.03 1.03 0 0 1 0 .74C18.04 13.69 14.67 16.6 10 16.6s-8.04-2.91-9.54-6.43a1.03 1.03 0 0 1 0-.74C1.96 5.91 5.33 3 10 3Zm0 2C6.44 5 3.83 7.1 2.5 9.8 3.83 12.5 6.44 14.6 10 14.6s6.17-2.1 7.5-4.8C16.17 7.1 13.56 5 10 5Zm0 1.6A3.2 3.2 0 1 1 6.8 9.8 3.2 3.2 0 0 1 10 6.6Zm0 2A1.2 1.2 0 1 0 11.2 9.8 1.2 1.2 0 0 0 10 8.6Z" />
          </svg>
        </button>
      </div>
      <x-input-error :messages="$errors->get('password')"
                     class="mt-2" />
    </div>

    <div class="flex justify-end">
      @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}"
           class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Lupa Password?</a>
      @endif
    </div>

    <button type="submit"
            class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-base font-semibold text-white shadow-lg shadow-indigo-200/70 hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-indigo-200">
      Masuk
    </button>
  </form>

  <div class="relative my-8">
    <div class="absolute inset-0 flex items-center">
      <div class="w-full border-t border-slate-200"></div>
    </div>
    <div class="relative flex justify-center text-sm">
      <span class="bg-white px-4 text-slate-500">atau</span>
    </div>
  </div>

  <p class="text-center text-sm text-slate-500">
    Belum punya akun?
    <a href="{{ route('register') }}"
       class="font-bold text-slate-900 hover:text-indigo-600 transition-colors">Daftar</a>
  </p>
</x-guest-layout>
