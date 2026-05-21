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
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 no-overflow-x">
                    @isset($slot)
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endisset
                </div>
            </main>
        </div>

        <!-- Global Confirmation Modal -->
        <x-confirm-dialog />

        <!-- PDS/Employee Edit Modal -->
        <div id="pds-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div id="pds-modal-backdrop" class="fixed inset-0 bg-gray-900/60 transition-opacity duration-300 ease-out opacity-0" aria-hidden="true"></div>

            <!-- Modal Positioning Wrapper -->
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-stretch lg:items-center lg:justify-center lg:p-4">
                    <!-- Modal Panel -->
                    <div id="pds-modal-panel" class="relative w-full bg-white transform transition-all duration-300 ease-out translate-y-4 opacity-0 lg:translate-y-0 lg:scale-95 lg:my-8 lg:max-w-5xl lg:w-full lg:rounded-xl lg:shadow-2xl lg:border lg:border-gray-100">
                        <!-- Modal Header -->
                        <div class="sticky top-0 z-10 bg-gray-50 border-b border-gray-200 px-4 py-3 sm:px-6 sm:py-4 flex items-center justify-between">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate pr-4" id="pds-modal-title">
                                Edit Details
                            </h3>
                            <button type="button" id="pds-modal-close" class="flex-shrink-0 text-gray-400 hover:text-gray-600 focus:outline-none p-1.5 hover:bg-gray-100 rounded-lg transition-colors">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Content Body -->
                        <div class="px-4 py-4 sm:px-6 sm:py-6 overflow-y-auto" id="pds-modal-body">
                            <!-- Loader -->
                            <div id="pds-modal-loader" class="flex flex-col items-center justify-center py-12">
                                <svg class="animate-spin h-10 w-10 text-blue-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm text-gray-500 font-medium">Loading form content...</span>
                            </div>

                            <!-- Container for AJAX HTML -->
                            <div id="pds-modal-form-container" class="hidden"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scripts Stack -->
        @stack('scripts')
    </body>
</html>
