<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-amber-600 border border-amber-600 rounded-lg font-medium text-sm text-white hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1 focus:ring-offset-amber-100 disabled:opacity-60 disabled:cursor-not-allowed transition-colors duration-200 ease-in-out']) }}>
    {{ $slot }}
</button>
