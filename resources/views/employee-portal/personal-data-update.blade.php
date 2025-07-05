@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Personal Data Update</h1>
                    <p class="text-lg text-gray-600 mt-1">Request changes to your personal information</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('employee-portal.dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <a href="{{ route('employee-portal.personal-data-update.new') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Request Change
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Current Information Overview -->
        <div class="mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-user text-blue-600 mr-3"></i>
                        Current Personal Information
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Details</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Full Name:</span>
                                    <span class="text-sm text-gray-900">{{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->last_name }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Birth Date:</span>
                                    <span class="text-sm text-gray-900">{{ $employee->birth_date?->format('F d, Y') ?? 'Not set' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Gender:</span>
                                    <span class="text-sm text-gray-900">{{ ucfirst($employee->gender ?? 'Not set') }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Civil Status:</span>
                                    <span class="text-sm text-gray-900">{{ ucfirst($employee->civil_status ?? 'Not set') }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-sm font-medium text-gray-600">Citizenship:</span>
                                    <span class="text-sm text-gray-900">{{ $employee->citizenship ?? 'Not set' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Email:</span>
                                    <span class="text-sm text-gray-900">{{ $employee->email ?? 'Not set' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Contact Number:</span>
                                    <span class="text-sm text-gray-900">{{ $employee->contact_number ?? 'Not set' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Address:</span>
                                    <span class="text-sm text-gray-900">{{ $employee->address ?? 'Not set' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Emergency Contact:</span>
                                    <span class="text-sm text-gray-900">{{ $employee->emergency_contact_name ?? 'Not set' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-sm font-medium text-gray-600">Emergency Number:</span>
                                    <span class="text-sm text-gray-900">{{ $employee->emergency_contact_number ?? 'Not set' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Requests List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-list text-blue-600 mr-3"></i>
                    My Change Requests
                </h2>
            </div>
            <div class="overflow-hidden">
                @if($changeRequests->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Field</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Value</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Value</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Justification</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($changeRequests as $request)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $request->formatted_field_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $request->formatted_change_type }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-600 max-w-xs truncate">{{ Str::limit($request->current_value, 30) ?: 'Not set' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-medium text-gray-900">{{ Str::limit($request->requested_value, 30) }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @switch($request->status_badge)
                                                    @case('success') bg-green-100 text-green-800 @break
                                                    @case('warning') bg-amber-100 text-amber-800 @break
                                                    @case('danger') bg-red-100 text-red-800 @break
                                                    @case('info') bg-blue-100 text-blue-800 @break
                                                    @default bg-gray-100 text-gray-800
                                                @endswitch">
                                                {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                            </span>
                                            @if($request->requires_approval)
                                                <div class="text-xs text-blue-600 mt-1">
                                                    <i class="fas fa-check-circle mr-1"></i>Requires approval
                                                </div>
                                            @else
                                                <div class="text-xs text-green-600 mt-1">
                                                    <i class="fas fa-bolt mr-1"></i>Auto-implementable
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-600 max-w-xs truncate">{{ Str::limit($request->justification, 40) }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <div>{{ $request->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $request->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button type="button" 
                                                    class="text-blue-600 hover:text-blue-900 transition-colors duration-200 p-1 rounded hover:bg-blue-50" 
                                                    onclick="toggleChangeRequestDetails('{{ $request->id }}')"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>

                                        <!-- View Change Request Modal -->
                                        <div class="modal fade" id="viewChangeRequestModal{{ $request->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Change Request Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6 class="fw-bold">Change Information</h6>
                                                                <p><strong>Field:</strong> {{ $request->formatted_field_name }}</p>
                                                                <p><strong>Change Type:</strong> {{ $request->formatted_change_type }}</p>
                                                                <p><strong>Priority:</strong> 
                                                                    <span class="badge bg-{{ $request->priority_badge }}">{{ ucfirst($request->priority) }}</span>
                                                                </p>
                                                                <p><strong>Current Value:</strong> {{ $request->current_value ?: 'Not set' }}</p>
                                                                <p><strong>Requested Value:</strong> <strong>{{ $request->requested_value }}</strong></p>
                                                                @if($request->effective_date)
                                                                    <p><strong>Effective Date:</strong> {{ $request->effective_date->format('F d, Y') }}</p>
                                                                @endif
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6 class="fw-bold">Request Status</h6>
                                                                <p><strong>Status:</strong> 
                                                                    <span class="badge bg-{{ $request->status_badge }}">{{ ucfirst(str_replace('_', ' ', $request->status)) }}</span>
                                                                </p>
                                                                <p><strong>Requires Approval:</strong> 
                                                                    @if($request->requires_approval)
                                                                        <span class="text-info">Yes</span>
                                                                    @else
                                                                        <span class="text-success">No (Auto-implementable)</span>
                                                                    @endif
                                                                </p>
                                                                @if($request->reviewed_at)
                                                                    <p><strong>Reviewed:</strong> {{ $request->reviewed_at->format('F d, Y H:i') }}</p>
                                                                @endif
                                                                @if($request->implemented_at)
                                                                    <p><strong>Implemented:</strong> {{ $request->implemented_at->format('F d, Y H:i') }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="mt-3">
                                                            <h6 class="fw-bold">Justification</h6>
                                                            <div class="alert alert-light">{{ $request->justification }}</div>
                                                        </div>
                                                        @if($request->hasSupportingDocuments())
                                                            <div class="mt-3">
                                                                <h6 class="fw-bold">Supporting Documents</h6>
                                                                <ul class="list-group">
                                                                    @foreach($request->supporting_documents as $doc)
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                            {{ $doc['name'] }}
                                                                            <small class="text-muted">{{ number_format($doc['size'] / 1024, 2) }} KB</small>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif
                                                        @if($request->review_notes)
                                                            <div class="mt-3">
                                                                <h6 class="fw-bold">Review Notes</h6>
                                                                <div class="alert alert-info">{{ $request->review_notes }}</div>
                                                            </div>
                                                        @endif
                                                        @if($request->rejection_reason)
                                                            <div class="mt-3">
                                                                <h6 class="fw-bold">Rejection Reason</h6>
                                                                <div class="alert alert-danger">{{ $request->rejection_reason }}</div>
                                                            </div>
                                                        @endif
                                                        @if($request->implementation_notes)
                                                            <div class="mt-3">
                                                                <h6 class="fw-bold">Implementation Notes</h6>
                                                                <div class="alert alert-success">{{ $request->implementation_notes }}</div>
                                                            </div>
                                                        @endif
                                                        @if($request->validation_errors)
                                                            <div class="mt-3">
                                                                <h6 class="fw-bold">Validation Issues</h6>
                                                                <div class="alert alert-warning">
                                                                    <ul class="mb-0">
                                                                        @foreach($request->validation_errors as $error)
                                                                            <li>{{ $error }}</li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if($request->audit_trail)
                                                            <div class="mt-3">
                                                                <h6 class="fw-bold">Status History</h6>
                                                                <div class="timeline-simple">
                                                                    @foreach(array_reverse($request->audit_trail) as $entry)
                                                                        <div class="timeline-entry">
                                                                            <small class="text-muted">{{ \Carbon\Carbon::parse($entry['timestamp'])->format('M d, Y H:i') }}</small>
                                                                            <p class="mb-1">{{ $entry['action'] }}</p>
                                                                            @if($entry['notes'])
                                                                                <small class="text-muted">{{ $entry['notes'] }}</small>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $changeRequests->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-edit text-muted fa-3x mb-3"></i>
                            <h5 class="text-muted">No Change Requests</h5>
                            <p class="text-muted mb-4">You haven't requested any personal data changes yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const changeTypeSelect = document.getElementById('change_type');
    const fieldNameSelect = document.getElementById('field_name');
    const currentValueDisplay = document.getElementById('current_value_display');
    
    // Field options for each change type
    const editableFields = @json($editableFields);
    
    // Current employee data
    const employeeData = @json($employee->toArray());
    
    changeTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        fieldNameSelect.innerHTML = '<option value="">Select field...</option>';
        currentValueDisplay.value = '';
        
        if (selectedType && editableFields[selectedType]) {
            Object.entries(editableFields[selectedType]).forEach(([key, value]) => {
                const option = new Option(value, key);
                fieldNameSelect.add(option);
            });
        }
    });
    
    fieldNameSelect.addEventListener('change', function() {
        const selectedField = this.value;
        if (selectedField && employeeData[selectedField] !== undefined) {
            currentValueDisplay.value = employeeData[selectedField] || 'Not set';
        } else {
            currentValueDisplay.value = 'Not set';
        }
    });
});
</script>
@endpush

@push('styles')
<style>
.timeline-simple .timeline-entry {
    padding: 0.5rem 0;
    border-left: 2px solid #dee2e6;
    padding-left: 1rem;
    margin-bottom: 0.5rem;
}

.timeline-simple .timeline-entry:last-child {
    border-left-color: transparent;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
}

.modal-lg {
    max-width: 900px;
}

.alert {
    border: 1px solid;
    border-radius: 0.375rem;
}
</style>
@endpush