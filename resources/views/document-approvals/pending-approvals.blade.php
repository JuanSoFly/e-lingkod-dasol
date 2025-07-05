@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Pending Approvals</h2>
                        <p class="mt-1 text-sm text-gray-600">Document approval requests awaiting your action</p>
                    </div>
                    <div class="flex space-x-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            {{ $requests->total() }} Pending
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
                <form method="GET" action="{{ route('document-approvals.pending-approvals') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Document Type Filter -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Document Type</label>
                            <input type="text" name="type" id="type" value="{{ request('type') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Employee Filter -->
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
                            <select name="employee_id" id="employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Workflow Filter -->
                        <div>
                            <label for="workflow_id" class="block text-sm font-medium text-gray-700">Workflow</label>
                            <select name="workflow_id" id="workflow_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Workflows</option>
                                @foreach($workflows as $workflow)
                                    <option value="{{ $workflow->id }}" {{ request('workflow_id') == $workflow->id ? 'selected' : '' }}>
                                        {{ $workflow->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150">
                            Filter
                        </button>
                        <a href="{{ route('document-approvals.pending-approvals') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 transition ease-in-out duration-150">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pending Approvals Table -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Pending Approvals</h3>
                    @if($requests->count() > 0)
                        <div class="flex space-x-2">
                            <button type="button" 
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    onclick="bulkAction('approve')">
                                Bulk Approve
                            </button>
                            <button type="button" 
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    onclick="bulkAction('reject')">
                                Bulk Reject
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reference
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Requester
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Document Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Title
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Priority
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Deadline
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($requests as $request)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="request_ids[]" value="{{ $request->id }}" 
                                           class="request-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $request->reference_number ?? 'DA-' . str_pad($request->id, 6, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $request->requester->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ucwords(str_replace('_', ' ', $request->document_type)) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ Str::limit($request->title, 40) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @switch($request->priority)
                                        @case('high')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                High
                                            </span>
                                            @break
                                        @case('medium')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Medium
                                            </span>
                                            @break
                                        @case('low')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Low
                                            </span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($request->priority) }}
                                            </span>
                                    @endswitch
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($request->deadline)
                                        <span class="{{ $request->deadline->isPast() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                            {{ $request->deadline->format('M d, Y') }}
                                        </span>
                                        @if($request->deadline->isPast())
                                            <span class="block text-xs text-red-500">Overdue</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">No deadline</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('document-approvals.show', $request) }}" 
                                           class="text-indigo-600 hover:text-indigo-900">
                                            View
                                        </a>
                                        <button type="button" 
                                                class="text-green-600 hover:text-green-900"
                                                onclick="approveRequest({{ $request->id }})">
                                            Approve
                                        </button>
                                        <button type="button" 
                                                class="text-red-600 hover:text-red-900"
                                                onclick="rejectRequest({{ $request->id }})">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                        <p class="text-lg font-medium text-gray-900 mb-2">No pending approvals</p>
                                        <p class="text-gray-500">All document approval requests have been processed.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div id="approval-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Approve Request</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="modal-message">
                    Are you sure you want to approve this document request?
                </p>
                <div class="mt-4">
                    <label for="approval-comments" class="block text-sm font-medium text-gray-700">Comments (Optional)</label>
                    <textarea id="approval-comments" rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                              placeholder="Add any comments..."></textarea>
                </div>
            </div>
            <div class="flex justify-center space-x-3 px-4 py-3">
                <button id="confirm-action" 
                        class="px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Confirm
                </button>
                <button onclick="closeModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function approveRequest(requestId) {
    document.getElementById('modal-title').textContent = 'Approve Request';
    document.getElementById('modal-message').textContent = 'Are you sure you want to approve this document request?';
    document.getElementById('confirm-action').onclick = function() {
        submitAction('approve', [requestId]);
    };
    document.getElementById('approval-modal').classList.remove('hidden');
}

function rejectRequest(requestId) {
    document.getElementById('modal-title').textContent = 'Reject Request';
    document.getElementById('modal-message').textContent = 'Are you sure you want to reject this document request?';
    document.getElementById('confirm-action').onclick = function() {
        submitAction('reject', [requestId]);
    };
    document.getElementById('approval-modal').classList.remove('hidden');
}

function bulkAction(action) {
    const checkboxes = document.querySelectorAll('.request-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one request to ' + action + '.');
        return;
    }
    
    const requestIds = Array.from(checkboxes).map(cb => cb.value);
    const actionText = action === 'approve' ? 'approve' : 'reject';
    
    document.getElementById('modal-title').textContent = 'Bulk ' + actionText.charAt(0).toUpperCase() + actionText.slice(1);
    document.getElementById('modal-message').textContent = `Are you sure you want to ${actionText} ${requestIds.length} selected request(s)?`;
    document.getElementById('confirm-action').onclick = function() {
        submitAction(action, requestIds);
    };
    document.getElementById('approval-modal').classList.remove('hidden');
}

function submitAction(action, requestIds) {
    const comments = document.getElementById('approval-comments').value;
    
    // Here you would normally submit via AJAX or form
    console.log('Action:', action, 'IDs:', requestIds, 'Comments:', comments);
    
    // For now, just close modal and show success message
    closeModal();
    alert(`${action.charAt(0).toUpperCase() + action.slice(1)} action submitted successfully!`);
}

function closeModal() {
    document.getElementById('approval-modal').classList.add('hidden');
    document.getElementById('approval-comments').value = '';
}

// Select all checkbox functionality
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.request-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>
@endsection