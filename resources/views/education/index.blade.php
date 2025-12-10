<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Educational Background') }} - {{ $employee->full_name }}
            </h2>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('employees.education.create', $employee) }}">
                    <x-primary-button>
                        {{ __('Add Education') }}
                    </x-primary-button>
                </a>
                <a
                    href="{{ route('employees.show', $employee) }}"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 ease-in-out"
                >
                    {{ __('Back to 201 File') }}
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

            <!-- Education Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Education Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $educations->count() }}</div>
                            <div class="text-sm text-gray-600">Total Records</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $educations->where('education_level', 'College')->count() + $educations->where('education_level', 'Graduate Studies')->count() }}</div>
                            <div class="text-sm text-gray-600">Higher Education</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ $educations->whereNotNull('scholarship_honors_received')->count() + $educations->whereNotNull('honors')->count() }}</div>
                            <div class="text-sm text-gray-600">With Honors</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">{{ $educations->whereNotNull('attachment_id')->count() }}</div>
                            <div class="text-sm text-gray-600">With Documents</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Education Records -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Education Records</h3>
                    
                    @if($educations->count() > 0)
                        <div class="space-y-6">
                            @foreach($educations->groupBy('education_level') as $level => $levelEducations)
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="bg-gray-50 px-6 py-3">
                                        <h4 class="text-md font-semibold text-gray-900 flex items-center">
                                            @switch($level)
                                                @case('Elementary')
                                                    <svg class="h-5 w-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                                                    </svg>
                                                    @break
                                                @case('Secondary')
                                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                    </svg>
                                                    @break
                                                @case('College')
                                                    <svg class="h-5 w-5 text-purple-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                                                    </svg>
                                                    @break
                                                @default
                                                    <svg class="h-5 w-5 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                            @endswitch
                                            {{ $level }}
                                            <span class="ml-2 text-sm font-normal text-gray-500">({{ $levelEducations->count() }} record{{ $levelEducations->count() !== 1 ? 's' : '' }})</span>
                                        </h4>
                                    </div>
                                    
                                    <div class="divide-y divide-gray-200">
                                        @foreach($levelEducations as $education)
                                            <div class="p-6">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                            <!-- School Information -->
                                                            <div>
                                                                <h5 class="text-lg font-medium text-gray-900 mb-2">{{ $education->school_name }}</h5>
                                                                
                                                                @if($education->degree_course)
                                                                    <p class="text-sm text-gray-600 mb-1">
                                                                        <span class="font-medium">Degree/Course:</span> {{ $education->degree_course }}
                                                                    </p>
                                                                @endif
                                                                
                                                                @if($education->course && $education->course !== $education->degree_course)
                                                                    <p class="text-sm text-gray-600 mb-1">
                                                                        <span class="font-medium">Course:</span> {{ $education->course }}
                                                                    </p>
                                                                @endif
                                                                
                                                                @if($education->highest_level_units_earned)
                                                                    <p class="text-sm text-gray-600 mb-1">
                                                                        <span class="font-medium">Highest Level/Units:</span> {{ $education->highest_level_units_earned }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                            
                                                            <!-- Period and Graduation -->
                                                            <div>
                                                                @if($education->period_from || $education->period_to)
                                                                    <p class="text-sm text-gray-600 mb-1">
                                                                        <span class="font-medium">Period:</span> {{ $education->duration }}
                                                                    </p>
                                                                @endif
                                                                
                                                                @if($education->graduation_year)
                                                                    <p class="text-sm text-gray-600 mb-1">
                                                                        <span class="font-medium">Year Graduated:</span> {{ $education->graduation_year }}
                                                                    </p>
                                                                @endif
                                                                
                                                                @if($education->all_honors)
                                                                    <p class="text-sm text-gray-600 mb-1">
                                                                        <span class="font-medium">Honors/Scholarships:</span> {{ $education->all_honors }}
                                                                    </p>
                                                                @endif
                                                                
                                                                @if($education->attachment_id)
                                                                    <div class="mt-2">
                                                                        <a href="{{ route('employees.education.download', [$employee, $education]) }}" 
                                                                           class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                                                                            <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                                            </svg>
                                                                            Download Document
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Actions -->
                                                    <div class="ml-6 flex items-center space-x-2">
                                                        <a href="{{ route('employees.education.edit', [$employee, $education]) }}" 
                                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                            Edit
                                                        </a>
                                                        <form method="POST" action="{{ route('employees.education.destroy', [$employee, $education]) }}" 
                                                              data-confirm="Are you sure you want to delete this education record?" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Education Records</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Get started by adding the first education record for this employee.
                            </p>
                            <div class="mt-6">
                                <a href="{{ route('employees.education.create', $employee) }}">
                                    <x-primary-button>
                                        {{ __('Add Education Record') }}
                                    </x-primary-button>
                                </a>
                            </div>
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
                        <h3 class="text-sm font-medium text-blue-800">Education Management Tips</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>List education records from <strong>elementary to highest level</strong> attained</li>
                                <li>Include <strong>degree/course names</strong> for college and graduate studies</li>
                                <li>Attach <strong>official transcripts or diplomas</strong> when available</li>
                                <li>Record all <strong>honors, scholarships, and awards</strong> received</li>
                                <li>Use correct <strong>period dates</strong> and graduation years</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
