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

        <!-- FontAwesome CDN -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-50 no-overflow-x">
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow-sm border-b border-gray-200">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center justify-between">
                            {{ $header }}
                        </div>
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 bg-gray-50 no-overflow-x">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>

         <!-- Global Confirmation Modal -->
        <x-confirm-dialog />

        @if(session('welcome'))
            <!-- Onboarding Welcome Modal -->
            <div x-data="{ open: true }" x-show="open" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
                <div x-show="open" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity"></div>

                <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div x-show="open" 
                             x-transition:enter="ease-out duration-300" 
                             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                             x-transition:leave="ease-in duration-200" 
                             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                             class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
                            
                            <div class="p-6 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 text-white shadow-lg shadow-indigo-200">
                                    <i class="fa-solid fa-circle-check text-3xl"></i>
                                </div>
                                <h3 class="mt-5 text-2xl font-bold tracking-tight text-slate-900" id="modal-title">Welcome to E-Lingkod Dasol!</h3>
                                <p class="mt-2 text-sm text-slate-600">Your account has been successfully activated. We are excited to have you on board!</p>
                            </div>

                            <div class="bg-slate-50/50 border-y border-slate-100 px-6 py-5 text-left space-y-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Quick Tour of Your Features</p>
                                
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700 border border-amber-100/50">
                                            <i class="fa-solid fa-id-card text-sm"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-950">Personal Data Sheet (PDS)</h4>
                                        <p class="text-xs text-slate-600 mt-0.5">Fill out and update your official PDS profile, family background, and educational history.</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700 border border-blue-100/50">
                                            <i class="fa-solid fa-calendar-check text-sm"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-950">Leave Management</h4>
                                        <p class="text-xs text-slate-600 mt-0.5">Apply for leaves online, check your earned credit balance, and track application approval status.</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100/50">
                                            <i class="fa-solid fa-folder-open text-sm"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-950">Digital 201 File & Record</h4>
                                        <p class="text-xs text-slate-600 mt-0.5">Access your official employee documents, service record, and training history anytime.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6">
                                <button type="button" 
                                        @click="open = false" 
                                        class="inline-flex w-full min-h-[48px] items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-100 transition hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <span>Get Started</span>
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Scripts Stack -->
        @stack('scripts')
    </body>
</html>
