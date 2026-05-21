<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <!-- <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" /> -->

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="relative min-h-screen overflow-hidden bg-slate-950">
            <div
                class="absolute inset-0 bg-cover bg-center"
                style="background-image: image-set(url('/images/background/salt-making-process.webp') type('image/webp'), url('/images/background/salt-making-process.jpeg') type('image/jpeg'));"
                aria-hidden="true"
            ></div>
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950/95 via-slate-900/80 to-amber-950/50" aria-hidden="true"></div>
            <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-black/35 to-transparent" aria-hidden="true"></div>

            <main class="relative z-10 flex min-h-screen items-center px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto grid w-full max-w-7xl items-center gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(360px,460px)]">
                    <section class="hidden min-h-[680px] flex-col justify-between py-4 text-white lg:flex">
                        <a href="/" class="group inline-flex w-fit items-center gap-6 rounded-xl p-1.5 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950">
                            <x-application-logo class="h-32 w-32 shrink-0 rounded-full bg-white/5 p-1.5 border border-white/10 shadow-lg drop-shadow-2xl transition-all duration-500 group-hover:scale-105 group-hover:border-amber-400/30 group-hover:shadow-amber-500/10" />
                            <div class="flex flex-col justify-center gap-1">
                                <span class="block text-4xl lg:text-5xl font-extrabold tracking-tight text-white transition-colors duration-300 group-hover:text-amber-200">
                                    Municipality of Dasol
                                </span>
                                <span class="block text-sm uppercase tracking-[0.25em] text-slate-400 transition-colors duration-300 group-hover:text-amber-100/70">
                                    Pangasinan
                                </span>
                            </div>
                        </a>

                        <div class="max-w-2xl">
                            <p class="mb-4 inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-amber-100 shadow-sm backdrop-blur-sm">
                                Human Resource Information System
                            </p>
                            <h1 class="text-4xl font-semibold leading-tight text-white sm:text-5xl">
                                Secure access for municipal personnel.
                            </h1>
                            <p class="mt-5 max-w-xl text-base leading-7 text-slate-100">
                                Manage your Dasol HRIS account with a protected portal built for municipal workforce records, requests, and approvals.
                            </p>
                        </div>

                        <div class="grid max-w-2xl grid-cols-3 gap-3 text-sm">
                            <div class="rounded-lg border border-white/15 bg-white/10 p-4 shadow-sm backdrop-blur-sm">
                                <span class="block text-xs font-medium uppercase tracking-[0.18em] text-amber-100">Access</span>
                                <span class="mt-2 block font-semibold text-white">Authorized users</span>
                            </div>
                            <div class="rounded-lg border border-white/15 bg-white/10 p-4 shadow-sm backdrop-blur-sm">
                                <span class="block text-xs font-medium uppercase tracking-[0.18em] text-amber-100">Office</span>
                                <span class="mt-2 block font-semibold text-white">HRMO supported</span>
                            </div>
                            <div class="rounded-lg border border-white/15 bg-white/10 p-4 shadow-sm backdrop-blur-sm">
                                <span class="block text-xs font-medium uppercase tracking-[0.18em] text-amber-100">Session</span>
                                <span class="mt-2 block font-semibold text-white">Protected portal</span>
                            </div>
                        </div>
                    </section>

                    <section class="w-full max-w-[460px] justify-self-center lg:justify-self-end" aria-label="Authentication">
                        <div class="mb-6 flex items-center justify-center gap-5 text-white lg:hidden">
                            <x-application-logo class="h-24 w-24 shrink-0 rounded-full bg-white/5 p-1 border border-white/10 shadow-lg drop-shadow-2xl" />
                            <div class="flex flex-col justify-center gap-0.5 text-left">
                                <span class="block text-3xl font-extrabold tracking-tight text-white">
                                    Municipality of Dasol
                                </span>
                                <span class="block text-xs uppercase tracking-[0.22em] text-slate-400">
                                    Pangasinan
                                </span>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-lg border border-white/70 bg-white/95 p-6 shadow-2xl shadow-slate-950/30 backdrop-blur-sm sm:p-8">
                            {{ $slot }}
                        </div>

                        <p class="mt-5 text-center text-xs leading-5 text-slate-200">
                            For account access concerns, coordinate with the HRMO or your system administrator.
                        </p>
                    </section>
                </div>
            </main>
        </div>
    </body>
</html>
