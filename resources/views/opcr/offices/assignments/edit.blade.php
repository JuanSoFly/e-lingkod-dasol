@extends('layouts.app')

@section('title', 'Edit Assignment - ' . $office->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('opcr.offices.index') }}" class="hover:text-gray-700">Office Management</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <a href="{{ route('opcr.offices.assignments.index', $office) }}" class="hover:text-gray-700">Assignments</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="text-gray-900">Edit Assignment</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">Edit Office Assignment</h1>
                <p class="mt-2 text-gray-600">Update assignment details for {{ $assignment->user->employee?->full_name ?? $assignment->user->name }} in {{ $office->name }}</p>
            </div>
            <a href="{{ route('opcr.offices.assignments.index', $office) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Assignments
            </a>
        </div>
    </div>

    <!-- Office Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <div class="flex items-center">
            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-blue-900">{{ $office->name }}</h2>
                <p class="text-blue-700">{{ $office->code }} • Level {{ $office->level }} Office</p>
                @if($office->description)
                <p class="text-blue-600 text-sm mt-1">{{ $office->description }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Current Assignment Info -->
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 mb-8">
        <div class="flex items-center">
            <div class="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-amber-900">Current Assignment</h3>
                <p class="text-amber-700">
                    <strong>{{ $assignment->user->employee?->full_name ?? $assignment->user->name }}</strong> -
                    {{ $assignment->role }}
                    @if($assignment->assigned_date)
                    (Since: {{ $assignment->assigned_date->format('M j, Y') }})
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Assignment Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form method="POST" action="{{ route('opcr.offices.assignments.update', [$office, $assignment]) }}" class="p-6">
            @csrf
            @method('PATCH')

            <!-- Employee Selection -->
            <div class="mb-6">
                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Employee <span class="text-red-500">*</span>
                </label>
                <select id="employee_id" name="employee_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select an employee...</option>
                    @foreach($employees as $employee)
                    <option value="{{ $employee->id }}"
                            data-name="{{ $employee->full_name }}"
                            data-employee-number="{{ $employee->employee_number }}"
                            {{ $assignment->employee_id == $employee->id ? 'selected' : '' }}>
                        {{ $employee->full_name }} - {{ $employee->employee_number }}
                        @if($employee->position) - {{ $employee->position }} @endif
                    </option>
                    @endforeach
                </select>
                @error('employee_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Search for an employee to assign to this office.</p>
            </div>

            <!-- Role Selection -->
            <div class="mb-6">
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                    Assignment Role <span class="text-red-500">*</span>
                </label>
                <select id="role" name="role" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select a role...</option>
                    <option value="Department Head" {{ $assignment->role == 'Department Head' ? 'selected' : '' }}>Department Head</option>
                    <option value="Assessor" {{ $assignment->role == 'Assessor' ? 'selected' : '' }}>Assessor (PMT)</option>
                    <option value="Final Approver" {{ $assignment->role == 'Final Approver' ? 'selected' : '' }}>Final Approver (Mayor)</option>
                    <option value="Staff" {{ $assignment->role == 'Staff' ? 'selected' : '' }}>Staff</option>
                    <option value="Supervisor" {{ $assignment->role == 'Supervisor' ? 'selected' : '' }}>Supervisor</option>
                    <option value="Member" {{ $assignment->role == 'Member' ? 'selected' : '' }}>Member</option>
                </select>
                @error('role')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role Descriptions -->
            <div id="roleDescriptions" class="mb-6 hidden">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-2">Role Description:</h4>
                    <div id="roleDescriptionText" class="text-sm text-gray-600"></div>
                </div>
            </div>

            <!-- Assignment Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="assigned_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Assignment Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="assigned_date" name="assigned_date" required
                           value="{{ $assignment->assigned_date ? $assignment->assigned_date->format('Y-m-d') : now()->format('Y-m-d') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('assigned_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ended_date" class="block text-sm font-medium text-gray-700 mb-2">
                        End Date (Optional)
                    </label>
                    <input type="date" id="ended_date" name="ended_date"
                           value="{{ $assignment->ended_date ? $assignment->ended_date->format('Y-m-d') : '' }}"
                           min="{{ now()->format('Y-m-d') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('ended_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Leave blank for ongoing assignment</p>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           {{ $assignment->is_active ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Active Assignment</span>
                </label>
                <p class="mt-1 text-sm text-gray-500">Uncheck to create an inactive assignment</p>
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">
                    Remarks / Notes
                </label>
                <textarea id="remarks" name="remarks" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Additional notes about this assignment...">{{ old('remarks', $assignment->remarks) }}</textarea>
                @error('remarks')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Preview Section -->
            <div id="assignmentPreview" class="mb-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-green-900 mb-4">Assignment Preview</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-green-800">Employee:</span>
                            <span class="text-green-700 ml-2" id="previewEmployee">{{ $assignment->user->employee?->full_name ?? $assignment->user->name }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-green-800">Role:</span>
                            <span class="text-green-700 ml-2" id="previewRole">{{ $assignment->role }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-green-800">Assignment Date:</span>
                            <span class="text-green-700 ml-2" id="previewStartDate">{{ $assignment->assigned_date ? $assignment->assigned_date->format('F j, Y') : now()->format('F j, Y') }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-green-800">End Date:</span>
                            <span class="text-green-700 ml-2" id="previewEndDate">{{ $assignment->ended_date ? $assignment->ended_date->format('F j, Y') : 'Not specified' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('opcr.offices.assignments.index', $office) }}"
                   class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    Update Assignment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeSelect = document.getElementById('employee_id');
    const roleSelect = document.getElementById('role');
    const assignedDateInput = document.getElementById('assigned_date');
    const endedDateInput = document.getElementById('ended_date');
    const isActiveCheckbox = document.getElementById('is_active');
    const remarksInput = document.getElementById('remarks');
    const roleDescriptions = document.getElementById('roleDescriptions');
    const roleDescriptionText = document.getElementById('roleDescriptionText');
    const assignmentPreview = document.getElementById('assignmentPreview');

    // Role descriptions
    const roleDescriptionsMap = {
        'Department Head': 'The Department Head is responsible for overall office management, supervising staff, and making key decisions for the department. They have access to OPCR management tools and can approve/reject workflows.',
        'Assessor': 'The Assessor (PMT - Performance Management Team) evaluates and reviews employee performance commitments and ratings. They can assess OPCR workflows and provide recommendations.',
        'Final Approver': 'The Final Approver (typically the Mayor or highest authority) provides final approval for all OPCR workflows and performance ratings.',
        'Staff': 'Regular staff member assigned to the office with no special OPCR permissions.',
        'Supervisor': 'Office supervisor with limited OPCR viewing and workflow participation permissions.',
        'Member': 'Office member with basic access to OPCR-related information.'
    };

    // Set initial role description
    if (roleSelect.value && roleDescriptionsMap[roleSelect.value]) {
        roleDescriptionText.textContent = roleDescriptionsMap[roleSelect.value];
        roleDescriptions.classList.remove('hidden');
    }

    // Employee search
    employeeSelect.addEventListener('change', updatePreview);

    // Role selection
    roleSelect.addEventListener('change', function() {
        const selectedRole = this.value;
        if (selectedRole && roleDescriptionsMap[selectedRole]) {
            roleDescriptionText.textContent = roleDescriptionsMap[selectedRole];
            roleDescriptions.classList.remove('hidden');
        } else {
            roleDescriptions.classList.add('hidden');
        }
        updatePreview();
    });

    // Date inputs
    assignedDateInput.addEventListener('change', updatePreview);
    endedDateInput.addEventListener('change', updatePreview);
    isActiveCheckbox.addEventListener('change', updatePreview);

    function updatePreview() {
        const selectedEmployee = employeeSelect.options[employeeSelect.selectedIndex];
        const selectedRole = roleSelect.value;
        const assignedDate = assignedDateInput.value;
        const endedDate = endedDateInput.value;
        const isActive = isActiveCheckbox.checked;

        if (selectedEmployee && selectedEmployee.value && selectedRole && assignedDate) {
            // Show preview
            assignmentPreview.classList.remove('hidden');

            // Update preview content
            document.getElementById('previewEmployee').textContent = selectedEmployee.text;
            document.getElementById('previewRole').textContent = selectedRole;
            document.getElementById('previewStartDate').textContent = new Date(assignedDate).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('previewEndDate').textContent = endedDate ? new Date(endedDate).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) : 'Not specified';

            // Update preview styling based on status
            const previewDiv = assignmentPreview.querySelector('div');
            if (isActive) {
                previewDiv.className = 'bg-green-50 border border-green-200 rounded-lg p-6';
                previewDiv.querySelector('h3').className = 'text-lg font-semibold text-green-900 mb-4';
                // Update text colors
                previewDiv.querySelectorAll('.text-green-800, .text-green-700').forEach(el => {
                    if (el.classList.contains('text-green-800')) {
                        el.classList.remove('text-green-800');
                        el.classList.add('text-green-800');
                    }
                    if (el.classList.contains('text-green-700')) {
                        el.classList.remove('text-green-700');
                        el.classList.add('text-green-700');
                    }
                });
            } else {
                previewDiv.className = 'bg-gray-50 border border-gray-200 rounded-lg p-6';
                previewDiv.querySelector('h3').className = 'text-lg font-semibold text-gray-900 mb-4';
                // Reset text colors
                previewDiv.querySelectorAll('.text-green-800, .text-green-700').forEach(el => {
                    if (el.classList.contains('text-green-800')) {
                        el.classList.remove('text-green-800');
                        el.classList.add('text-gray-800');
                    }
                    if (el.classList.contains('text-green-700')) {
                        el.classList.remove('text-green-700');
                        el.classList.add('text-gray-700');
                    }
                });
            }
        } else {
            assignmentPreview.classList.add('hidden');
        }
    }

    // Initialize preview on page load
    updatePreview();

    // Employee search enhancement
    $(document).ready(function() {
        $('#employee_id').select2({
            placeholder: 'Search for an employee...',
            allowClear: true,
            width: '100%'
        });
    });
});
</script>
@endpush
@endsection