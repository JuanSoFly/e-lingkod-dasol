<x-guest-layout>
    <div class="mb-7">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Account recovery</p>
        <h2 class="mt-3 text-2xl font-semibold leading-8 text-slate-950">Reset your password</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Enter the email address linked to your HRIS account. We will send a secure reset link if the account can receive password recovery email.
        </p>
    </div>

    <x-auth-session-status class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
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
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <button
                type="submit"
                class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-lg border border-amber-700 bg-amber-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2 active:bg-amber-900"
            >
                <span>{{ __('Send reset link') }}</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 7.5v9A2.25 2.25 0 0119.5 18.75h-15A2.25 2.25 0 012.25 16.5v-9m19.5 0A2.25 2.25 0 0019.5 5.25h-15A2.25 2.25 0 002.25 7.5m19.5 0l-8.625 5.308a2.25 2.25 0 01-2.25 0L2.25 7.5" />
                </svg>
            </button>

            <div class="border-t border-slate-200 pt-5 text-center">
                <a class="text-sm font-medium text-amber-700 underline-offset-4 hover:text-amber-900 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" href="{{ route('login') }}">
                    {{ __('Back to log in') }}
                </a>
            </div>
        </div>
    </form>
</x-guest-layout>
