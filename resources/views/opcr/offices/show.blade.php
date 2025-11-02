@extends('layouts.app')

@section('title', $office->name . ' - Office Details')

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
                    <span class="text-gray-900">{{ $office->name }}</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">{{ $office->name }}</h1>
                <p class="mt-2 text-gray-600">{{ $office->code }} • Level {{ $office->level }} Office</p>
                @if($office->description)
                <p class="mt-2 text-gray-600">{{ $office->description }}</p>
                @endif
            </div>
            <div class="flex items-center space-x-4">
                @can('opcr.create')
                <a href="{{ route('opcr.offices.assignments.create', $office) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Assignment
                </a>
                @endcan
                @can('opcr.edit')
                <a href="{{ route('opcr.offices.edit', $office) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Office
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Office Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_employees'] }}</div>
                    <div class="text-sm text-gray-500">Total Employees</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['active_assignments'] }}</div>
                    <div class="text-sm text-gray-500">Active Assignments</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['active_mfos'] }}</div>
                    <div class="text-sm text-gray-500">Active MFOs</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['opcr_workflows'] }}</div>
                    <div class="text-sm text-gray-500">OPCR Workflows</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Assignments -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Current Assignments</h3>
            <a href="{{ route('opcr.offices.assignments.index', $office) }}"
               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All →
            </a>
        </div>
        <div class="p-6">
            @if($office->assignments->count() > 0)
            <div class="space-y-4">
                @foreach($office->assignments->take(5) as $assignment)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
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
                            <div class="text-sm text-gray-500">{{ $assignment->employee?->employee_number ?? 'No ID' }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($assignment->role === 'Department Head') bg-purple-100 text-purple-800
                            @elseif($assignment->role === 'Assessor') bg-blue-100 text-blue-800
                            @elseif($assignment->role === 'Final Approver') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $assignment->role }}
                        </span>
                        <div class="text-xs text-gray-500 mt-1">
                            Assigned: {{ $assignment->assigned_date ? $assignment->assigned_date->format('M d, Y') : 'Not set' }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @if($office->assignments->count() > 5)
            <div class="mt-4 text-center">
                <a href="{{ route('opcr.offices.assignments.index', $office) }}"
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View all {{ $office->assignments->count() }} assignments →
                </a>
            </div>
            @endif
            @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No assignments yet</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding your first assignment.</p>
                @can('opcr.create')
                <div class="mt-4">
                    <a href="{{ route('opcr.offices.assignments.create', $office) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
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

    <!-- Quick Actions -->
    @can('opcr.create')
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('opcr.offices.assignments.create', $office) }}?role=Department+Head"
                   class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-200 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-purple-900">Assign Department Head</div>
                        <div class="text-xs text-purple-700">Manage office leadership</div>
                    </div>
                </a>

                <a href="{{ route('opcr.offices.assignments.create', $office) }}?role=Assessor"
                   class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-blue-900">Assign Assessor</div>
                        <div class="text-xs text-blue-700">Add performance evaluator</div>
                    </div>
                </a>

                <a href="{{ route('opcr.workflows.create') }}?office_id={{ $office->id }}"
                   class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-200 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-green-900">Create OPCR</div>
                        <div class="text-xs text-green-700">Start new performance review</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection