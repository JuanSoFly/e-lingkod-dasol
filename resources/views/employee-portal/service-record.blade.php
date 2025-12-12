@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Service Record</h1>
                    <p class="text-lg text-gray-600 mt-1">Complete employment history and career progression</p>
                </div>
                <div>
                    <a href="{{ route('employee-portal.dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Employee Summary -->
        <div class="mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                {{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->last_name }}
                            </h2>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <i class="fas fa-id-badge text-gray-400 mr-2"></i>
                                    <span><span class="font-medium">Employee ID:</span> {{ $employee->employee_number }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-user-tie text-gray-400 mr-2"></i>
                                    <span><span class="font-medium">Position:</span> {{ $employee->position }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-building text-gray-400 mr-2"></i>
                                    <span><span class="font-medium">Department:</span> {{ $employee->office?->name ?? $employee->department }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg px-4 py-3 border border-gray-200">
                            <div class="text-sm space-y-2">
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-calendar text-gray-400 mr-2 w-4"></i>
                                    <span><span class="font-medium">Date Hired:</span> {{ $employee->date_hired?->format('F d, Y') ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-check-circle text-gray-400 mr-2 w-4"></i>
                                    <span><span class="font-medium">Status:</span> {{ ucfirst($employee->employment_status) }}</span>
                                </div>
                                @if($employee->date_hired)
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-clock text-gray-400 mr-2 w-4"></i>
                                        <span><span class="font-medium">Service:</span> {{ $employee->date_hired->diffInYears(now()) }} years</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service History Timeline and Career Progression -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Service History Timeline -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-history text-blue-600 mr-3"></i>
                        Service History Timeline
                    </h2>
                </div>
                <div class="p-6">
                    @if(count($serviceHistory) > 0)
                        <div class="timeline">
                            @foreach($serviceHistory as $index => $event)
                                <div class="timeline-item {{ $index === 0 ? 'active' : '' }}">
                                    <div class="timeline-marker">
                                        @switch($event['type'])
                                            @case('employment')
                                                <i class="fas fa-briefcase"></i>
                                                @break
                                            @case('promotion')
                                                <i class="fas fa-arrow-up"></i>
                                                @break
                                            @default
                                                <i class="fas fa-circle"></i>
                                        @endswitch
                                    </div>
                                    <div class="timeline-content">
                                        <h3 class="text-sm font-medium text-gray-900 mb-1">{{ $event['event'] }}</h3>
                                        <p class="text-sm text-gray-600 mb-2">{{ $event['description'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $event['date']?->format('F d, Y') ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-clock text-gray-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No service history</h3>
                            <p class="text-gray-500">Service history will appear here once available.</p>
                        </div>
                    @endif
                </div>
            </div>

        <!-- Career Progression -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center">
                <i class="fas fa-chart-line text-emerald-500 mr-3"></i>
                <h2 class="text-lg font-semibold text-gray-900">Career Progression</h2>
            </div>
            <div class="p-6">
                @if($careerProgression->count() > 0)
                    <div class="space-y-4">
                        @foreach($careerProgression as $progression)
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 uppercase tracking-wide">
                                            {{ Str::headline($progression->progression_type ?? 'Progression') }}
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <span class="font-medium text-gray-900">From:</span> {{ $progression->from_position ?? 'N/A' }}<br>
                                            <span class="font-medium text-gray-900">To:</span> {{ $progression->to_position ?? 'N/A' }}
                                        </p>
                                        @if($progression->salary_change_amount)
                                            <p class="text-xs text-emerald-600 mt-2 flex items-center gap-2">
                                                <i class="fas fa-arrow-up"></i>
                                                Salary change: ₱{{ number_format($progression->salary_change_amount, 2) }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 sm:text-right">
                                        Effective {{ $progression->effective_date?->format('M d, Y') ?? 'TBD' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <i class="fas fa-user-tie text-slate-400 fa-2x mb-3"></i>
                        <p class="text-gray-500">No career progression records</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Training & Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center">
                <i class="fas fa-graduation-cap text-blue-500 mr-3"></i>
                <h2 class="text-lg font-semibold text-gray-900">Training & Development</h2>
            </div>
            <div class="p-6">
                @if($trainingHistory->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($trainingHistory->take(5) as $training)
                            @php
                                $trainingTitle = $training->training_title
                                    ?? $training->trainingProgram?->program_name
                                    ?? 'Training Program';
                                $trainingDescription = $training->trainingProgram?->description
                                    ?? $training->trainingProgram?->learning_objectives
                                    ?? $training->conducted_sponsored_by
                                    ?? 'Details not provided';
                                $status = $training->completion_status
                                    ?? $training->enrollment_status
                                    ?? $training->record_status
                                    ?? 'Scheduled';
                                $statusStyles = [
                                    'completed' => 'bg-green-100 text-green-800',
                                    'in progress' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-yellow-100 text-yellow-800',
                                    'enrolled' => 'bg-blue-100 text-blue-800',
                                    'scheduled' => 'bg-blue-100 text-blue-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'withdrawn' => 'bg-gray-200 text-gray-700',
                                    'canceled' => 'bg-gray-200 text-gray-700',
                                    'cancelled' => 'bg-gray-200 text-gray-700',
                                ];
                                $statusClass = $statusStyles[strtolower($status)] ?? 'bg-slate-100 text-slate-800';
                                $hours = $training->number_of_hours
                                    ?? $training->hours_attended
                                    ?? $training->trainingProgram?->duration_hours;
                                $startDate = $training->inclusive_date_from ?? $training->start_date;
                                $endDate = $training->inclusive_date_to ?? $training->end_date;
                            @endphp
                            <div class="py-5">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">{{ $trainingTitle }}</h3>
                                        <p class="text-sm text-gray-600 mt-1">{{ Str::limit($trainingDescription, 120) }}</p>
                                        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs font-medium">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full {{ $statusClass }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                            @if($hours)
                                                <span class="text-gray-500">{{ $hours }} hrs</span>
                                            @endif
                                            @if($startDate || $endDate)
                                                <span class="text-gray-500">
                                                    {{ $startDate?->format('M d, Y') ?? 'TBD' }} – {{ $endDate?->format('M d, Y') ?? 'Ongoing' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500 sm:text-right">
                                        {{ $training->trainingProgram?->provider_organization ?? $training->conducted_sponsored_by ?? 'Internal' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($trainingHistory->count() > 5)
                        <div class="mt-4 text-sm text-center text-gray-500">
                            And {{ $trainingHistory->count() - 5 }} more training records…
                        </div>
                    @endif
                @else
                    <div class="text-center py-6">
                        <i class="fas fa-chalkboard-teacher text-slate-400 fa-2x mb-3"></i>
                        <p class="text-gray-500">No training records found</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center">
                <i class="fas fa-star text-amber-500 mr-3"></i>
                <h2 class="text-lg font-semibold text-gray-900">Performance History</h2>
            </div>
            <div class="p-6">
                @if($performanceHistory->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($performanceHistory->take(5) as $review)
                            <div class="py-5">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">{{ $review->performancePeriod?->name ?? 'Performance Review' }}</h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Overall Rating:
                                            @if(!is_null($review->overall_rating))
                                                <span class="text-blue-600 font-semibold">{{ number_format($review->overall_rating, 2) }}</span>
                                            @else
                                                <span class="text-gray-500">Not yet rated</span>
                                            @endif
                                        </p>
                                        @if($review->performanceTargets && $review->performanceTargets->count() > 0)
                                            <p class="text-xs text-gray-500 mt-2">
                                                {{ $review->performanceTargets->count() }} targets set
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 sm:text-right">
                                        {{ $review->review_date?->format('M d, Y') ?? 'Pending Date' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($performanceHistory->count() > 5)
                        <div class="mt-4 text-sm text-center text-gray-500">
                            And {{ $performanceHistory->count() - 5 }} more performance records…
                        </div>
                    @endif
                @else
                    <div class="text-center py-6">
                        <i class="fas fa-chart-bar text-slate-400 fa-2x mb-3"></i>
                        <p class="text-gray-500">No performance reviews found</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Education & Work Experience -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center">
                <i class="fas fa-university text-indigo-500 mr-3"></i>
                <h2 class="text-lg font-semibold text-gray-900">Educational Background</h2>
            </div>
            <div class="p-6">
                @if($educationHistory->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($educationHistory as $education)
                            @php
                                $degree = $education->degree_course ?? $education->course ?? 'Program / Course not specified';
                                $school = $education->school_name ?? 'School not specified';
                                $graduationYear = $education->graduation_year ?? $education->year_graduated ?? $education->year_graduated_pds;
                                $honors = $education->all_honors;
                            @endphp
                            <div class="py-4">
                                <h3 class="text-base font-semibold text-gray-900">{{ $education->education_level }} – {{ $school }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ $degree }}</p>
                                <p class="text-xs text-gray-500 mt-2">
                                    {{ $education->duration }}
                                    @if($graduationYear)
                                        | Graduated: {{ $graduationYear }}
                                    @endif
                                </p>
                                @if($honors)
                                    <p class="text-xs text-emerald-600 mt-1">Honors/Scholarships: {{ $honors }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <i class="fas fa-graduation-cap text-slate-400 fa-2x mb-3"></i>
                        <p class="text-gray-500">No education records found</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center">
                <i class="fas fa-briefcase text-green-500 mr-3"></i>
                <h2 class="text-lg font-semibold text-gray-900">Previous Work Experience</h2>
            </div>
            <div class="p-6">
                @if($workExperience->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($workExperience as $work)
                            @php
                                $position = $work->position_title ?? $work->position ?? 'Position not specified';
                                $organization = $work->department_agency_office ?? $work->company ?? 'Organization not specified';
                                $startDate = $work->inclusive_date_from ?? $work->from_date;
                                $endDate = $work->inclusive_date_to ?? $work->to_date;
                                $status = $work->status_of_appointment ?? $work->status;
                                $durationMonths = ($startDate && $endDate) ? $startDate->diffInMonths($endDate) : null;
                            @endphp
                            <div class="py-4">
                                <h3 class="text-base font-semibold text-gray-900">{{ $position }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ $organization }}</p>
                                <p class="text-xs text-gray-500 mt-2">
                                    {{ $startDate?->format('M Y') ?? 'Unknown' }} – {{ $endDate?->format('M Y') ?? 'Present' }}
                                    @if($durationMonths)
                                        ({{ $durationMonths }} months)
                                    @endif
                                </p>
                                @if($status)
                                    <p class="text-xs text-gray-500">Status: {{ $status }}</p>
                                @endif
                                @if(!is_null($work->is_government_service))
                                    <p class="text-xs text-gray-500">
                                        {{ $work->is_government_service ? 'Government Service' : 'Private Sector' }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <i class="fas fa-building text-slate-400 fa-2x mb-3"></i>
                        <p class="text-gray-500">No previous work experience on record</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
