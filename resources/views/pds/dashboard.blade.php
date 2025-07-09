<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Personal Data Sheet (PDS)') }} - {{ $employee->full_name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('pds.generate-pdf', $employee) }}">
                    <x-secondary-button>
                        {{ __('Download PDS PDF') }}
                    </x-secondary-button>
                </a>
                <a href="{{ route('employees.show', $employee) }}">
                    <x-secondary-button>
                        {{ __('Back to 201 File') }}
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- PDS Completion Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">PDS Completion Status</h3>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm">
                            <span>Overall Completion</span>
                            <span class="font-medium">{{ $completionStatus['overall_completion'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 mt-1">
                            <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $completionStatus['overall_completion'] }}%"></div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ $completionStatus['completed_panels'] }} of {{ $completionStatus['total_panels'] }} sections completed
                    </div>
                </div>
            </div>

            <!-- PDS Panel Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Panel 1: Personal Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Personal Information</h4>
                            @if($completionStatus['panels']['personal_information'] >= 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ round($completionStatus['panels']['personal_information']) }}%
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $completionStatus['panels']['personal_information'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Basic personal details, addresses, and contact information</p>
                        <a href="{{ route('pds.personal-information', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Edit Details
                        </a>
                    </div>
                </div>

                <!-- Panel 2: Family Background -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Family Background</h4>
                            @if($completionStatus['panels']['family_background'] >= 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ round($completionStatus['panels']['family_background']) }}%
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $completionStatus['panels']['family_background'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Spouse, parents, and children information</p>
                        <a href="{{ route('pds.family-background', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Edit Details
                        </a>
                    </div>
                </div>

                <!-- Panel 3: Educational Background -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Educational Background</h4>
                            @if($completionStatus['panels']['educational_background'] >= 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ round($completionStatus['panels']['educational_background']) }}%
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $completionStatus['panels']['educational_background'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Schools attended and educational achievements</p>
                        <div class="text-sm text-gray-500 mb-4">{{ $employee->education->count() }} record(s)</div>
                        <a href="{{ route('employees.education.index', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Manage Education
                        </a>
                    </div>
                </div>

                <!-- Panel 4: Civil Service Eligibility -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Civil Service Eligibility</h4>
                            @if($completionStatus['panels']['civil_service_eligibility'] >= 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ round($completionStatus['panels']['civil_service_eligibility']) }}%
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $completionStatus['panels']['civil_service_eligibility'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Government eligibility examinations and ratings</p>
                        <div class="text-sm text-gray-500 mb-4">{{ $employee->pdsEligibilities->count() }} record(s)</div>
                        <a href="{{ route('pds.eligibility', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Edit Details
                        </a>
                    </div>
                </div>

                <!-- Panel 5: Work Experience -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Work Experience</h4>
                            @if($completionStatus['panels']['work_experience'] >= 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ round($completionStatus['panels']['work_experience']) }}%
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $completionStatus['panels']['work_experience'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Previous employment history and positions</p>
                        <div class="text-sm text-gray-500 mb-4">{{ $employee->workExperiences->count() }} record(s)</div>
                        <a href="{{ route('pds.work-experience', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Manage Experience
                        </a>
                    </div>
                </div>

                <!-- Panel 6: Voluntary Work -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Voluntary Work</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Optional
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full w-full"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Volunteer work and community service</p>
                        <div class="text-sm text-gray-500 mb-4">{{ $employee->voluntaryWork->count() }} record(s)</div>
                        <a href="{{ route('pds.voluntary-work', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Edit Details
                        </a>
                    </div>
                </div>

                <!-- Panel 7: Learning & Development -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Learning & Development</h4>
                            @if($completionStatus['panels']['learning_development'] >= 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ round($completionStatus['panels']['learning_development']) }}%
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $completionStatus['panels']['learning_development'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Trainings, seminars, and professional development</p>
                        <div class="text-sm text-gray-500 mb-4">{{ $employee->employeeTrainings->count() }} record(s)</div>
                        <a href="{{ route('pds.learning-development', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Manage Trainings
                        </a>
                    </div>
                </div>

                <!-- Panel 8: Other Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Other Information</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Optional
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full w-full"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Skills, hobbies, recognitions, and memberships</p>
                        <div class="text-sm text-gray-500 mb-4">{{ $employee->otherInformation->count() }} record(s)</div>
                        <a href="{{ route('pds.other-information', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Edit Details
                        </a>
                    </div>
                </div>

                <!-- Panel 9: References -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">References</h4>
                            @if($completionStatus['panels']['references'] >= 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ round($completionStatus['panels']['references']) }}%
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $completionStatus['panels']['references'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Character references (minimum 3)</p>
                        <div class="text-sm text-gray-500 mb-4">{{ $employee->references->count() }} of 3 references</div>
                        <a href="{{ route('pds.references', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Edit Details
                        </a>
                    </div>
                </div>

                <!-- Panel 10: Questionnaire -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Questionnaire</h4>
                            @if($completionStatus['panels']['questionnaire'] >= 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ round($completionStatus['panels']['questionnaire']) }}%
                                </span>
                            @endif
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $completionStatus['panels']['questionnaire'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Legal and ethical declarations</p>
                        <a href="{{ route('pds.questionnaire', $employee) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Edit Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>