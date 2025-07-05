@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Document Request System</h1>
                    <p class="text-lg text-gray-600 mt-1">Request and track official documents and certificates</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('employee-portal.dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <a href="{{ route('employee-portal.document-requests.new') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        New Request
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Document Requests List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-list text-blue-600 mr-3"></i>
                    My Document Requests
                </h2>
            </div>
            <div class="overflow-hidden">
                @if($documentRequests->count() > 0)
                    <!-- Desktop Table View -->
                    <div class="hidden lg:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Needed By</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($documentRequests as $request)
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $request->document_name }}</div>
                                                    <div class="text-sm text-gray-500">{{ $availableDocuments[$request->document_type] ?? $request->document_type }}</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-600 max-w-xs truncate">{{ Str::limit($request->purpose, 50) }}</div>
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
                                                    {{ ucfirst($request->status) }}
                                                </span>
                                                @if($request->is_overdue)
                                                    <div class="text-xs text-red-600 mt-1">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>Overdue
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @switch($request->priority_badge)
                                                        @case('danger') bg-red-100 text-red-800 @break
                                                        @case('warning') bg-amber-100 text-amber-800 @break
                                                        @case('info') bg-blue-100 text-blue-800 @break
                                                        @default bg-gray-100 text-gray-800
                                                    @endswitch">
                                                    {{ ucfirst($request->priority) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                @if($request->needed_by)
                                                    <div>{{ $request->needed_by->format('M d, Y') }}</div>
                                                    @if($request->days_until_needed !== null)
                                                        <div class="text-xs text-gray-500">
                                                            @if($request->days_until_needed < 0)
                                                                {{ abs($request->days_until_needed) }} days overdue
                                                            @elseif($request->days_until_needed == 0)
                                                                Due today
                                                            @else
                                                                {{ $request->days_until_needed }} days remaining
                                                            @endif
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">Not specified</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <div>{{ $request->created_at->format('M d, Y') }}</div>
                                                <div class="text-xs text-gray-500">{{ $request->created_at->diffForHumans() }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <button type="button" 
                                                            class="text-blue-600 hover:text-blue-900 transition-colors duration-200 p-1 rounded hover:bg-blue-50" 
                                                            onclick="toggleRequestDetails('{{ $request->id }}')"
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if($request->status === 'ready' && $request->hasFile())
                                                        <a href="{{ route('employee-portal.document-requests.download', $request) }}" 
                                                           class="text-green-600 hover:text-green-900 transition-colors duration-200 p-1 rounded hover:bg-green-50"
                                                           title="Download Document">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Collapsible Details Row -->
                                        <tr id="details-{{ $request->id }}" class="hidden">
                                            <td colspan="7" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                                <div class="space-y-4">
                                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                        <div class="space-y-3">
                                                            <h6 class="font-semibold text-gray-900">Document Information</h6>
                                                            <div class="space-y-2 text-sm">
                                                                <div><strong class="text-gray-700">Document:</strong> {{ $request->document_name }}</div>
                                                                <div><strong class="text-gray-700">Type:</strong> {{ $availableDocuments[$request->document_type] ?? $request->document_type }}</div>
                                                                <div><strong class="text-gray-700">Purpose:</strong> {{ $request->purpose }}</div>
                                                                @if($request->description)
                                                                    <div><strong class="text-gray-700">Description:</strong> {{ $request->description }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="space-y-3">
                                                            <h6 class="font-semibold text-gray-900">Request Status</h6>
                                                            <div class="space-y-2 text-sm">
                                                                <div><strong class="text-gray-700">Status:</strong> 
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                                        @switch($request->status_badge)
                                                                            @case('success') bg-green-100 text-green-800 @break
                                                                            @case('warning') bg-amber-100 text-amber-800 @break
                                                                            @case('danger') bg-red-100 text-red-800 @break
                                                                            @case('info') bg-blue-100 text-blue-800 @break
                                                                            @default bg-gray-100 text-gray-800
                                                                        @endswitch">
                                                                        {{ ucfirst($request->status) }}
                                                                    </span>
                                                                </div>
                                                                <div><strong class="text-gray-700">Priority:</strong> 
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                                        @switch($request->priority_badge)
                                                                            @case('danger') bg-red-100 text-red-800 @break
                                                                            @case('warning') bg-amber-100 text-amber-800 @break
                                                                            @case('info') bg-blue-100 text-blue-800 @break
                                                                            @default bg-gray-100 text-gray-800
                                                                        @endswitch">
                                                                        {{ ucfirst($request->priority) }}
                                                                    </span>
                                                                </div>
                                                                @if($request->needed_by)
                                                                    <div><strong class="text-gray-700">Needed By:</strong> {{ $request->needed_by->format('F d, Y') }}</div>
                                                                @endif
                                                                <div><strong class="text-gray-700">Delivery Method:</strong> {{ $deliveryMethods[$request->delivery_method] ?? $request->delivery_method }}</div>
                                                                @if($request->delivery_address)
                                                                    <div><strong class="text-gray-700">Delivery Address:</strong> {{ $request->delivery_address }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if($request->processing_notes)
                                                        <div class="mt-4">
                                                            <h6 class="font-semibold text-gray-900 mb-2">Processing Notes</h6>
                                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">{{ $request->processing_notes }}</div>
                                                        </div>
                                                    @endif
                                                    @if($request->rejection_reason)
                                                        <div class="mt-4">
                                                            <h6 class="font-semibold text-gray-900 mb-2">Rejection Reason</h6>
                                                            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800">{{ $request->rejection_reason }}</div>
                                                        </div>
                                                    @endif
                                                    @if($request->audit_trail)
                                                        <div class="mt-4">
                                                            <h6 class="font-semibold text-gray-900 mb-2">Status History</h6>
                                                            <div class="space-y-2">
                                                                @foreach(array_reverse($request->audit_trail) as $entry)
                                                                    <div class="flex items-start space-x-3 text-sm">
                                                                        <div class="flex-shrink-0 w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                                                                        <div class="flex-1">
                                                                            <div class="text-gray-900">{{ $entry['action'] }}</div>
                                                                            <div class="text-gray-500 text-xs">{{ \Carbon\Carbon::parse($entry['timestamp'])->format('M d, Y H:i') }}</div>
                                                                            @if($entry['notes'])
                                                                                <div class="text-gray-600 text-xs mt-1">{{ $entry['notes'] }}</div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="lg:hidden">
                        <div class="space-y-4 p-4">
                            @foreach($documentRequests as $request)
                                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium text-gray-900">{{ $request->document_name }}</h3>
                                            <p class="text-xs text-gray-500">{{ $availableDocuments[$request->document_type] ?? $request->document_type }}</p>
                                        </div>
                                        <div class="flex items-center space-x-2 ml-3">
                                            <button type="button" 
                                                    class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50" 
                                                    onclick="toggleRequestDetails('{{ $request->id }}')"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($request->status === 'ready' && $request->hasFile())
                                                <a href="{{ route('employee-portal.document-requests.download', $request) }}" 
                                                   class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50"
                                                   title="Download Document">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div>
                                            <span class="text-gray-500">Status:</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-1
                                                @switch($request->status_badge)
                                                    @case('success') bg-green-100 text-green-800 @break
                                                    @case('warning') bg-amber-100 text-amber-800 @break
                                                    @case('danger') bg-red-100 text-red-800 @break
                                                    @case('info') bg-blue-100 text-blue-800 @break
                                                    @default bg-gray-100 text-gray-800
                                                @endswitch">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Priority:</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ml-1
                                                @switch($request->priority_badge)
                                                    @case('danger') bg-red-100 text-red-800 @break
                                                    @case('warning') bg-amber-100 text-amber-800 @break
                                                    @case('info') bg-blue-100 text-blue-800 @break
                                                    @default bg-gray-100 text-gray-800
                                                @endswitch">
                                                {{ ucfirst($request->priority) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Requested:</span>
                                            <span class="text-gray-900 ml-1">{{ $request->created_at->format('M d, Y') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Needed by:</span>
                                            <span class="text-gray-900 ml-1">
                                                @if($request->needed_by)
                                                    {{ $request->needed_by->format('M d, Y') }}
                                                @else
                                                    Not specified
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    
                                    @if($request->purpose)
                                        <div class="mt-3 pt-3 border-t border-gray-100">
                                            <p class="text-xs text-gray-600"><span class="font-medium">Purpose:</span> {{ Str::limit($request->purpose, 100) }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                        
                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $documentRequests->links() }}
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-file-alt text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 mb-2">No Document Requests</h3>
                        <p class="text-gray-500 mb-6">You haven't made any document requests yet.</p>
                        <a href="{{ route('employee-portal.document-requests.new') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i>Create Your First Request
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Toggle request details function
function toggleRequestDetails(requestId) {
    const detailsRow = document.getElementById('details-' + requestId);
    const button = event.target.closest('button');
    
    if (detailsRow.classList.contains('hidden')) {
        // Show details
        detailsRow.classList.remove('hidden');
        detailsRow.classList.add('table-row');
        button.querySelector('i').classList.remove('fa-eye');
        button.querySelector('i').classList.add('fa-eye-slash');
        button.title = 'Hide Details';
    } else {
        // Hide details
        detailsRow.classList.add('hidden');
        detailsRow.classList.remove('table-row');
        button.querySelector('i').classList.remove('fa-eye-slash');
        button.querySelector('i').classList.add('fa-eye');
        button.title = 'View Details';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Enhanced table hover effects
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            if (!this.id.includes('details-')) {
                this.classList.add('bg-gray-50');
            }
        });
        
        row.addEventListener('mouseleave', function() {
            if (!this.id.includes('details-')) {
                this.classList.remove('bg-gray-50');
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
/* Enhanced timeline styles for modals */
.timeline-simple .timeline-entry {
    padding: 0.75rem 0;
    border-left: 3px solid #e5e7eb;
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease-in-out;
}

.timeline-simple .timeline-entry:last-child {
    border-left-color: transparent;
}

.timeline-simple .timeline-entry:hover {
    border-left-color: #3b82f6;
    transform: translateX(2px);
}

/* Enhanced table styles */
.table-hover tbody tr:hover {
    background-color: #f9fafb;
}

/* Custom badge styles */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
}

/* Modal enhancements */
.modal-lg {
    max-width: 900px;
}

.modal-content {
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #e5e7eb;
    padding: 1.5rem;
}

/* Enhanced form styles */
.form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Responsive table improvements */
@media (max-width: 1024px) {
    .table-responsive {
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
    }
}

/* Animation improvements */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Status badge hover effects */
.badge:hover {
    transform: scale(1.05);
    transition: transform 0.1s ease-in-out;
}
</style>
@endpush