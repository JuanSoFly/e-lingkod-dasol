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
                                    <span><span class="font-medium">Department:</span> {{ $employee->department }}</span>
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
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line text-success me-2"></i>Career Progression
                    </h5>
                </div>
                <div class="card-body">
                    @if($careerProgression->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($careerProgression as $progression)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst($progression->progression_type) }}</h6>
                                            <p class="text-muted mb-1">
                                                From: {{ $progression->from_position }}<br>
                                                To: {{ $progression->to_position }}
                                            </p>
                                            @if($progression->salary_change_amount)
                                                <small class="text-success">
                                                    <i class="fas fa-arrow-up me-1"></i>
                                                    Salary change: ₱{{ number_format($progression->salary_change_amount, 2) }}
                                                </small>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">{{ $progression->effective_date?->format('M d, Y') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-user-tie text-muted fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No career progression records</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Training History -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-graduation-cap text-info me-2"></i>Training & Development
                    </h5>
                </div>
                <div class="card-body">
                    @if($trainingHistory->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($trainingHistory->take(5) as $training)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $training->trainingProgram?->title ?? 'Training Program' }}</h6>
                                            <p class="text-muted mb-1">{{ $training->trainingProgram?->description ?? 'N/A' }}</p>
                                            <small class="badge bg-{{ $training->status === 'completed' ? 'success' : 'warning' }}">
                                                {{ ucfirst($training->status) }}
                                            </small>
                                            @if($training->training_hours)
                                                <small class="text-muted ms-2">{{ $training->training_hours }} hours</small>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">{{ $training->training_date?->format('M d, Y') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($trainingHistory->count() > 5)
                            <div class="text-center mt-3">
                                <small class="text-muted">And {{ $trainingHistory->count() - 5 }} more training records...</small>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chalkboard-teacher text-muted fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No training records found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Performance History -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star text-warning me-2"></i>Performance History
                    </h5>
                </div>
                <div class="card-body">
                    @if($performanceHistory->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($performanceHistory->take(5) as $review)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $review->performancePeriod?->period_name ?? 'Performance Review' }}</h6>
                                            <p class="text-muted mb-1">
                                                Final Rating: 
                                                @if($review->final_rating)
                                                    <span class="fw-bold text-primary">{{ number_format($review->final_rating, 2) }}</span>
                                                @else
                                                    <span class="text-muted">Not yet rated</span>
                                                @endif
                                            </p>
                                            @if($review->performanceTargets)
                                                <small class="text-muted">
                                                    {{ $review->performanceTargets->count() }} targets set
                                                </small>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">{{ $review->review_date?->format('M d, Y') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($performanceHistory->count() > 5)
                            <div class="text-center mt-3">
                                <small class="text-muted">And {{ $performanceHistory->count() - 5 }} more performance records...</small>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar text-muted fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No performance reviews found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Education & Work Experience -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-university text-primary me-2"></i>Educational Background
                    </h5>
                </div>
                <div class="card-body">
                    @if($educationHistory->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($educationHistory as $education)
                                <div class="list-group-item border-0 px-0">
                                    <h6 class="mb-1">{{ $education->degree }} - {{ $education->field_of_study }}</h6>
                                    <p class="text-muted mb-1">{{ $education->institution }}</p>
                                    <small class="text-muted">
                                        Graduated: {{ $education->graduation_year ?? 'N/A' }}
                                        @if($education->gpa)
                                            | GPA: {{ $education->gpa }}
                                        @endif
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-graduation-cap text-muted fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No education records found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-briefcase text-success me-2"></i>Previous Work Experience
                    </h5>
                </div>
                <div class="card-body">
                    @if($workExperience->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($workExperience as $work)
                                <div class="list-group-item border-0 px-0">
                                    <h6 class="mb-1">{{ $work->position }}</h6>
                                    <p class="text-muted mb-1">{{ $work->company }} - {{ $work->department }}</p>
                                    <small class="text-muted">
                                        {{ $work->start_date?->format('M Y') }} - 
                                        {{ $work->end_date?->format('M Y') ?? 'Present' }}
                                        @if($work->start_date && $work->end_date)
                                            ({{ $work->start_date->diffInMonths($work->end_date) }} months)
                                        @endif
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-building text-muted fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No previous work experience on record</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Modern Tailwind-compatible timeline styles */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #e5e7eb, #d1d5db);
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-marker {
    position: absolute;
    left: -2.5rem;
    top: 0.25rem;
    width: 2rem;
    height: 2rem;
    background: white;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.timeline-item.active .timeline-marker {
    border-color: #3b82f6;
    background: #3b82f6;
    color: white;
}

.timeline-content {
    background: #f9fafb;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    border-left: 4px solid #e5e7eb;
    transition: all 0.2s ease-in-out;
}

.timeline-item.active .timeline-content {
    border-left-color: #3b82f6;
    background: #eff6ff;
}

.timeline-content:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Enhanced card hover effects */
.card-hover {
    transition: all 0.2s ease-in-out;
}

.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
@endpush