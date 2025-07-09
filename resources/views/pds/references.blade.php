<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Character References') }} - {{ $employee->full_name }}
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

            <!-- Progress Indicator -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Reference Progress</h3>
                        <span class="text-sm text-gray-600">{{ $references ? $references->count() : 0 }} of 3 minimum references</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        @php
                            $count = $references ? $references->count() : 0;
                            $percentage = min(($count / 3) * 100, 100);
                        @endphp
                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                    </div>
                    @if($count < 3)
                        <p class="text-sm text-orange-600 mt-2">
                            <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            You need {{ 3 - $count }} more reference(s) to meet the minimum requirement.
                        </p>
                    @else
                        <p class="text-sm text-green-600 mt-2">
                            <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Great! You have met the minimum reference requirement.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Add New Reference Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Add New Character Reference</h3>
                    
                    <form method="POST" action="{{ route('pds.store-reference', $employee) }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div class="md:col-span-2">
                                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                                <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required 
                                       placeholder="e.g. Dr. Juan Dela Cruz"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700">Complete Address *</label>
                                <textarea name="address" id="address" rows="3" required
                                          placeholder="Complete address including barangay, city/municipality, and province"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('address') }}</textarea>
                            </div>

                            <!-- Telephone Number -->
                            <div>
                                <label for="telephone_no" class="block text-sm font-medium text-gray-700">Telephone Number</label>
                                <input type="text" name="telephone_no" id="telephone_no" value="{{ old('telephone_no') }}" 
                                       placeholder="e.g. (02) 8123-4567 or 09171234567"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                {{ __('Add Reference') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing References -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Current Character References</h3>
                    
                    @if($references && $references->count() > 0)
                        <div class="space-y-4">
                            @foreach($references as $index => $reference)
                                <div class="border border-gray-200 rounded-lg p-6">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-600 text-sm font-medium mr-3">
                                                    {{ $index + 1 }}
                                                </span>
                                                <h4 class="text-lg font-medium text-gray-900">{{ $reference->full_name }}</h4>
                                            </div>
                                            
                                            <div class="ml-11 space-y-2">
                                                <div class="flex items-start">
                                                    <svg class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-sm text-gray-600">{{ $reference->address }}</span>
                                                </div>
                                                
                                                @if($reference->telephone_no)
                                                    <div class="flex items-center">
                                                        <svg class="h-5 w-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                                        </svg>
                                                        <span class="text-sm text-gray-600">{{ $reference->telephone_no }}</span>
                                                    </div>
                                                @else
                                                    <div class="flex items-center">
                                                        <svg class="h-5 w-5 text-gray-300 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                                        </svg>
                                                        <span class="text-sm text-gray-400 italic">No telephone number provided</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="ml-4">
                                            <form method="POST" action="{{ route('pds.destroy-reference', [$employee, $reference]) }}" 
                                                  onsubmit="return confirm('Are you sure you want to delete this reference?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Character References</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                You haven't added any character references yet. Add your first reference using the form above.
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
                        <h3 class="text-sm font-medium text-blue-800">Guidelines for Character References</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p class="mb-2">Character references should be people who know you well professionally or personally and can vouch for your character and work ethic. Consider including:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Former supervisors or colleagues</strong> - People who have worked with you directly</li>
                                <li><strong>Community leaders</strong> - Religious leaders, community officials, or organization heads</li>
                                <li><strong>Professional peers</strong> - Members of professional associations or business contacts</li>
                                <li><strong>Long-time acquaintances</strong> - People who have known you for several years</li>
                            </ul>
                            <div class="mt-3 p-3 bg-blue-100 rounded-md">
                                <p class="text-xs font-medium text-blue-800">Important Notes:</p>
                                <ul class="text-xs text-blue-700 mt-1 space-y-1">
                                    <li>• Avoid listing family members as references</li>
                                    <li>• Make sure to get permission before listing someone as a reference</li>
                                    <li>• Provide current and accurate contact information</li>
                                    <li>• Choose references who can speak positively about your character</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>