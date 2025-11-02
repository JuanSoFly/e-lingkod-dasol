<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Success Indicator Access Restricted
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">
                    <svg class="mx-auto h-16 w-16 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Access Required</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ $message ?? 'You need an OPCR management assignment to configure success indicators. Coordinate with the HRMO.' }}</p>
                    <div class="mt-6">
                        <a href="{{ route('opcr.mfos.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Return to MFOs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
