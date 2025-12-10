<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Learning & Development') }} - {{ $employee->full_name }}
            </h2>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('pds.dashboard', $employee) }}">
                    <x-secondary-button>
                        {{ __('Back to PDS Dashboard') }}
                    </x-secondary-button>
                </a>
            </div>
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

            <!-- Add New Learning & Development Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Add New Learning & Development Intervention</h3>
                    
                    <form method="POST" action="{{ route('pds.store-learning-development', $employee) }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Training Title -->
                            <div class="md:col-span-2">
                                <label for="training_title" class="block text-sm font-medium text-gray-700">Training Title *</label>
                                <input type="text" name="training_title" id="training_title" value="{{ old('training_title') }}" required
                                       placeholder="e.g. Strategic Leadership Development Program"
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
                                <p class="text-xs text-gray-500 mt-1">Leave blank if same day or ongoing</p>
                            </div>

                            <!-- Number of Hours -->
                            <div>
                                <label for="number_of_hours" class="block text-sm font-medium text-gray-700">Number of Hours *</label>
                                <input type="number" name="number_of_hours" id="number_of_hours" value="{{ old('number_of_hours') }}" required
                                       step="0.5" min="0.5" placeholder="e.g. 40.0"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Type of L&D -->
                            <div>
                                <label for="type_of_ld" class="block text-sm font-medium text-gray-700">Type of L&D *</label>
                                <select name="type_of_ld" id="type_of_ld" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Type</option>
                                    <option value="Managerial" {{ old('type_of_ld') == 'Managerial' ? 'selected' : '' }}>Managerial</option>
                                    <option value="Supervisory" {{ old('type_of_ld') == 'Supervisory' ? 'selected' : '' }}>Supervisory</option>
                                    <option value="Technical" {{ old('type_of_ld') == 'Technical' ? 'selected' : '' }}>Technical</option>
                                    <option value="Professional" {{ old('type_of_ld') == 'Professional' ? 'selected' : '' }}>Professional</option>
                                    <option value="Foundational" {{ old('type_of_ld') == 'Foundational' ? 'selected' : '' }}>Foundational</option>
                                    <option value="Other" {{ old('type_of_ld') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <!-- Conducted/Sponsored By -->
                            <div class="md:col-span-2">
                                <label for="conducted_sponsored_by" class="block text-sm font-medium text-gray-700">Conducted/Sponsored By *</label>
                                <input type="text" name="conducted_sponsored_by" id="conducted_sponsored_by" value="{{ old('conducted_sponsored_by') }}" required
                                       placeholder="e.g. Department of Budget and Management"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Attachment ID -->
                            <div class="md:col-span-2">
                                <label for="attachment_id" class="block text-sm font-medium text-gray-700">Attachment ID (Certificate Reference)</label>
                                <input type="text" name="attachment_id" id="attachment_id" value="{{ old('attachment_id') }}" 
                                       placeholder="e.g. Certificate #12345 or file reference"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Reference number or file path of the training certificate</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                {{ __('Add L&D Intervention') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Learning & Development -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Learning & Development Records</h3>
                    
                    @if($trainings && $trainings->count() > 0)
                        <div class="space-y-4">
                            @foreach($trainings as $training)
                                <div class="border border-gray-200 rounded-lg p-6">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Training Title & Type -->
                                                <div class="md:col-span-2">
                                                    <h4 class="text-lg font-medium text-gray-900 mb-1">{{ $training->training_title }}</h4>
                                                    <p class="text-sm text-gray-600 mb-2">{{ $training->conducted_sponsored_by }}</p>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            {{ $training->type_of_ld == 'Managerial' ? 'bg-purple-100 text-purple-800' : 
                                                               ($training->type_of_ld == 'Supervisory' ? 'bg-blue-100 text-blue-800' : 
                                                                ($training->type_of_ld == 'Technical' ? 'bg-green-100 text-green-800' : 
                                                                 ($training->type_of_ld == 'Professional' ? 'bg-indigo-100 text-indigo-800' : 
                                                                  ($training->type_of_ld == 'Foundational' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')))) }}">
                                                            {{ $training->type_of_ld }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Duration -->
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700">Duration:</span>
                                                    <p class="text-sm text-gray-600">
                                                        {{ $training->inclusive_date_from->format('M d, Y') }}
                                                        @if($training->inclusive_date_to && $training->inclusive_date_to != $training->inclusive_date_from)
                                                            - {{ $training->inclusive_date_to->format('M d, Y') }}
                                                        @endif
                                                    </p>
                                                </div>

                                                <!-- Hours -->
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700">Hours:</span>
                                                    <p class="text-sm text-gray-600">{{ number_format($training->number_of_hours, 1) }} hours</p>
                                                </div>

                                                <!-- Attachment -->
                                                @if($training->attachment_id)
                                                    <div class="md:col-span-2">
                                                        <span class="text-sm font-medium text-gray-700">Certificate:</span>
                                                        <p class="text-sm text-gray-600">{{ $training->attachment_id }}</p>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Duration Badge -->
                                            <div class="mt-4">
                                                @php
                                                    $endDate = $training->inclusive_date_to ?? $training->inclusive_date_from;
                                                    $diffInDays = $training->inclusive_date_from->diffInDays($endDate);
                                                @endphp
                                                
                                                @if($diffInDays == 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        1 Day
                                                    </span>
                                                @elseif($diffInDays < 7)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $diffInDays + 1 }} Days
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        {{ ceil($diffInDays / 7) }} Week(s)
                                                    </span>
                                                @endif

                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 ml-2">
                                                    {{ number_format($training->number_of_hours, 1) }}h
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="ml-4">
                                            <form method="POST" action="{{ route('pds.destroy-learning-development', [$employee, $training]) }}" 
                                                  data-confirm="Are you sure you want to delete this learning & development record?" class="inline">
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
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Total Trainings:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ $trainings->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Total Hours:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ number_format($trainings->sum('number_of_hours'), 1) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">With Certificates:</span>
                                    <span class="font-medium text-gray-900 ml-2">{{ $trainings->whereNotNull('attachment_id')->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Most Common Type:</span>
                                    <span class="font-medium text-gray-900 ml-2">
                                        {{ $trainings->groupBy('type_of_ld')->sortByDesc(function($group) { return $group->count(); })->keys()->first() ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Learning & Development Records</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                You haven't added any learning & development interventions yet. Add your first training using the form above.
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
                        <h3 class="text-sm font-medium text-blue-800">About Learning & Development</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Include all relevant trainings, seminars, workshops, and learning interventions you have attended. Categories include:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li><strong>Managerial:</strong> Leadership, strategic planning, management development</li>
                                <li><strong>Supervisory:</strong> Team management, supervisory skills, people management</li>
                                <li><strong>Technical:</strong> Job-specific skills, technical competencies, specialized training</li>
                                <li><strong>Professional:</strong> Professional development, continuing education, certification programs</li>
                                <li><strong>Foundational:</strong> Basic skills, orientation programs, core competencies</li>
                            </ul>
                            <p class="mt-2"><strong>Note:</strong> This information is used for career development and promotion considerations.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
