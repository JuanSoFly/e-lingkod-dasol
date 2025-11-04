@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My 201 File</h1>
                    <p class="text-lg text-gray-600 mt-1">Complete employee record and information</p>
                </div>
                <div class="flex space-x-3">
                    <!--
                    <button onclick="downloadPDSExcel(this)"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        <i class="fas fa-file-excel mr-2"></i>
                        <span id="download-text">Download Excel</span>
                    </button>
                    -->
                    <a href="{{ route('employee-portal.dashboard') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Employee Basic Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-indigo-50">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-user text-indigo-600 mr-3"></i>
                    Personal Information
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Full Name</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->last_name }} {{ $employee->suffix }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Employee Number</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->employee_number }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Position</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->position }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Department</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->office?->name ?? $employee->department }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Date Hired</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->date_hired ? $employee->date_hired->format('F d, Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Employment Status</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->employment_status ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Basic Salary</h3>
                        <p class="text-lg font-medium text-gray-900">₱{{ number_format($employee->basic_salary, 2) }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Birth Date</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->birth_date ? $employee->birth_date->format('F d, Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Age</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->birth_date ? $employee->birth_date->age : 'N/A' }} years old</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Sex</h3>
                        <p class="text-lg font-medium text-gray-900">{{ $employee->gender ?? 'N/A' }}</p>
                    </div>
                </div>

                @if($employee->contact_number || $employee->email || $employee->address)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($employee->contact_number)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Contact Number</h3>
                            <p class="text-lg font-medium text-gray-900">{{ $employee->contact_number }}</p>
                        </div>
                        @endif
                        @if($employee->email)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Email Address</h3>
                            <p class="text-lg font-medium text-gray-900">{{ $employee->email }}</p>
                        </div>
                        @endif
                        @if($employee->address)
                        <div class="md:col-span-2">
                            <h3 class="text-sm font-medium text-gray-500">Address</h3>
                            <p class="text-lg font-medium text-gray-900">{{ $employee->address }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Office Assignments and Roles -->
        @if($officeAssignments->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-building text-purple-600 mr-3"></i>
                    Office Assignments and Roles
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($officeAssignments as $assignment)
                        <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg border border-purple-200">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user-tie text-purple-600"></i>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ $assignment->role }}</h3>
                                    <p class="text-sm text-gray-600">{{ $assignment->office->name }}</p>
                                    <p class="text-xs text-gray-500">
                                        Assigned: {{ $assignment->assigned_date->format('F j, Y') }}
                                        @if($assignment->ended_date)
                                        - Ended: {{ $assignment->ended_date->format('F j, Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                    @if($assignment->is_active)
                                        Active
                                    @else
                                        Inactive
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Education History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-emerald-50">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-graduation-cap text-emerald-600 mr-3"></i>
                    Educational Background
                </h2>
            </div>
            <div class="p-6">
                @if($educationHistory->count() > 0)
                    <div class="space-y-6">
                        @foreach($educationHistory as $education)
                        <div class="border-l-4 border-emerald-200 pl-4 py-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ $education->degree }}</h3>
                                    <p class="text-gray-600">{{ $education->school_name }}</p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $education->start_date ? $education->start_date->format('Y') : '' }} -
                                        {{ $education->end_date ? $education->end_date->format('Y') : 'Present' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        {{ $education->level ?? 'Not specified' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-graduation-cap text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Education Records</h3>
                        <p class="text-gray-500">No educational background information has been recorded.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Work Experience -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-briefcase text-blue-600 mr-3"></i>
                    Work Experience
                </h2>
            </div>
            <div class="p-6">
                @if($workExperience->count() > 0)
                    <div class="space-y-6">
                        @foreach($workExperience as $work)
                        <div class="border-l-4 border-blue-200 pl-4 py-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ $work->position }}</h3>
                                    <p class="text-gray-600">{{ $work->company }}</p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $work->start_date ? $work->start_date->format('M Y') : '' }} -
                                        {{ $work->end_date ? $work->end_date->format('M Y') : 'Present' }}
                                    </p>
                                    @if($work->description)
                                    <p class="text-sm text-gray-600 mt-2">{{ $work->description }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $work->employment_type ?? 'Not specified' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-briefcase text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Work Experience Records</h3>
                        <p class="text-gray-500">No work experience information has been recorded.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Family Background -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-users text-purple-600 mr-3"></i>
                    Family Background
                </h2>
            </div>
            <div class="p-6">
                @if($familyBackground)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Parents</h3>
                            <div class="space-y-3">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Father's Name</h4>
                                    <p class="text-gray-900">{{ $familyBackground->father_name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Mother's Name</h4>
                                    <p class="text-gray-900">{{ $familyBackground->mother_name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Spouse</h3>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500">Spouse's Name</h4>
                                <p class="text-gray-900">{{ $familyBackground->spouse_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($children->count() > 0)
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Children</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($children as $child)
                            <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                                <h4 class="font-medium text-gray-900">{{ $child->name }}</h4>
                                <p class="text-sm text-gray-600">Born: {{ $child->birth_date ? $child->birth_date->format('F d, Y') : 'N/A' }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Family Background</h3>
                        <p class="text-gray-500">No family background information has been recorded.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Documents -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-amber-50">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-file-alt text-amber-600 mr-3"></i>
                    Documents
                </h2>
            </div>
            <div class="p-6">
                @if($documents->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($documents as $document)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-medium text-gray-900">{{ $document->document_type }}</h3>
                                @if($document->file_path)
                                <a href="{{ route('documents.download', $document) }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-download"></i>
                                </a>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">
                                Uploaded: {{ $document->created_at->format('M d, Y') }}
                            </p>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-alt text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Documents</h3>
                        <p class="text-gray-500">No documents have been uploaded to your file.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Civil Service Eligibility -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-certificate text-green-600 mr-3"></i>
                    Civil Service Eligibility
                </h2>
            </div>
            <div class="p-6">
                @if($eligibilities->count() > 0)
                    <div class="space-y-4">
                        @foreach($eligibilities as $eligibility)
                        <div class="border-l-4 border-green-200 pl-4 py-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ $eligibility->career_service }}</h3>
                                    <p class="text-gray-600">Rating: {{ $eligibility->rating ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Date Acquired: {{ $eligibility->date_acquired ? $eligibility->date_acquired->format('F d, Y') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $eligibility->examination_place ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-certificate text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Eligibility Records</h3>
                        <p class="text-gray-500">No civil service eligibility information has been recorded.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

<!-- Status Messages Container -->
<div id="status-message" class="hidden fixed top-4 right-4 max-w-md p-4 rounded-lg shadow-lg z-50 transition-all duration-300 transform">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i id="status-icon" class="fas fa-info-circle text-xl"></i>
        </div>
        <div class="ml-3">
            <p id="status-text" class="text-sm font-medium"></p>
        </div>
        <div class="ml-auto pl-3">
            <button onclick="hideStatusMessage()" class="inline-flex text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<script>
function downloadPDSExcel(button) {
    // Disable button and show loading state
    const originalText = document.getElementById('download-text').textContent;
    button.disabled = true;
    document.getElementById('download-text').textContent = 'Preparing Download...';
    button.classList.add('opacity-75', 'cursor-not-allowed');

    // Show status message
    showStatusMessage('Preparing your PDS Excel file...', 'info');

    // Validate PDS data completeness
    const hasBasicInfo = '{{ $employee->first_name }}' && '{{ $employee->last_name }}';
    const hasEducation = {{ $educationHistory->count() }} > 0;
    const hasFamilyBackground = {{ $familyBackground ? 'true' : 'false' }};
    const hasWorkExperience = {{ $workExperience->count() }} > 0;
    const hasEligibilities = {{ $eligibilities->count() }} > 0;

    // Check current time for business hours
    const now = new Date();
    const currentHour = now.getHours();
    const isWeekday = now.getDay() >= 1 && now.getDay() <= 5;
    const isBusinessHours = isWeekday && currentHour >= 9 && currentHour < 18;

    // Validation warnings
    const warnings = [];

    if (!hasEducation) {
        warnings.push('No education records found');
    }

    if (!hasFamilyBackground) {
        warnings.push('No family background information');
    }

    if (!hasWorkExperience) {
        warnings.push('No work experience records');
    }

    if (!hasEligibilities) {
        warnings.push('No civil service eligibility records');
    }

    // Show warnings if data is incomplete
    if (warnings.length > 0) {
        const warningMessage = 'Your PDS data appears incomplete. The following sections are empty: ' + warnings.join(', ') + '. You may want to update your information first. Continue with export?';

        if (!confirm(warningMessage)) {
            // Reset button state
            button.disabled = false;
            document.getElementById('download-text').textContent = originalText;
            button.classList.remove('opacity-75', 'cursor-not-allowed');
            hideStatusMessage();
            return;
        }
    }

    // Show business hours warning for non-Super Admin users
    @auth
        @if(!auth()->user()->hasRole('Super Admin'))
            if (!isBusinessHours) {
                const businessHoursMessage = 'Exports are only allowed during business hours (9 AM - 6 PM, Monday-Friday). Current time is outside business hours. The export may be restricted. Continue?';

                if (!confirm(businessHoursMessage)) {
                    // Reset button state
                    button.disabled = false;
                    document.getElementById('download-text').textContent = originalText;
                    button.classList.remove('opacity-75', 'cursor-not-allowed');
                    hideStatusMessage();
                    return;
                }
            }
        @endif
    @endauth

    // Proceed with download
    showStatusMessage('Downloading your PDS Excel file...', 'success');
    window.location.href = '{{ route("employee-portal.export-pds") }}';

    // Reset button state after a delay
    setTimeout(() => {
        button.disabled = false;
        document.getElementById('download-text').textContent = originalText;
        button.classList.remove('opacity-75', 'cursor-not-allowed');
        hideStatusMessage();
    }, 3000);
}

function showStatusMessage(message, type = 'info') {
    const messageDiv = document.getElementById('status-message');
    const textElement = document.getElementById('status-text');
    const iconElement = document.getElementById('status-icon');

    // Set message
    textElement.textContent = message;

    // Set styling based on type
    messageDiv.classList.remove('bg-blue-100', 'text-blue-800', 'bg-yellow-100', 'text-yellow-800', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
    iconElement.classList.remove('fa-info-circle', 'fa-exclamation-triangle', 'fa-check-circle', 'fa-times-circle', 'text-blue-500', 'text-yellow-500', 'text-green-500', 'text-red-500');

    switch(type) {
        case 'warning':
            messageDiv.classList.add('bg-yellow-100', 'text-yellow-800', 'border', 'border-yellow-200');
            iconElement.classList.add('fa-exclamation-triangle', 'text-yellow-500');
            break;
        case 'success':
            messageDiv.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-200');
            iconElement.classList.add('fa-check-circle', 'text-green-500');
            break;
        case 'error':
            messageDiv.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-200');
            iconElement.classList.add('fa-times-circle', 'text-red-500');
            break;
        default: // info
            messageDiv.classList.add('bg-blue-100', 'text-blue-800', 'border', 'border-blue-200');
            iconElement.classList.add('fa-info-circle', 'text-blue-500');
    }

    // Show message with animation
    messageDiv.classList.remove('hidden');
    setTimeout(() => {
        messageDiv.classList.add('translate-y-0', 'opacity-100');
    }, 100);
}

function hideStatusMessage() {
    const messageDiv = document.getElementById('status-message');
    messageDiv.classList.add('hidden', 'translate-y-2', 'opacity-0');
    setTimeout(() => {
        messageDiv.classList.add('hidden');
    }, 300);
}

// Auto-hide any Laravel flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('[role="alert"]');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
});
</script>

@endsection
