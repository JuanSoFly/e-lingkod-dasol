@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center md:px-2 lg:px-3 py-2 md:text-xs lg:text-sm font-medium leading-5 text-indigo-700 bg-indigo-50 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-150 ease-in-out'
            : 'inline-flex items-center md:px-2 lg:px-3 py-2 md:text-xs lg:text-sm font-medium leading-5 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md focus:outline-none focus:text-gray-900 focus:bg-gray-50 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
