<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Audit Trail Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Statistics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <!-- Total Activities -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Activities</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $statistics['total_activities'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Activities -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Today</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $statistics['today_activities'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- This Week -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">This Week</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $statistics['this_week_activities'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- This Month -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">This Month</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $statistics['this_month_activities'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unique Users -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Unique Users</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $statistics['unique_users'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Filter Activities</h3>
                        <div class="flex space-x-3">
                            @if(auth()->user()->can('audit.export'))
                                <button onclick="showExportModal()" class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Export
                                </button>
                            @endif
                            @if(auth()->user()->can('audit.manage'))
                                <button onclick="showCleanupModal()" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Cleanup
                                </button>
                            @endif
                        </div>
                    </div>

                    <form action="{{ route('admin.audit-trail.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search activities..."
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="action_type" class="block text-sm font-medium text-gray-700">Action Type</label>
                            <select id="action_type" name="action_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Types</option>
                                <option value="opcr_workflow" {{ request('action_type') == 'opcr_workflow' ? 'selected' : '' }}>OPCR Workflow</option>
                                <option value="mfo" {{ request('action_type') == 'mfo' ? 'selected' : '' }}>MFO</option>
                                <option value="success_indicator" {{ request('action_type') == 'success_indicator' ? 'selected' : '' }}>Success Indicator</option>
                                <option value="office" {{ request('action_type') == 'office' ? 'selected' : '' }}>Office</option>
                                <option value="user" {{ request('action_type') == 'user' ? 'selected' : '' }}>User</option>
                                <option value="performance_period" {{ request('action_type') == 'performance_period' ? 'selected' : '' }}>Performance Period</option>
                                <option value="rating_scale" {{ request('action_type') == 'rating_scale' ? 'selected' : '' }}>Rating Scale</option>
                            </select>
                        </div>

                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                            <input type="date"
                                   id="date_from"
                                   name="date_from"
                                   value="{{ request('date_from') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                            <input type="date"
                                   id="date_to"
                                   name="date_to"
                                   value="{{ request('date_to') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="per_page" class="block text-sm font-medium text-gray-700">Per Page</label>
                            <select id="per_page" name="per_page" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="10" {{ request('per_page', 25) == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page', 25) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page', 25) == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Top Action Types -->
            @if(isset($statistics['top_action_types']) && $statistics['top_action_types']->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Top Activity Types</h3>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            @foreach($statistics['top_action_types'] as $actionType)
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-lg font-bold text-gray-900">{{ $actionType->count }}</div>
                                    <div class="text-xs text-gray-600">{{ ucfirst(str_replace('_', ' ', $actionType->action_type)) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Activities List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($activities->count() > 0)
                        <div class="space-y-4">
                            @foreach($activities as $activity)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if(($activity->properties['action_type'] ?? null) === 'opcr_workflow')
                                                        bg-blue-100 text-blue-800
                                                    @elseif(($activity->properties['action_type'] ?? null) === 'mfo')
                                                        bg-green-100 text-green-800
                                                    @elseif(($activity->properties['action_type'] ?? null) === 'success_indicator')
                                                        bg-purple-100 text-purple-800
                                                    @elseif(($activity->properties['action_type'] ?? null) === 'office')
                                                        bg-yellow-100 text-yellow-800
                                                    @elseif(($activity->properties['action_type'] ?? null) === 'user')
                                                        bg-indigo-100 text-indigo-800
                                                    @else
                                                        bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ ucfirst(str_replace('_', ' ', $activity->properties['action_type'] ?? 'system')) }}
                                                </span>
                                                <h4 class="text-sm font-medium text-gray-900">
                                                    {{ $activity->description }}
                                                </h4>
                                            </div>

                                            @if($activity->properties['action'] ?? null)
                                                <p class="text-sm text-gray-600 mb-2">
                                                    <span class="font-medium">Action:</span> {{ $activity->properties['action'] }}
                                                </p>
                                            @endif

                                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                <span>
                                                    <strong>User:</strong> {{ $activity->causer?->name ?? 'System' }}
                                                </span>
                                                @if($activity->causer?->email)
                                                    <span>({{ $activity->causer->email }})</span>
                                                @endif
                                                <span>
                                                    <strong>Time:</strong> {{ $activity->created_at->format('M d, Y h:i A') }}
                                                </span>
                                                @if($activity->properties['user_ip'] ?? null)
                                                    <span>
                                                        <strong>IP:</strong> {{ $activity->properties['user_ip'] }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if($activity->subject)
                                                <div class="mt-2 text-xs text-gray-500">
                                                    <span class="font-medium">Subject:</span>
                                                    {{ class_basename($activity->subject_type) }}
                                                    @if($activity->subject_id)
                                                        #{{ $activity->subject_id }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <div class="ml-4">
                                            <a href="{{ route('admin.audit-trail.show', $activity) }}"
                                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $activities->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No audit activities found</h3>
                            <p class="mt-1 text-sm text-gray-500">No activities match your current filters.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div id="exportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Export Audit Trail</h3>
                <form action="{{ route('admin.audit-trail.export') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Export Format</label>
                            <select name="format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="csv">CSV</option>
                                <option value="xlsx">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date Range (Optional)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" name="date_from" placeholder="From" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <input type="date" name="date_to" placeholder="To" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Action Type (Optional)</label>
                            <select name="action_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Types</option>
                                <option value="opcr_workflow">OPCR Workflow</option>
                                <option value="mfo">MFO</option>
                                <option value="success_indicator">Success Indicator</option>
                                <option value="office">Office</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="hideExportModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Export
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cleanup Modal -->
    <div id="cleanupModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Cleanup Audit Trail</h3>
                <form action="{{ route('admin.audit-trail.cleanup') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Delete entries older than (days)</label>
                            <input type="number" name="days_old" value="90" min="30" max="365" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-500 mt-1">Minimum 30 days, maximum 365 days</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Action Types (Optional)</label>
                            <div class="space-y-2 mt-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="action_types[]" value="opcr_workflow" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">OPCR Workflow</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="action_types[]" value="mfo" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">MFO</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="action_types[]" value="success_indicator" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Success Indicator</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="action_types[]" value="office" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Office</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="action_types[]" value="user" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">User</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Leave blank to delete all action types</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="hideCleanupModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" onclick="return confirm('This action cannot be undone. Are you sure?')" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Cleanup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showExportModal() {
            document.getElementById('exportModal').classList.remove('hidden');
        }

        function hideExportModal() {
            document.getElementById('exportModal').classList.add('hidden');
        }

        function showCleanupModal() {
            document.getElementById('cleanupModal').classList.remove('hidden');
        }

        function hideCleanupModal() {
            document.getElementById('cleanupModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const exportModal = document.getElementById('exportModal');
            const cleanupModal = document.getElementById('cleanupModal');

            if (event.target === exportModal) {
                hideExportModal();
            }
            if (event.target === cleanupModal) {
                hideCleanupModal();
            }
        }
    </script>
</x-app-layout>