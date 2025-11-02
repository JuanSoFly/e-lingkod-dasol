<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            OPCR Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Welcome Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Welcome back, {{ auth()->user()->employee->full_name ?? auth()->user()->name }}!
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $userRole === 'Department Head' ? 'Manage your office performance commitments' :
                                   ($userRole === 'Assessor' ? 'Review and evaluate OPCR submissions' :
                                   ($userRole === 'Final Approver' ? 'Finalize OPCR approvals' :
                                   'Monitor OPCR system performance')) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-indigo-600">{{ now()->format('M d, Y') }}</div>
                            <div class="text-sm text-gray-500">{{ $selectedPeriod ? 'Period: ' . $selectedPeriod : 'Current Period' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Workflows -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Workflows</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $opcrData['total_workflows'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Actions -->
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Actions</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $pendingItems['total_pending'] ?? array_sum($pendingItems) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completed Workflows -->
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $opcrData['completed_workflows'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Rating -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Avg Rating</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ number_format($opcrData['average_rating'] ?? 0, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Items and Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Pending Items -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Pending Items</h3>
                        @if(empty($pendingItems) || (isset($pendingItems['total_pending']) ? $pendingItems['total_pending'] : array_sum($pendingItems)) === 0)
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No pending items</h3>
                                <p class="mt-1 text-sm text-gray-500">All caught up! Great job.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @if($userRole === 'Department Head')
                                    @if(isset($pendingItems['draft_workflows']) && $pendingItems['draft_workflows'] > 0)
                                        <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                                            <div>
                                                <h4 class="text-sm font-medium text-blue-900">Draft Workflows</h4>
                                                <p class="text-sm text-blue-700">{{ $pendingItems['draft_workflows'] }} need completion</p>
                                            </div>
                                            <a href="{{ route('opcr.workflows.index', ['workflow_state' => 'draft']) }}" class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                Review
                                            </a>
                                        </div>
                                    @endif
                                    @if(isset($pendingItems['returned_workflows']) && $pendingItems['returned_workflows'] > 0)
                                        <div class="flex items-center justify-between p-4 bg-orange-50 rounded-lg">
                                            <div>
                                                <h4 class="text-sm font-medium text-orange-900">Returned Workflows</h4>
                                                <p class="text-sm text-orange-700">{{ $pendingItems['returned_workflows'] }} need revision</p>
                                            </div>
                                            <a href="{{ route('opcr.workflows.index', ['workflow_state' => 'returned']) }}" class="inline-flex items-center px-3 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                Revise
                                            </a>
                                        </div>
                                    @endif
                                @endif

                                @if($userRole === 'Assessor')
                                    @if(isset($pendingItems['evaluation_workflows']) && $pendingItems['evaluation_workflows'] > 0)
                                        <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                                            <div>
                                                <h4 class="text-sm font-medium text-purple-900">Workflows for Evaluation</h4>
                                                <p class="text-sm text-purple-700">{{ $pendingItems['evaluation_workflows'] }} ready for assessment</p>
                                            </div>
                                            <a href="{{ route('opcr.workflows.index', ['workflow_state' => 'evaluation']) }}" class="inline-flex items-center px-3 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                Evaluate
                                            </a>
                                        </div>
                                    @endif
                                @endif

                                @if(in_array($userRole, ['Super Admin', 'HR Admin']))
                                    @if(isset($pendingItems['total_pending']) && $pendingItems['total_pending'] > 0)
                                        <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                                            <div>
                                                <h4 class="text-sm font-medium text-purple-900">System Overview</h4>
                                                <p class="text-sm text-purple-700">{{ $pendingItems['total_pending'] }} total actions pending across all offices</p>
                                            </div>
                                            <a href="{{ route('opcr.workflows.index') }}" class="inline-flex items-center px-3 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                Review
                                            </a>
                                        </div>
                                    @endif
                                @endif

                                @if($userRole === 'Final Approver')
                                    @if(isset($pendingItems['final_approval_workflows']) && $pendingItems['final_approval_workflows'] > 0)
                                        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                                            <div>
                                                <h4 class="text-sm font-medium text-green-900">Final Approvals</h4>
                                                <p class="text-sm text-green-700">{{ $pendingItems['final_approval_workflows'] }} awaiting final approval</p>
                                            </div>
                                            <a href="{{ route('opcr.workflows.index', ['workflow_state' => 'final_approval']) }}" class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                Approve
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            @if(in_array($userRole, ['Department Head', 'Super Admin', 'HR Admin']))
                                <a href="{{ route('opcr.workflows.create') }}" class="block w-full text-left p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <h4 class="text-sm font-medium text-indigo-900">Create New OPCR</h4>
                                            <p class="text-sm text-indigo-700">Start a new performance commitment</p>
                                        </div>
                                    </div>
                                </a>
                            @endif

                            <a href="{{ route('opcr.workflows.index') }}" class="block w-full text-left p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">View All Workflows</h4>
                                        <p class="text-sm text-gray-700">Browse and manage existing OPCR workflows</p>
                                    </div>
                                </div>
                            </a>

                            @if(in_array($userRole, ['Super Admin', 'HR Admin']))
                                <a href="{{ route('opcr.mfos.index') }}" class="block w-full text-left p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <h4 class="text-sm font-medium text-yellow-900">Manage MFOs</h4>
                                            <p class="text-sm text-yellow-700">Configure Major Final Outputs and indicators</p>
                                        </div>
                                    </div>
                                </a>
                            @endif

                            <a href="{{ route('opcr.analytics.index') }}" class="block w-full text-left p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-purple-900">Analytics & Reports</h4>
                                        <p class="text-sm text-purple-700">View performance analytics and insights</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                        <a href="{{ route('opcr.analytics.workflow') }}" class="text-sm text-indigo-600 hover:text-indigo-900">View All</a>
                    </div>
                    <div class="space-y-4">
                        @if(isset($opcrData['recent_activities']) && count($opcrData['recent_activities']) > 0)
                            @foreach($opcrData['recent_activities'] as $activity)
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-{{ $activity['status'] === 'completed' ? 'green' : ($activity['status'] === 'active' ? 'blue' : 'gray') }}-400 rounded-full"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900">
                                            <span class="font-medium">{{ $activity['user'] }}</span>
                                            {{ $activity['action'] }}
                                            @if($activity['type'] === 'workflow_state_change')
                                                <span class="text-gray-600">{{ $activity['description'] }}</span>
                                            @else
                                                <span class="text-indigo-600">{{ $activity['description'] }}</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ $activity['office'] }} • {{ \Carbon\Carbon::parse($activity['time'])->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No recent activity</h3>
                                <p class="mt-1 text-sm text-gray-500">Recent OPCR activity will appear here.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
