<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Work Experience') }} - {{ $employee->full_name }}
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

            <!-- Add New Work Experience Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Add New Work Experience</h3>
                    
                    <form method="POST" action="{{ route('pds.store-work-experience', $employee) }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Position Title -->
                            <div>
                                <label for="position_title" class="block text-sm font-medium text-gray-700">Position Title *</label>
                                <input type="text" name="position_title" id="position_title" value="{{ old('position_title') }}" required
                                       placeholder="e.g. Administrative Assistant I"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Department/Agency/Office -->
                            <div>
                                <label for="department_agency_office" class="block text-sm font-medium text-gray-700">Department/Agency/Office *</label>
                                <input type="text" name="department_agency_office" id="department_agency_office" value="{{ old('department_agency_office') }}" required
                                       placeholder="e.g. Municipality of Dasol"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
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
                                <p class="text-xs text-gray-500 mt-1">Leave blank if current position</p>
                            </div>

                            <!-- Monthly Salary -->
                            <div>
                                <label for="monthly_salary" class="block text-sm font-medium text-gray-700">Monthly Salary</label>
                                <input type="number" name="monthly_salary" id="monthly_salary" value="{{ old('monthly_salary') }}" 
                                       step="0.01" min="0" placeholder="e.g. 25000.00"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Salary Grade & Step -->
                            <div>
                                <label for="salary_grade_step" class="block text-sm font-medium text-gray-700">Salary Grade & Step</label>
                                <input type="text" name="salary_grade_step" id="salary_grade_step" value="{{ old('salary_grade_step') }}" 
                                       placeholder="e.g. 24-2"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Status of Appointment -->
                            <div>
                                <label for="status_of_appointment" class="block text-sm font-medium text-gray-700">Status of Appointment *</label>
                                <select name="status_of_appointment" id="status_of_appointment" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Status</option>
                                    <option value="Permanent" {{ old('status_of_appointment') == 'Permanent' ? 'selected' : '' }}>Permanent</option>
                                    <option value="Temporary" {{ old('status_of_appointment') == 'Temporary' ? 'selected' : '' }}>Temporary</option>
                                    <option value="Casual" {{ old('status_of_appointment') == 'Casual' ? 'selected' : '' }}>Casual</option>
                                    <option value="Contractual" {{ old('status_of_appointment') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                                    <option value="Job Order" {{ old('status_of_appointment') == 'Job Order' ? 'selected' : '' }}>Job Order</option>
                                    <option value="Contract of Service" {{ old('status_of_appointment') == 'Contract of Service' ? 'selected' : '' }}>Contract of Service</option>
                                </select>
                            </div>

                            <!-- Government Service -->
                            <div>
                                <label for="is_government_service" class="block text-sm font-medium text-gray-700">Government Service *</label>
                                <select name="is_government_service" id="is_government_service" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Type</option>
                                    <option value="1" {{ old('is_government_service') == '1' ? 'selected' : '' }}>Yes (Government Service)</option>
                                    <option value="0" {{ old('is_government_service') == '0' ? 'selected' : '' }}>No (Private Sector)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                {{ __('Add Work Experience') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Work Experience -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Work Experience Records</h3>
                    
                    @if($workExperiences && $workExperiences->count() > 0)
                        <div class="space-y-4">
                            @foreach($workExperiences as $experience)
                                <div class="border border-gray-200 rounded-lg p-6">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Position & Organization -->
                                                <div class="md:col-span-2">
                                                    <h4 class="text-lg font-medium text-gray-900 mb-1">{{ $experience->position_title }}</h4>
                                                    <p class="text-sm text-gray-600 mb-2">{{ $experience->department_agency_office }}</p>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $experience->is_government_service ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                                            {{ $experience->is_government_service ? 'Government' : 'Private' }}
                                                        </span>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            {{ $experience->status_of_appointment }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Duration -->
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700">Duration:</span>
                                                    <p class="text-sm text-gray-600">
                                                        {{ $experience->inclusive_date_from->format('M d, Y') }} - 
                                                        @if($experience->inclusive_date_to)
                                                            {{ $experience->inclusive_date_to->format('M d, Y') }}
                                                        @else
                                                            <span class="text-blue-600 font-medium">Present</span>
                                                        @endif
                                                    </p>
                                                </div>

                                                <!-- Salary Info -->
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700">Salary:</span>
                                                    <p class="text-sm text-gray-600">
                                                        @if($experience->monthly_salary)
                                                            ₱{{ number_format($experience->monthly_salary, 2) }}
                                                        @else
                                                            <span class="text-gray-400">Not specified</span>
                                                        @endif
                                                        @if($experience->salary_grade_step)
                                                            <span class="text-gray-500">(SG {{ $experience->salary_grade_step }})</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Duration Badge -->
                                            <div class="mt-4">
                                                @php
                                                    $endDate = $experience->inclusive_date_to ?? now();
                                                    $diffInDays = $experience->inclusive_date_from->diffInDays($endDate);
                                                    $diffInMonths = $experience->inclusive_date_from->diffInMonths($endDate);
                                                    $diffInYears = $experience->inclusive_date_from->diffInYears($endDate);
                                                @endphp
                                                
                                                @if($diffInYears >= 1)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $diffInYears }} year(s) {{ $diffInMonths % 12 }} month(s)
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

                                                @if(!$experience->inclusive_date_to)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                                                        Current
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="ml-4">
                                            <form method="POST" action="{{ route('pds.destroy-work-experience', [$employee, $experience]) }}" 
                                                  data-confirm="Are you sure you want to delete this work experience?" class="inline">
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
                                    <span class="text-gray-600">Total Positions:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ $workExperiences->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Government Service:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ $workExperiences->where('is_government_service', true)->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Private Sector:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ $workExperiences->where('is_government_service', false)->count() }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6.294A23.931 23.931 0 0112 15" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Work Experience</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                You haven't added any work experience yet. Add your first work experience using the form above.
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
                        <h3 class="text-sm font-medium text-blue-800">About Work Experience</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>List all your work experience, starting with your current or most recent position. Include:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Full-time and part-time positions</li>
                                <li>Government and private sector employment</li>
                                <li>Contractual and permanent appointments</li>
                                <li>Job order and contract of service positions</li>
                                <li>Temporary assignments and details</li>
                            </ul>
                            <p class="mt-2"><strong>Note:</strong> Be accurate with dates and position titles as these will be verified.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
