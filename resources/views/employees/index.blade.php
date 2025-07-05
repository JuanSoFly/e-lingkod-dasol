<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employee Management') }}
            </h2>
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

        <!-- Page Header -->
        <div class="bg-white p-4 sm:p-6 rounded-lg border border-gray-200 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">All Employees</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        @if(request('search') || request('department') || request('position') || request('employment_status'))
                            {{ $employees->total() }} of {{ \App\Models\Employee::count() }} employees
                            <a href="{{ route('employees.index') }}" class="text-blue-600 hover:text-blue-800 ml-2 text-xs">Clear filters</a>
                        @else
                            {{ $employees->total() }} total employees
                        @endif
                    </p>
                </div>
                <div>
                    @can('employee.create')
                    <a href="{{ route('employees.create') }}">
                        <x-primary-button class="w-full sm:w-auto justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Employee
                        </x-primary-button>
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white p-4 sm:p-6 rounded-lg border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('employees.index') }}" class="space-y-4">
                <!-- Search Bar -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search employees by name, email, position, department, or employee number..." 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Filters Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Department Filter -->
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department" id="department" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                                    {{ $department }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Position Filter -->
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <select name="position" id="position" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Positions</option>
                            @foreach($positions as $position)
                                <option value="{{ $position }}" {{ request('position') == $position ? 'selected' : '' }}>
                                    {{ $position }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Employment Status Filter -->
                    <div>
                        <label for="employment_status" class="block text-sm font-medium text-gray-700 mb-1">Employment Status</label>
                        <select name="employment_status" id="employment_status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Statuses</option>
                            @foreach($employmentStatuses as $status)
                                <option value="{{ $status }}" {{ request('employment_status') == $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-2 sm:items-end">
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search
                        </button>
                        @if(request()->hasAny(['search', 'department', 'position', 'employment_status']))
                        <a href="{{ route('employees.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Mobile Card View -->
        <div class="lg:hidden space-y-4">
            @forelse ($employees as $employee)
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <div class="flex items-start space-x-4">
                        <!-- Avatar -->
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0">
                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                        </div>
                        
                        <!-- Employee Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900 truncate employee-name">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 truncate employee-email">{{ $employee->email }}</p>
                                </div>
                                
                                <!-- Quick Actions -->
                                <div class="flex space-x-1 flex-shrink-0 ml-2">
                                    <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center p-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded hover:bg-gray-200 transition-colors duration-150">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @can('employee.edit')
                                    <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center p-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded hover:bg-gray-200 transition-colors duration-150">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                </div>
                            </div>
                            
                            <!-- Employee Details -->
                            <div class="mt-3 space-y-2">
                                <div class="flex items-center text-xs text-gray-500">
                                    <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                                    </svg>
                                    <span class="truncate employee-position">{{ $employee->position }}</span>
                                </div>
                                
                                <div class="flex items-center text-xs text-gray-500">
                                    <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span class="truncate employee-department">{{ $employee->department }}</span>
                                </div>
                                
                                <div class="flex items-center text-xs text-gray-500">
                                    <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 012-2h4a1 1 0 012 2v4m0 0v11a1 1 0 01-1 1H9a1 1 0 01-1-1V7m0 0h8m-8 0h8m-8 0V7"></path>
                                    </svg>
                                    <span>Hired: {{ $employee->date_hired?->format('M d, Y') ?? 'Not provided' }}</span>
                                </div>
                            </div>
                            
                            <!-- Actions Row for Mobile -->
                            <div class="mt-3 flex space-x-2">
                                <a href="{{ route('employees.show', $employee) }}" class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded hover:bg-gray-200 transition-colors duration-150">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </a>
                                @can('employee.edit')
                                <a href="{{ route('employees.edit', $employee) }}" class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-100 text-blue-700 text-xs font-medium rounded hover:bg-blue-200 transition-colors duration-150">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </a>
                                @endcan
                                @can('employee.delete')
                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this employee? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors duration-150">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-500 mb-2">No employees found</h3>
                    <p class="text-sm text-gray-400 mb-4">
                        @if(request()->hasAny(['search', 'department', 'position', 'employment_status']))
                            Try adjusting your search criteria or <a href="{{ route('employees.index') }}" class="text-blue-600 hover:text-blue-800">clear all filters</a>.
                        @else
                            Get started by adding your first employee.
                        @endif
                    </p>
                    @can('employee.create')
                    <a href="{{ route('employees.create') }}">
                        <x-primary-button>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Employee
                        </x-primary-button>
                    </a>
                    @endcan
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Hired</th>
                            <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($employees as $employee)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 employee-name">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                            <div class="text-sm text-gray-500 employee-email">{{ $employee->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 employee-position">{{ $employee->position }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 employee-department">{{ $employee->department }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $employee->date_hired?->format('M d, Y') ?? 'Not provided' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </a>
                                        @can('employee.edit')
                                        <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit
                                        </a>
                                        @endcan
                                        @can('employee.delete')
                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this employee? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-500 mb-2">No employees found</h3>
                                    <p class="text-sm text-gray-400 mb-4">Get started by adding your first employee.</p>
                                    @can('employee.create')
                                    <a href="{{ route('employees.create') }}">
                                        <x-primary-button>
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Add Employee
                                        </x-primary-button>
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($employees->hasPages())
        <div class="bg-white px-4 py-3 border border-gray-200 rounded-lg">
            {{ $employees->links() }}
        </div>
        @endif
    </div>

    <!-- Enhanced Search Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            const form = searchInput?.closest('form');
            let searchTimeout;

            // Auto-submit on search input with debounce
            if (searchInput && form) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        if (this.value.length >= 2 || this.value.length === 0) {
                            form.submit();
                        }
                    }, 500); // 500ms delay
                });

                // Submit on Enter key
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(searchTimeout);
                        form.submit();
                    }
                });
            }

            // Auto-submit on filter changes
            const filterSelects = form?.querySelectorAll('select');
            if (filterSelects) {
                filterSelects.forEach(select => {
                    select.addEventListener('change', function() {
                        form.submit();
                    });
                });
            }

            // Clear search on Escape key
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                        form.submit();
                    }
                });
            }

            // Focus search input on page load if no search value
            if (searchInput && !searchInput.value) {
                searchInput.focus();
            }

            // Add loading state to search button
            const searchButton = form?.querySelector('button[type="submit"]');
            if (searchButton && form) {
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
        });

        // Highlight search terms in results
        function highlightSearchTerms() {
            const searchTerm = new URLSearchParams(window.location.search).get('search');
            if (!searchTerm) return;

            const terms = searchTerm.split(' ').filter(term => term.length > 1);
            const employeeCards = document.querySelectorAll('.employee-name, .employee-email, .employee-position, .employee-department');
            
            terms.forEach(term => {
                employeeCards.forEach(element => {
                    const regex = new RegExp(`(${term})`, 'gi');
                    element.innerHTML = element.innerHTML.replace(regex, '<mark class="bg-yellow-200 px-0.5 rounded">$1</mark>');
                });
            });
        }

        // Call highlight function after DOM is loaded
        document.addEventListener('DOMContentLoaded', highlightSearchTerms);
    </script>
</x-app-layout>
