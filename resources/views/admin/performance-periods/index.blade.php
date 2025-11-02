<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Performance Period Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Header with Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Manage Performance Periods
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Configure and manage OPCR evaluation periods
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.performance-periods.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New Period
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                                <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                            <select id="year" name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Years</option>
                                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label for="per_page" class="block text-sm font-medium text-gray-700">Per Page</label>
                            <select id="per_page" name="per_page" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" form="filter-form" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Periods -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Periods</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $periods->total() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Periods -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Active Periods</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $periods->where('status', 'active')->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Periods -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Periods</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $periods->where('status', 'upcoming')->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Closed Periods -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-gray-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Closed Periods</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $periods->where('status', 'closed')->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Periods List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($periods->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Period
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date Range
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            OPCR Workflows
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Completion Rate
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($periods as $period)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $period->year }} - {{ ucfirst($period->semester) }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ID: #{{ str_pad($period->id, 4, '0', STR_PAD_LEFT) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $period->start_date->format('M d, Y') }} - {{ $period->end_date->format('M d, Y') }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $period->start_date->diffInDays($period->end_date) }} days
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($period->status === 'active')
                                                        bg-green-100 text-green-800
                                                    @elseif($period->status === 'upcoming')
                                                        bg-yellow-100 text-yellow-800
                                                    @elseif($period->status === 'closed')
                                                        bg-gray-100 text-gray-800
                                                    @else
                                                        bg-red-100 text-red-800
                                                    @endif">
                                                    {{ ucfirst($period->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $period->opcr_workflows_count ?? 0 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-1 mr-2">
                                                        <div class="text-sm text-gray-900">
                                                            {{ $period->completion_rate ?? 0 }}%
                                                        </div>
                                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $period->completion_rate ?? 0 }}%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <a href="{{ route('admin.performance-periods.show', $period) }}"
                                                       class="text-indigo-600 hover:text-indigo-900">
                                                        View
                                                    </a>
                                                    @if(in_array($period->status, ['active', 'upcoming']) && auth()->user()->can('performance-period.edit'))
                                                        <a href="{{ route('admin.performance-periods.edit', $period) }}"
                                                           class="text-gray-600 hover:text-gray-900">
                                                            Edit
                                                        </a>
                                                    @endif
                                                    @if($period->status === 'upcoming' && auth()->user()->can('performance-period.manage'))
                                                        <form action="{{ route('admin.performance-periods.activate', $period) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Activate this performance period?')"
                                                              class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="text-green-600 hover:text-green-900">
                                                                Activate
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($period->status === 'active' && auth()->user()->can('performance-period.manage'))
                                                        <form action="{{ route('admin.performance-periods.close', $period) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Close this performance period? This will prevent new submissions.')"
                                                              class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="text-orange-600 hover:text-orange-900">
                                                                Close
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $periods->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No performance periods found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating your first performance period.</p>
                            <div class="mt-6">
                                <a href="{{ route('admin.performance-periods.create') }}"
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Create Performance Period
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for filters -->
    <form id="filter-form" action="{{ route('admin.performance-periods.index') }}" method="GET" class="hidden">
        @if(request()->has('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        @if(request()->has('year'))
            <input type="hidden" name="year" value="{{ request('year') }}">
        @endif
        @if(request()->has('per_page'))
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @endif
    </form>
</x-app-layout>