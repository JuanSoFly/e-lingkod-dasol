<x-guest-layout>
    <div class="mb-7">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Welcome back</p>
        <h2 class="mt-3 text-2xl font-semibold leading-8 text-slate-950">Sign in to Dasol HRIS</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Use your authorized municipal account to continue.
        </p>
    </div>

    <x-auth-session-status class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" :status="session('status')" />

    @if (session('info'))
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
            {{ session('info') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="space-y-5">
            <div>
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="email" :value="__('Email address')" />
                    <span class="text-xs font-medium text-slate-400">Required</span>
                </div>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21.75 7.5v9A2.25 2.25 0 0119.5 18.75h-15A2.25 2.25 0 012.25 16.5v-9m19.5 0A2.25 2.25 0 0019.5 5.25h-15A2.25 2.25 0 002.25 7.5m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0l-7.5-4.615a2.25 2.25 0 01-1.07-1.916V7.5" />
                        </svg>
                    </span>
                    <input
                        id="email"
                        class="block w-full rounded-lg border-slate-300 bg-white py-3 pl-11 pr-3 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-amber-500 focus:ring-amber-500"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="you@example.com"
                    />
                </div>
                <p class="mt-2 text-xs leading-5 text-slate-500">Use the email address assigned to your HRIS account.</p>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div x-data="{ showPassword: false }">
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="password" :value="__('Password')" />
                    @if (Route::has('password.request'))
                        <a class="text-sm font-medium text-amber-700 underline-offset-4 hover:text-amber-900 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" href="{{ route('password.request') }}">
                            {{ __('Forgot?') }}
                        </a>
                    @endif
                </div>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V7.875a4.5 4.5 0 00-9 0V10.5m-.75 10.125h10.5A2.625 2.625 0 0019.875 18v-4.875A2.625 2.625 0 0017.25 10.5H6.75a2.625 2.625 0 00-2.625 2.625V18a2.625 2.625 0 002.625 2.625z" />
                        </svg>
                    </span>
                    <input
                        id="password"
                        class="block w-full rounded-lg border-slate-300 bg-white py-3 pl-11 pr-20 text-base text-slate-900 shadow-sm transition focus:border-amber-500 focus:ring-amber-500"
                        type="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-1.5 right-1.5 inline-flex items-center rounded-md px-3 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        x-on:click="showPassword = ! showPassword"
                        x-bind:aria-pressed="showPassword.toString()"
                    >
                        <span x-text="showPassword ? 'Hide' : 'Show'">Show</span>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex flex-col gap-4 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-amber-600 shadow-sm focus:ring-amber-500" name="remember">
                    <span class="ms-2 text-sm text-slate-600">{{ __('Keep me signed in') }}</span>
                </label>

                <span class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">Authorized access only</span>
            </div>

            <button
                type="submit"
                class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-lg border border-amber-700 bg-amber-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2 active:bg-amber-900"
            >
                <span>{{ __('Log in') }}</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </button>

            @if (Route::has('register'))
                <p class="text-center text-sm text-slate-500">
                    New account requests are reviewed by HRMO before access is granted.
                </p>
            @endif
        </div>
    </form>
</x-guest-layout>
