<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            OPCR Workflows
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Office Performance Commitments</h3>
                            <p class="mt-1 text-sm text-gray-600">Manage and monitor OPCR workflows</p>
                        </div>
                        @php
                            $departmentHeadOfficeIds = $opcrContext['department_head_office_ids'] ?? collect();
                        @endphp
                        @if(in_array($userRole, ['Super Admin', 'HR Admin', 'Department Head']))
                            <a href="{{ route('opcr.workflows.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New OPCR
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('opcr.workflows.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-input-label for="period_id" value="Performance Period" />
                                <select id="period_id" name="period_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Periods</option>
                                    @foreach($periods as $period)
                                        <option value="{{ $period->id }}" {{ $filters['period_id'] == $period->id ? 'selected' : '' }}>
                                            {{ $period->year }} - {{ $period->semester }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="office_id" value="Office" />
                                <select id="office_id" name="office_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Offices</option>
                                    @foreach($offices as $office)
                                        <option value="{{ $office->id }}" {{ $filters['office_id'] == $office->id ? 'selected' : '' }}>
                                            {{ $office->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="workflow_state" value="Status" />
                                <select id="workflow_state" name="workflow_state" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Statuses</option>
                                    <option value="draft" {{ $filters['workflow_state'] == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="committed" {{ $filters['workflow_state'] == 'committed' ? 'selected' : '' }}>Committed</option>
                                    <option value="in_progress" {{ $filters['workflow_state'] == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="evaluation" {{ $filters['workflow_state'] == 'evaluation' ? 'selected' : '' }}>Evaluation</option>
                                    <option value="final_approval" {{ $filters['workflow_state'] == 'final_approval' ? 'selected' : '' }}>Final Approval</option>
                                    <option value="approved" {{ $filters['workflow_state'] == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="returned" {{ $filters['workflow_state'] == 'returned' ? 'selected' : '' }}>Returned</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="search" value="Search" />
                                <input type="text" id="search" name="search" value="{{ $filters['search'] }}" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Office or name...">
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('opcr.workflows.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Clear
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Workflows List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Office / Period
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Title
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Committed By
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Targets
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Updated
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($workflows as $workflow)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $workflow->office->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $workflow->period->year }} - {{ $workflow->period->semester }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $workflow->title }}</div>
                                        @if($workflow->description)
                                            <div class="text-sm text-gray-500 truncate">{{ Str::limit($workflow->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $workflow->committedBy->employee->full_name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($workflow->workflow_state === 'draft') bg-gray-100 text-gray-800
                                            @elseif($workflow->workflow_state === 'committed') bg-blue-100 text-blue-800
                                            @elseif($workflow->workflow_state === 'in_progress') bg-yellow-100 text-yellow-800
                                            @elseif($workflow->workflow_state === 'evaluation') bg-orange-100 text-orange-800
                                            @elseif($workflow->workflow_state === 'final_approval') bg-purple-100 text-purple-800
                                            @elseif($workflow->workflow_state === 'approved') bg-green-100 text-green-800
                                            @elseif($workflow->workflow_state === 'returned') bg-red-100 text-red-800
                                            @endif">
                                            {{ ucwords(str_replace('_', ' ', $workflow->workflow_state)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $workflow->targets_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $workflow->updated_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('opcr.workflows.show', $workflow) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            View
                                        </a>
                                        @php
                                            $canManageOffice = in_array($userRole, ['Super Admin', 'HR Admin']) || ($userRole === 'Department Head' && $departmentHeadOfficeIds->contains($workflow->office_id));
                                        @endphp
                                        @if($canManageOffice && in_array($workflow->workflow_state, ['draft', 'returned']))
                                            <a href="{{ route('opcr.workflows.edit', $workflow) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                Edit
                                            </a>
                                        @endif
                                        @if($canManageOffice && $workflow->workflow_state === 'draft')
                                            <form method="POST" action="{{ route('opcr.workflows.destroy', $workflow) }}" class="inline" data-confirm="Are you sure you want to delete this OPCR workflow?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No OPCR workflows found</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            @if($filters['period_id'] || $filters['office_id'] || $filters['workflow_state'] || $filters['search'])
                                                Try adjusting your filters or
                                                <a href="{{ route('opcr.workflows.index') }}" class="text-indigo-600 hover:text-indigo-500">clear all filters</a>.
                                            @else
                                                Get started by creating your first OPCR workflow.
                                            @endif
                                        </p>
                                        @if(in_array($userRole, ['Super Admin', 'HR Admin', 'Department Head']) && !$filters['period_id'] && !$filters['office_id'] && !$filters['workflow_state'] && !$filters['search'])
                                            <div class="mt-6">
                                                <a href="{{ route('opcr.workflows.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    </svg>
                                                    Create OPCR Workflow
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($workflows->hasPages())
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            {{ $workflows->links() }}
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ $workflows->firstItem() }}</span>
                                    to
                                    <span class="font-medium">{{ $workflows->lastItem() }}</span>
                                    of
                                    <span class="font-medium">{{ $workflows->total() }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                {{ $workflows->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
