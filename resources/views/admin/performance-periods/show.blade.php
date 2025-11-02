<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Performance Period Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ $period->year }} - {{ ucfirst($period->semester) }} Period
                                    </h3>
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
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $period->start_date->format('F d, Y') }} - {{ $period->end_date->format('F d, Y') }}
                                    ({{ $period->start_date->diffInDays($period->end_date) }} days)
                                </p>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            @if(in_array($period->status, ['active', 'upcoming']) && auth()->user()->can('performance-period.edit'))
                                <a href="{{ route('admin.performance-periods.edit', $period) }}"
                                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </a>
                            @endif
                            <a href="{{ route('admin.performance-periods.index') }}"
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Period Statistics -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Period Statistics</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-900">{{ $period->opcr_workflows_count ?? 0 }}</div>
                                    <div class="text-sm text-gray-600">Total OPCR Workflows</div>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">{{ $period->completed_workflows ?? 0 }}</div>
                                    <div class="text-sm text-gray-600">Completed</div>
                                </div>
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ $period->completion_rate ?? 0 }}%</div>
                                    <div class="text-sm text-gray-600">Completion Rate</div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mt-6">
                                <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                                    <span>Overall Progress</span>
                                    <span>{{ $period->completion_rate ?? 0 }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-indigo-600 h-3 rounded-full transition-all duration-300" style="width: {{ $period->completion_rate ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Recent OPCR Activity</h3>
                                <a href="#" class="text-sm text-indigo-600 hover:text-indigo-900">View All</a>
                            </div>
                            <div class="space-y-4">
                                @if(isset($period->recent_activity) && count($period->recent_activity) > 0)
                                    @foreach($period->recent_activity as $activity)
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-shrink-0">
                                                <div class="w-2 h-2 bg-{{ $activity['color'] ?? 'gray' }}-400 rounded-full"></div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-gray-900">
                                                    <span class="font-medium">{{ $activity['user'] }}</span>
                                                    {{ $activity['action'] }}
                                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">{{ $activity['target'] }}</a>
                                                </p>
                                                <p class="text-sm text-gray-500">{{ $activity['time'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-8">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No recent activity</h3>
                                        <p class="mt-1 text-sm text-gray-500">OPCR activity will appear here.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Period Timeline -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Timeline</h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Start Date</p>
                                        <p class="text-xs text-gray-500">{{ $period->start_date->format('M d, Y') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">End Date</p>
                                        <p class="text-xs text-gray-500">{{ $period->end_date->format('M d, Y') }}</p>
                                    </div>
                                </div>

                                @if(now()->between($period->start_date, $period->end_date))
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Days Remaining</p>
                                            <p class="text-xs text-gray-500">{{ now()->diffInDays($period->end_date) }} days</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                @if($period->status === 'upcoming' && auth()->user()->can('performance-period.manage'))
                                    <form action="{{ route('admin.performance-periods.activate', $period) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 border border-green-300 shadow-sm text-sm leading-4 font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Activate Period
                                        </button>
                                    </form>
                                @endif

                                @if($period->status === 'active' && auth()->user()->can('performance-period.manage'))
                                    <form action="{{ route('admin.performance-periods.close', $period) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 border border-orange-300 shadow-sm text-sm leading-4 font-medium rounded-md text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Close Period
                                        </button>
                                    </form>
                                @endif

                                <a href="#" class="block w-full text-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    View Analytics
                                </a>

                                <a href="#" class="block w-full text-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Export Report
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Period Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Period Information</h3>
                            <div class="space-y-3 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700">Period ID:</span>
                                    <span class="text-gray-900 ml-2">#{{ str_pad($period->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Created:</span>
                                    <span class="text-gray-900 ml-2">{{ $period->created_at->format('M d, Y \a\t h:i A') }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Last Updated:</span>
                                    <span class="text-gray-900 ml-2">{{ $period->updated_at->format('M d, Y \a\t h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>