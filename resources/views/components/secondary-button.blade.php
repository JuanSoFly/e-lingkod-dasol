<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 ease-in-out']) }}>
    {{ $slot }}
</button>
