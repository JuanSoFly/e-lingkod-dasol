<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            OPCR Analytics Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Analytics Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Performance Analytics & Insights
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Comprehensive analysis of OPCR system performance and metrics
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-indigo-600">{{ now()->format('M d, Y') }}</div>
                            <div class="text-sm text-gray-500">Real-time Analytics</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Navigation -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Main Dashboard -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Main Dashboard</dt>
                                    <dd class="text-lg font-medium text-gray-900">Overview</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Analytics -->
                <a href="{{ route('opcr.analytics.performance') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Performance</dt>
                                    <dd class="text-lg font-medium text-gray-900">Analytics</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Workflow Analytics -->
                <a href="{{ route('opcr.analytics.workflow') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Workflow</dt>
                                    <dd class="text-lg font-medium text-gray-900">Analytics</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Compliance Analytics -->
                <a href="{{ route('opcr.analytics.compliance') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Compliance</dt>
                                    <dd class="text-lg font-medium text-gray-900">Reports</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </a>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total OPCR Forms</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $dashboardData['total_workflows'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completion Rate -->
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Completion Rate</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $dashboardData['completion_rate'] ?? 0 }}%</dd>
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Average Rating</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ number_format($dashboardData['average_rating'] ?? 0, 2) }}</dd>
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
                                <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Active Periods</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $dashboardData['active_periods'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Analytics Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Analytics Summary</h3>
                        <span class="text-sm text-gray-500">Analytics Dashboard</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Performance Overview -->
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $dashboardData['performance_score'] ?? 85 }}%</div>
                            <div class="text-sm font-medium text-gray-900 mt-1">Performance Score</div>
                            <div class="text-xs text-gray-500 mt-1">Based on completion and ratings</div>
                        </div>

                        <!-- Workflow Efficiency -->
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $dashboardData['workflow_efficiency'] ?? 92 }}%</div>
                            <div class="text-sm font-medium text-gray-900 mt-1">Workflow Efficiency</div>
                            <div class="text-xs text-gray-500 mt-1">Timeliness and process optimization</div>
                        </div>

                        <!-- Compliance Rate -->
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">{{ $dashboardData['compliance_rate'] ?? 78 }}%</div>
                            <div class="text-sm font-medium text-gray-900 mt-1">Compliance Rate</div>
                            <div class="text-xs text-gray-500 mt-1">Adherence to deadlines and standards</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>