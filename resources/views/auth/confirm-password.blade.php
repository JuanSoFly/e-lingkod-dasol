<x-guest-layout>
    <div class="mb-7">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Security check</p>
        <h2 class="mt-3 text-2xl font-semibold leading-8 text-slate-950">Confirm your password</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            This area contains protected HRIS information. Re-enter your password to continue.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="space-y-5">
            <div x-data="{ showPassword: false }">
                <x-input-label for="password" :value="__('Password')" />
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

            <button
                type="submit"
                class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-lg border border-amber-700 bg-amber-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2 active:bg-amber-900"
            >
                <span>{{ __('Confirm and continue') }}</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </button>
        </div>
    </form>
</x-guest-layout>
