@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-2 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-indigo-600 bg-indigo-50 rounded-t-md focus:outline-none focus:border-indigo-700 transition-all duration-200 ease-in-out'
            : 'inline-flex items-center px-3 py-2 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-600 hover:text-gray-800 hover:border-gray-300 hover:bg-gray-50 rounded-t-md focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
