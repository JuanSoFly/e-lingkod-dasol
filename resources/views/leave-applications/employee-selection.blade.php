@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Select Employee for Leave Card</h1>
        <p class="text-gray-600">Choose an employee to view their leave card</p>
    </div>

    <!-- Search -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <form method="GET" action="{{ route('leave-card.view') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search employees by name or employee number..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                @if(request()->filled('search'))
                    <a href="{{ route('leave-card.view') }}"
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors duration-150">
                        Clear
                    </a>
                @endif
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150">
                    Search
                </button>
            </div>
        </form>
        @if(request()->filled('search'))
            <div class="mt-3 text-sm text-gray-600">
                @if($employees->count() > 0)
                    Found {{ $employees->count() }} result{{ $employees->count() != 1 ? 's' : '' }} for "{{ request('search') }}"
                @else
                    No employees found for "{{ request('search') }}"
                @endif
            </div>
        @endif
    </div>

    <!-- Employee List -->
    <div class="bg-white shadow rounded-lg">
        <!-- Desktop & Tablet View -->
        <div class="hidden sm:block">
            <div class="overflow-x-auto lg:overflow-x-visible">
                <table class="w-full divide-y divide-gray-200 min-w-[320px] sm:min-w-[400px] md:min-w-[500px] lg:min-w-[600px] xl:min-w-[700px]" id="employeeTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 sm:px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[140px] sm:min-w-[180px] md:min-w-[200px]">Employee</th>
                            <th class="px-2 sm:px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[80px] sm:min-w-[100px] md:min-w-[120px] hidden sm:table-cell">Employee ID</th>
                            <th class="px-2 sm:px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px] sm:min-w-[120px] md:min-w-[150px] hidden md:table-cell">Position</th>
                            <th class="px-2 sm:px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px] sm:min-w-[150px] md:min-w-[180px] hidden lg:table-cell">Department</th>
                            <th class="relative px-2 sm:px-3 lg:px-4 py-3 min-w-[80px] sm:min-w-[100px]"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($employees as $employee)
                            <tr class="employee-row hover:bg-gray-50 transition-colors">
                                <td class="px-2 sm:px-3 lg:px-4 py-3 min-w-[140px] sm:min-w-[180px] md:min-w-[200px]">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500 truncate">{{ $employee->email }}</div>
                                </td>
                                <td class="px-2 sm:px-3 lg:px-4 py-3 text-sm text-gray-900 font-mono text-xs min-w-[80px] sm:min-w-[100px] md:min-w-[120px] hidden sm:table-cell">{{ $employee->employee_number ?? 'N/A' }}</td>
                                <td class="px-2 sm:px-3 lg:px-4 py-3 min-w-[100px] sm:min-w-[120px] md:min-w-[150px] hidden md:table-cell">
                                    <div class="text-sm text-gray-900 truncate" title="{{ $employee->position ?? 'N/A' }}">
                                        {{ $employee->position ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-2 sm:px-3 lg:px-4 py-3 min-w-[120px] sm:min-w-[150px] md:min-w-[180px] hidden lg:table-cell">
                                    <div class="text-sm text-gray-900 truncate" title="{{ $employee->department ?? 'N/A' }}">
                                        {{ $employee->department ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-2 sm:px-3 lg:px-4 py-3 text-right text-sm font-medium min-w-[80px] sm:min-w-[100px]">
                                    <a href="{{ route('leave-card.view', $employee->id) }}"
                                       class="inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 whitespace-nowrap touch-target">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <span class="hidden sm:inline">View</span>
                                        <span class="sm:hidden">V</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 sm:px-6 py-4 text-center text-sm text-gray-500">
                                    No employees found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View -->
        <div class="sm:hidden">
            <div class="divide-y divide-gray-200">
                @forelse ($employees as $employee)
                    <div class="p-3 sm:p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex flex-col space-y-2 sm:space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0 pr-2">
                                    <h3 class="text-sm font-medium text-gray-900 truncate mb-1">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 truncate mb-1">{{ $employee->email }}</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="{{ route('leave-card.view', $employee->id) }}"
                                       class="inline-flex items-center px-2 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 whitespace-nowrap touch-target">
                                        <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        View
                                    </a>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-1.5 text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-gray-600 flex-shrink-0">ID:</span>
                                    <span class="font-mono text-gray-900 truncate">{{ $employee->employee_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="font-medium text-gray-600 flex-shrink-0">Position:</span>
                                    <span class="text-gray-900 truncate flex-1">{{ $employee->position ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <span class="font-medium text-gray-600 flex-shrink-0">Dept:</span>
                                    <span class="text-gray-900 truncate flex-1">{{ $employee->department ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-3 sm:p-4 text-center text-sm text-gray-500">
                        No employees found.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination -->
        @if ($employees->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Real-time Search Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        const form = searchInput?.closest('form');
        const searchButton = form?.querySelector('button[type="submit"]');
        let searchTimeout;

        // Auto-submit on search input with debounce
        if (searchInput && form) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        form.submit();
                    }
                }, 800); // 800ms delay for beginner typists
            });

            // Submit on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimeout);
                    form.submit();
                }
            });

            // Clear search on Escape key
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    form.submit();
                }
            });

            // Focus search input on page load if empty
            if (!searchInput.value) {
                searchInput.focus();
            }

            // Add loading state to search button
            if (searchButton) {
                form.addEventListener('submit', function() {
                    searchButton.innerHTML = `
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Searching...
                    `;
                    searchButton.disabled = true;
                });
            }
        }

        // Highlight search terms in results
        function highlightSearchTerms() {
            const searchTerm = new URLSearchParams(window.location.search).get('search');
            if (!searchTerm) return;

            const terms = searchTerm.split(' ').filter(term => term.length > 1);
            const employeeCells = document.querySelectorAll('.employee-row td');

            terms.forEach(term => {
                employeeCells.forEach(element => {
                    if (!element.querySelector('button')) { // Don't highlight inside buttons
                        const regex = new RegExp(`(${term})`, 'gi');
                        element.innerHTML = element.innerHTML.replace(regex, '<mark class="bg-yellow-200 px-0.5 rounded">$1</mark>');
                    }
                });
            });
        }

        // Call highlight function after DOM is loaded
        highlightSearchTerms();
    });
</script>

@endsection