<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employee Archives') }}
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

        @if (session('error'))
            <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Archive Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total Archived</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $stats['total_archived'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">This Month</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $stats['archived_this_month'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Active Users</p>
                        <p class="text-lg font-semibold text-gray-900">{{ \App\Models\Employee::count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total Employees</p>
                        <p class="text-lg font-semibold text-gray-900">{{ \App\Models\Employee::withTrashed()->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Header with Filters -->
        <div class="bg-white p-4 sm:p-6 rounded-lg border border-gray-200 shadow-sm">
            <div class="flex flex-col space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Archived Employees</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $archivedEmployees->total() }} archived employees
                            @if(request()->anyFilled(['search', 'department', 'position', 'archived_by']))
                                <a href="{{ route('employees.archive.index') }}" class="text-blue-600 hover:text-blue-800 ml-2 text-xs">Clear filters</a>
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 sm:space-x-3">
                        @can('employee.view')
                        <a href="{{ route('employees.archive.export', request()->query()) }}" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 w-full sm:w-auto">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Archives
                        </a>
                        @endcan
                        <a href="{{ route('employees.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 w-full sm:w-auto">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Employees
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" action="{{ route('employees.archive.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, Email, Employee #"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" name="department" value="{{ request('department') }}" placeholder="Department"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                            <input type="text" name="position" value="{{ request('position') }}" placeholder="Position"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Archived By</label>
                            <select name="archived_by" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Users</option>
                                @foreach($usersWithArchives as $id => $name)
                                    <option value="{{ $id }}" {{ request('archived_by') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 sm:items-center">
                        <div class="flex space-x-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="flex space-x-2 sm:mt-6">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Apply Filters
                            </button>
                            <a href="{{ route('employees.archive.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Archives Table -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            @if($archivedEmployees->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Employee
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Position
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Department
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date Hired
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Archived Info
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($archivedEmployees as $employee)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-600">
                                                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                                </div>
                                                <div class="text-sm text-gray-500">{{ $employee->employee_number }}</div>
                                                <div class="text-sm text-gray-500">{{ $employee->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $employee->position }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $employee->department }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $employee->date_hired->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $employee->archived_at ? $employee->archived_at->format('M d, Y H:i') : 'Unknown' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            by {{ $employee->archivedBy->name ?? 'Unknown' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <a href="{{ route('employees.archive.show', $employee) }}"
                                           class="text-blue-600 hover:text-blue-900">
                                            View
                                        </a>
                                        @can('employee.delete')
                                        <button onclick="restoreEmployee({{ $employee->id }}, '{{ $employee->first_name }} {{ $employee->last_name }}', 'index')"
                                                data-employee-id="{{ $employee->id }}"
                                                data-action="restore"
                                                class="text-green-600 hover:text-green-900 transition-opacity duration-200">
                                            Restore
                                        </button>
                                        @can('employee.forceDelete')
                                        <button onclick="forceDeleteEmployee({{ $employee->id }}, '{{ $employee->first_name }} {{ $employee->last_name }}')"
                                                data-employee-id="{{ $employee->id }}"
                                                data-action="delete"
                                                class="text-red-600 hover:text-red-900 transition-opacity duration-200">
                                            Delete
                                        </button>
                                        @endcan
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        {{ $archivedEmployees->links() }}
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ $archivedEmployees->firstItem() }}</span>
                                to
                                <span class="font-medium">{{ $archivedEmployees->lastItem() }}</span>
                                of
                                <span class="font-medium">{{ $archivedEmployees->total() }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            {{ $archivedEmployees->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No archived employees</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request()->anyFilled(['search', 'department', 'position', 'archived_by']))
                            No archived employees match your search criteria.
                        @else
                            No employees have been archived yet.
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('employees.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            View Active Employees
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

  @push('scripts')
<script>
function restoreEmployee(employeeId, employeeName) {
    if (!confirm(`Are you sure you want to restore ${employeeName} to active status?`)) {
        return;
    }

    fetch(`/employees/archive/${employeeId}/restore`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to restore employee.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while restoring the employee.');
    });
}

function forceDeleteEmployee(employeeId, employeeName) {
    const confirmation1 = confirm(`⚠️ WARNING: This will permanently delete ${employeeName} and all their data. This action cannot be undone.\n\nDo you want to continue?`);

    if (!confirmation1) {
        return;
    }

    const nameConfirmation = prompt(`Type the employee's name exactly to confirm permanent deletion:\n\n"${employeeName}"`);

    if (nameConfirmation !== employeeName) {
        alert('Employee name confirmation does not match. Deletion cancelled.');
        return;
    }

    const finalConfirmation = confirm(`🚨 FINAL WARNING: This is your last chance to cancel.\n\nDeleting: ${employeeName}\nEmployee ID: ${employeeId}\n\nThis action cannot be undone.\n\nAre you absolutely sure?`);

    if (!finalConfirmation) {
        return;
    }

    fetch(`/employees/archive/${employeeId}/force-delete`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            confirmation: 'PERMANENTLY_DELETE_' + employeeId,
            employee_name: employeeName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to permanently delete employee.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while permanently deleting the employee.');
    });
}
</script>
@endpush
</x-app-layout>

