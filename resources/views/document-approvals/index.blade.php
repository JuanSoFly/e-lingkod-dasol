<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Approval Workflows') }}
            </h2>
            @can('create', App\Models\DocumentApprovalRequest::class)
                <x-primary-button onclick="window.location.href='{{ route('document-approvals.create') }}'">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    New Request
                </x-primary-button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
                <form method="GET" action="{{ route('document-approvals.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Statuses</option>
                            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                            <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                            <option value="under_review" @selected(request('status') === 'under_review')>Under Review</option>
                            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                        </select>
                    </div>
                    
                    <div>
                        <x-input-label for="type" value="Document Type" />
                        <x-text-input id="type" name="type" type="text" value="{{ request('type') }}" placeholder="Enter document type" />
                    </div>
                    
                    <div>
                        <x-input-label for="employee_id" value="Employee" />
                        <select id="employee_id" name="employee_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>
                                    {{ $employee->last_name }}, {{ $employee->first_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <x-primary-button type="submit">Filter</x-primary-button>
                        <x-secondary-button type="button" onclick="window.location.href='{{ route('document-approvals.index') }}'">Reset</x-secondary-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($requests as $request)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $request->reference_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $request->employee->last_name }}, {{ $request->employee->first_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $request->document_type }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @include('document-approvals.partials.status-badge', ['status' => $request->status])
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $request->submitted_at ? $request->submitted_at->format('M j, Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        @can('view', $request)
                                            <a href="{{ route('document-approvals.show', $request) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </a>
                                        @endcan
                                        
                                        @can('update', $request)
                                            @if($request->status === 'draft')
                                                <a href="{{ route('document-approvals.edit', $request) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-md hover:bg-blue-200 transition-colors duration-150">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </a>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No approval workflow requests found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($requests->hasPages())
                    <div class="mt-6">
                        {{ $requests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>