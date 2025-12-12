<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center btn-responsive btn-touch bg-red-600 border border-transparent rounded-lg font-medium text-white shadow-sm hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 ease-in-out']) }}>
    {{ $slot }}
</button>
