@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full px-4 py-3 border-l-4 border-indigo-400 text-start text-base font-medium text-indigo-700 bg-indigo-50 rounded-r-lg focus:outline-none focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700 transition-all duration-200 ease-in-out'
            : 'block w-full px-4 py-3 border-l-4 border-transparent text-start text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 hover:border-gray-300 rounded-r-lg focus:outline-none focus:text-gray-900 focus:bg-gray-100 focus:border-gray-300 transition-all duration-200 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
