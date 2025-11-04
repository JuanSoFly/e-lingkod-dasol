@extends('layouts.app')

@section('title', 'Office Assignments - ' . $office->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('opcr.offices.index') }}" class="hover:text-gray-700">Office Management</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="text-gray-900">Assignments</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">{{ $office->name }} Assignments</h1>
                <p class="mt-2 text-gray-600">Manage department heads, assessors, and approvers for {{ $office->name }}</p>
            </div>
            @can('opcr.create')
            <a href="{{ route('opcr.offices.assignments.create', $office) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Assignment
            </a>
            @endcan
        </div>
    </div>

    <!-- Access Notice for Department Heads -->
    @if(auth()->user()->hasRole('Department Head') && !auth()->user()->hasAnyRole(['HR Admin', 'Super Admin']))
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h3 class="text-sm font-medium text-blue-800">Department Head Access</h3>
                <p class="text-sm text-blue-600 mt-1">You can only view and manage assignments for your assigned office. You can edit your own assignment details but cannot modify other Department Heads, Assessors, or Final Approvers.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Office Info Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $office->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $office->code }} • Level {{ $office->level }} Office</p>
                    @if($office->description)
                    <p class="text-sm text-gray-600 mt-1">{{ $office->description }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center space-x-4 text-right">
                <div>
                    <div class="text-2xl font-bold text-blue-600">{{ $office->employees_count ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Employees</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600">{{ $activeCount }}</div>
                    <div class="text-xs text-gray-500">Active Assignments</div>
                </div>
                @if($inactiveCount > 0)
                <div>
                    <div class="text-2xl font-bold text-gray-500">{{ $inactiveCount }}</div>
                    <div class="text-xs text-gray-500">Inactive Assignments</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('opcr.offices.assignments.index', $office) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search by Employee</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee name..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Roles</option>
                    <option value="Department Head" {{ request('role') == 'Department Head' ? 'selected' : '' }}>Department Head</option>
                    <option value="Assessor" {{ request('role') == 'Assessor' ? 'selected' : '' }}>Assessor (PMT)</option>
                    <option value="Final Approver" {{ request('role') == 'Final Approver' ? 'selected' : '' }}>Final Approver (Mayor)</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Active Assignments Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Active Assignments</h3>
        </div>

        @if($activeAssignments->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned By</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($activeAssignments as $assignment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 relative">
                                    @if(!$assignment->employee_id && $assignment->user?->email === 'admin@example.com')
                                        <!-- System Account Badge -->
                                        <div class="absolute -top-1 -right-1 h-3 w-3 bg-indigo-600 rounded-full flex items-center justify-center">
                                            <svg class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <img class="h-10 w-10 rounded-full @if(!$assignment->employee_id && $assignment->user?->email !== 'admin@example.com') ring-2 ring-yellow-400 @endif"
                                         src="{{ $assignment->user?->employee?->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($assignment->user?->name ?? 'User') . '&color=7F9CF5&background=EBF4FF' }}"
                                         alt="{{ $assignment->user?->name ?? 'No photo' }}">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 flex items-center">
                                        {{ $assignment->user?->employee?->full_name ?? $assignment->user?->name }}
                                        @if(!$assignment->employee_id && $assignment->user?->email !== 'admin@example.com')
                                            <!-- Data Quality Warning -->
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800" title="Missing employee data">
                                                ⚠️
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        @if($assignment->employee_id)
                                            {{ $assignment->user?->employee?->employee_number }}
                                        @elseif($assignment->user?->email === 'admin@example.com')
                                            <span class="text-indigo-600 font-medium">System Account</span>
                                        @else
                                            <span class="text-yellow-600">No employee data</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($assignment->role === 'Department Head') bg-purple-100 text-purple-800
                                @elseif($assignment->role === 'Assessor') bg-blue-100 text-blue-800
                                @elseif($assignment->role === 'Final Approver') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $assignment->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>From: {{ $assignment->assigned_date ? $assignment->assigned_date->format('M d, Y') : 'Not set' }}</div>
                            @if($assignment->ended_date)
                            <div>To: {{ $assignment->ended_date->format('M d, Y') }}</div>
                            @else
                            <div class="text-green-600">Present</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $assignment->assignedBy?->name ?? 'System' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                @if($assignment->employee)
                                <a href="{{ route('employees.show', $assignment->employee) }}"
                                   class="text-gray-400 hover:text-gray-600" title="View Employee Profile">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                @endif

                                @php
                                $canEditAssignment = false;
                                $isOwnAssignment = false;

                                // Check if user can edit this assignment
                                if (auth()->user()->hasAnyRole(['Super Admin', 'HR Admin'])) {
                                    $canEditAssignment = true;
                                } elseif (auth()->user()->hasRole('Department Head') &&
                                         $assignment->user_id === auth()->id() &&
                                         $assignment->role === 'Department Head') {
                                    $canEditAssignment = true;
                                    $isOwnAssignment = true;
                                }
                                @endphp

                                @if($canEditAssignment)
                                <a href="{{ route('opcr.offices.assignments.edit', [$office, $assignment]) }}"
                                   class="text-blue-600 hover:text-blue-900"
                                   title="{{ $isOwnAssignment ? 'Edit Your Assignment' : 'Edit Assignment' }}">
                                    @if($isOwnAssignment)
                                    <!-- Different icon for own assignment -->
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    @endif
                                </a>
                                @elseif($assignment->role === 'Assessor' || $assignment->role === 'Final Approver')
                                <span class="text-gray-300 cursor-not-allowed"
                                      title="Only HR Admin can edit {{ $assignment->role }} assignments">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No active assignments</h3>
            <p class="mt-1 text-sm text-gray-500">This office currently has no active assignments.</p>
            @can('opcr.create')
            <div class="mt-6">
                <a href="{{ route('opcr.offices.assignments.create', $office) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Assignment
                </a>
            </div>
            @endcan
        </div>
        @endif
    </div>

    <!-- Inactive Assignments Collapsible Section -->
    @if($inactiveCount > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <button type="button"
                class="w-full px-6 py-4 border-b border-gray-200 text-left hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                onclick="document.getElementById('inactive-section').classList.toggle('hidden')">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <h3 class="text-lg font-medium text-gray-900">Inactive Assignments ({{ $inactiveCount }})</h3>
                    <span class="text-sm text-gray-500">For audit purposes</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        Historical records
                    </span>
                    <svg id="collapse-icon" class="w-5 h-5 text-gray-500 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </button>

        <div id="inactive-section" class="hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned By</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($inactiveAssignments as $assignment)
                        <tr class="hover:bg-gray-50 border-l-4 border-gray-300">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 opacity-75">
                                        <img class="h-10 w-10 rounded-full grayscale"
                                             src="{{ $assignment->user?->employee?->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($assignment->user?->name ?? 'User') . '&color=7F9CF5&background=EBF4FF' }}"
                                             alt="{{ $assignment->user?->name ?? 'No photo' }}">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-600">
                                            {{ $assignment->user?->employee?->full_name ?? $assignment->user?->name }}
                                        </div>
                                        <div class="text-sm text-gray-400">
                                            {{ $assignment->user?->employee?->employee_number ?? 'No ID' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ $assignment->role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                <div>From: {{ $assignment->assigned_date ? $assignment->assigned_date->format('M d, Y') : 'Not set' }}</div>
                                @if($assignment->ended_date)
                                <div>To: {{ $assignment->ended_date->format('M d, Y') }}</div>
                                @else
                                <div class="text-gray-400">Not ended</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    Inactive
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                {{ $assignment->assignedBy?->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($assignment->employee)
                                    <a href="{{ route('employees.show', $assignment->employee) }}"
                                       class="text-gray-300 hover:text-gray-500" title="View Employee Profile">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @endif

                                    <span class="text-gray-300 cursor-not-allowed" title="Inactive assignments cannot be edited">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- JavaScript for collapsible section -->
    @if($inactiveCount > 0)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.querySelector('[onclick*="inactive-section"]');
            const icon = document.getElementById('collapse-icon');

            if (button && icon) {
                button.addEventListener('click', function() {
                    icon.classList.toggle('rotate-180');
                });
            }
        });
    </script>
    @endif
</div>
@endsection