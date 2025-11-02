@extends('layouts.app')

@section('title', 'Office Management - OPCR')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Office Management</h1>
                <p class="mt-2 text-gray-600">Manage municipal offices and department head assignments for OPCR</p>
            </div>
            @can('opcr.create')
            <a href="{{ route('opcr.offices.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Office
            </a>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('opcr.offices.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Offices</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or code..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Office Level</label>
                <select name="level" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Levels</option>
                    <option value="1" {{ request('level') == '1' ? 'selected' : '' }}>Level 1 - Executive</option>
                    <option value="2" {{ request('level') == '2' ? 'selected' : '' }}>Level 2 - Administrative</option>
                    <option value="3" {{ request('level') == '3' ? 'selected' : '' }}>Level 3 - Departments</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Offices Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($offices as $office)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
            <!-- Office Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $office->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $office->code }}</p>
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Level {{ $office->level }}
                            </span>
                            @if($office->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                Active
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">
                                Inactive
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                @if($office->description)
                <p class="mt-3 text-sm text-gray-600">{{ Str::limit($office->description, 100) }}</p>
                @endif
            </div>

            <!-- Office Stats -->
            <div class="p-6 bg-gray-50">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-blue-600">{{ $office->employees_count }}</div>
                        <div class="text-xs text-gray-500">Employees</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600">{{ $office->mfos_count }}</div>
                        <div class="text-xs text-gray-500">MFOs</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-purple-600">{{ $office->opcr_count }}</div>
                        <div class="text-xs text-gray-500">OPCRs</div>
                    </div>
                </div>
            </div>

            <!-- Office Actions -->
            <div class="p-6 border-t border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-gray-600">Department Head:</span>
                    @if($office->departmentHead)
                    <span class="text-sm font-medium text-gray-900">{{ $office->departmentHead->full_name }}</span>
                    @else
                    <span class="text-sm text-gray-400">Not Assigned</span>
                    @endif
                </div>

                <div class="flex space-x-2">
                    <a href="{{ route('opcr.offices.assignments.index', $office) }}"
                       class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Assignments
                    </a>
                    @can('opcr.edit')
                    <a href="{{ route('opcr.offices.edit', $office) }}"
                       class="inline-flex items-center px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No offices found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first office.</p>
                @can('opcr.create')
                <div class="mt-6">
                    <a href="{{ route('opcr.offices.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Office
                    </a>
                </div>
                @endcan
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($offices->hasPages())
    <div class="mt-8">
        {{ $offices->links() }}
    </div>
    @endif
</div>
@endsection