<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Civil Service Eligibility') }} - {{ $employee->full_name }}
            </h2>
            <a href="{{ route('pds.dashboard', $employee) }}">
                <x-secondary-button>
                    {{ __('Back to PDS Dashboard') }}
                </x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Add New Eligibility Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Add New Civil Service Eligibility</h3>
                    
                    <form method="POST" action="{{ route('pds.store-eligibility', $employee) }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Eligibility Name -->
                            <div>
                                <label for="eligibility_name" class="block text-sm font-medium text-gray-700">Eligibility Name *</label>
                                <input type="text" name="eligibility_name" id="eligibility_name" value="{{ old('eligibility_name') }}" required 
                                       placeholder="e.g. Career Service Professional, RA 1080"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Rating -->
                            <div>
                                <label for="rating" class="block text-sm font-medium text-gray-700">Rating (%)</label>
                                <input type="number" name="rating" id="rating" value="{{ old('rating') }}" 
                                       min="0" max="100" step="0.01" placeholder="e.g. 85.50"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Date of Examination -->
                            <div>
                                <label for="date_of_examination" class="block text-sm font-medium text-gray-700">Date of Examination</label>
                                <input type="date" name="date_of_examination" id="date_of_examination" value="{{ old('date_of_examination') }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Place of Examination -->
                            <div>
                                <label for="place_of_examination" class="block text-sm font-medium text-gray-700">Place of Examination</label>
                                <input type="text" name="place_of_examination" id="place_of_examination" value="{{ old('place_of_examination') }}" 
                                       placeholder="e.g. Manila, Philippines"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- License Number -->
                            <div>
                                <label for="license_number" class="block text-sm font-medium text-gray-700">License Number</label>
                                <input type="text" name="license_number" id="license_number" value="{{ old('license_number') }}" 
                                       placeholder="License/Certificate Number"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Date of Validity -->
                            <div>
                                <label for="date_of_validity" class="block text-sm font-medium text-gray-700">Date of Validity</label>
                                <input type="date" name="date_of_validity" id="date_of_validity" value="{{ old('date_of_validity') }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                {{ __('Add Eligibility') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Eligibilities -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Current Civil Service Eligibilities</h3>
                    
                    @if($eligibilities && $eligibilities->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Eligibility Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Rating
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date of Examination
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Place of Examination
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            License Number
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Validity
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($eligibilities as $eligibility)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $eligibility->eligibility_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($eligibility->rating)
                                                    {{ number_format($eligibility->rating, 2) }}%
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $eligibility->date_of_examination?->format('M d, Y') ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $eligibility->place_of_examination ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $eligibility->license_number ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($eligibility->date_of_validity)
                                                    {{ $eligibility->date_of_validity->format('M d, Y') }}
                                                    @if($eligibility->date_of_validity->isPast())
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">
                                                            Expired
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                                            Valid
                                                        </span>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <form method="POST" action="{{ route('pds.destroy-eligibility', [$employee, $eligibility]) }}" 
                                                      data-confirm="Are you sure you want to delete this eligibility?" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Civil Service Eligibilities</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                You haven't added any civil service eligibilities yet. Add your first eligibility using the form above.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Information Panel -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Information</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Civil Service eligibilities are crucial for government employment. Common eligibilities include:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li><strong>Career Service Professional</strong> - For professional positions</li>
                                <li><strong>Career Service Subprofessional</strong> - For subprofessional positions</li>
                                <li><strong>RA 1080 (Bar/Board)</strong> - For licensed professionals</li>
                                <li><strong>Honor Graduate</strong> - For academic achievers</li>
                                <li><strong>PBET</strong> - Philippine Basic Education Test</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
