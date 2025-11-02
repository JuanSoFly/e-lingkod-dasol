<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            OPCR Access Required
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Department Head Assignment Required</h3>
                        <p class="text-gray-600 mb-6">{{ $message }}</p>

                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                            <h4 class="text-sm font-medium text-blue-900 mb-2">Next Steps:</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Contact your HR Administrator to be assigned as Department Head</li>
                                <li>• Ensure your user account has the proper permissions</li>
                                <li>• Verify your office assignment in the system</li>
                            </ul>
                        </div>

                        <div class="flex justify-center space-x-4">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Return to Dashboard
                            </a>
                            @auth
                                @if(auth()->user()->hasAnyRole(['Super Admin', 'HR Admin']))
                                    <a href="{{ route('opcr.workflows.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        View All Workflows
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>