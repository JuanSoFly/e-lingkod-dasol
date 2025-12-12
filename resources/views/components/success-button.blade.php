<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center btn-responsive btn-touch bg-gray-800 border border-transparent rounded-lg font-medium text-white shadow-sm hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 ease-in-out']) }}>
    {{ $slot }}
</button>
