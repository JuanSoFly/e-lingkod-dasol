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
                    <div class="text-2xl font-bold text-green-600">{{ $activeAssignments }}</div>
                    <div class="text-xs text-gray-500">Active Assignments</div>
                </div>
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

    <!-- Assignments Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Current Assignments</h3>
        </div>

        @if($assignments->count() > 0)
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
                    @foreach($assignments as $assignment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full"
                                         src="{{ $assignment->employee?->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($assignment->user?->name ?? 'User') . '&color=7F9CF5&background=EBF4FF' }}"
                                         alt="{{ $assignment->user?->name ?? 'No photo' }}">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $assignment->employee?->full_name ?? $assignment->user?->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $assignment->employee?->employee_number ?? 'No ID' }}
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
                            @if($assignment->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Inactive
                            </span>
                            @endif
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
                                @can('opcr.edit')
                                <a href="{{ route('opcr.offices.assignments.edit', [$office, $assignment]) }}"
                                   class="text-blue-600 hover:text-blue-900" title="Edit Assignment">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($assignments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $assignments->links() }}
        </div>
        @endif
        @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No assignments found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first assignment for this office.</p>
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
</div>
@endsection