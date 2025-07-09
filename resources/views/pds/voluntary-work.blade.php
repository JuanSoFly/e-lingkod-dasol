<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Voluntary Work Experience') }} - {{ $employee->full_name }}
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

            <!-- Add New Voluntary Work Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Add New Voluntary Work Experience</h3>
                    
                    <form method="POST" action="{{ route('pds.store-voluntary-work', $employee) }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Organization Name & Address -->
                            <div class="md:col-span-2">
                                <label for="organization_name_address" class="block text-sm font-medium text-gray-700">Organization Name & Address *</label>
                                <textarea name="organization_name_address" id="organization_name_address" rows="3" required
                                          placeholder="e.g. Red Cross Philippines, 123 Main Street, Manila"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('organization_name_address') }}</textarea>
                            </div>

                            <!-- Inclusive Date From -->
                            <div>
                                <label for="inclusive_date_from" class="block text-sm font-medium text-gray-700">From Date *</label>
                                <input type="date" name="inclusive_date_from" id="inclusive_date_from" value="{{ old('inclusive_date_from') }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Inclusive Date To -->
                            <div>
                                <label for="inclusive_date_to" class="block text-sm font-medium text-gray-700">To Date</label>
                                <input type="date" name="inclusive_date_to" id="inclusive_date_to" value="{{ old('inclusive_date_to') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Leave blank if ongoing</p>
                            </div>

                            <!-- Number of Hours -->
                            <div>
                                <label for="number_of_hours" class="block text-sm font-medium text-gray-700">Number of Hours *</label>
                                <input type="number" name="number_of_hours" id="number_of_hours" value="{{ old('number_of_hours') }}" required
                                       min="1" placeholder="e.g. 40"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Position/Nature of Work -->
                            <div>
                                <label for="position_nature_of_work" class="block text-sm font-medium text-gray-700">Position/Nature of Work *</label>
                                <input type="text" name="position_nature_of_work" id="position_nature_of_work" value="{{ old('position_nature_of_work') }}" required
                                       placeholder="e.g. Volunteer, Community Organizer"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                {{ __('Add Voluntary Work') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Voluntary Work -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Voluntary Work Experience Records</h3>
                    
                    @if($voluntaryWork && $voluntaryWork->count() > 0)
                        <div class="space-y-4">
                            @foreach($voluntaryWork as $work)
                                <div class="border border-gray-200 rounded-lg p-6">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Organization -->
                                                <div class="md:col-span-2">
                                                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $work->position_nature_of_work }}</h4>
                                                    <p class="text-sm text-gray-600">{{ $work->organization_name_address }}</p>
                                                </div>

                                                <!-- Duration -->
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700">Duration:</span>
                                                    <p class="text-sm text-gray-600">
                                                        {{ $work->inclusive_date_from->format('M d, Y') }} - 
                                                        @if($work->inclusive_date_to)
                                                            {{ $work->inclusive_date_to->format('M d, Y') }}
                                                        @else
                                                            <span class="text-blue-600 font-medium">Ongoing</span>
                                                        @endif
                                                    </p>
                                                </div>

                                                <!-- Hours -->
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700">Hours:</span>
                                                    <p class="text-sm text-gray-600">{{ number_format($work->number_of_hours) }} hours</p>
                                                </div>
                                            </div>

                                            <!-- Duration Badge -->
                                            <div class="mt-4">
                                                @php
                                                    $endDate = $work->inclusive_date_to ?? now();
                                                    $diffInDays = $work->inclusive_date_from->diffInDays($endDate);
                                                    $diffInMonths = $work->inclusive_date_from->diffInMonths($endDate);
                                                @endphp
                                                
                                                @if($diffInMonths >= 12)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $work->inclusive_date_from->diffInYears($endDate) }} year(s)
                                                    </span>
                                                @elseif($diffInMonths >= 1)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $diffInMonths }} month(s)
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        {{ $diffInDays }} day(s)
                                                    </span>
                                                @endif

                                                @if(!$work->inclusive_date_to)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                                                        Active
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="ml-4">
                                            <form method="POST" action="{{ route('pds.destroy-voluntary-work', [$employee, $work]) }}" 
                                                  onsubmit="return confirm('Are you sure you want to delete this voluntary work experience?')" class="inline">
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

                        <!-- Summary Stats -->
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Summary</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Total Organizations:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ $voluntaryWork->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Total Hours:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ number_format($voluntaryWork->sum('number_of_hours')) }} hours</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Active Volunteer Work:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ $voluntaryWork->whereNull('inclusive_date_to')->count() }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Voluntary Work Experience</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                You haven't added any voluntary work experience yet. Add your first voluntary work using the form above.
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
                        <h3 class="text-sm font-medium text-blue-800">About Voluntary Work</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Voluntary work demonstrates civic responsibility and community involvement. Include:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Community service activities</li>
                                <li>Non-profit organization involvement</li>
                                <li>Charity work and fundraising activities</li>
                                <li>Disaster response and relief operations</li>
                                <li>Environmental conservation projects</li>
                                <li>Educational outreach programs</li>
                            </ul>
                            <p class="mt-2"><strong>Note:</strong> This section is optional but highly valued by government agencies.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>