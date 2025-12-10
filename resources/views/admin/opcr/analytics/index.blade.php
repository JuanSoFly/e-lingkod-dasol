<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            OPCR Analytics Dashboard
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Analytics Overview -->
            <div class="bg-gradient-to-r from-slate-100 via-white to-slate-100 overflow-hidden shadow-sm sm:rounded-lg">
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Performance Analytics -->
                <a href="{{ route('opcr.analytics.performance') }}" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-600 text-white shadow-lg ring-1 ring-emerald-200/60 transition transform hover:-translate-y-1 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/80">
                    <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/15 blur-3xl"></div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Performance</p>
                                <h4 class="mt-1 text-2xl font-bold">Performance Analytics</h4>
                                <p class="mt-2 text-sm text-white/80">Trend lines, ratings, and completion scores for all offices.</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h4l3 7 4-14 3 7h4" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <span>View analytics</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Workflow Analytics -->
                <a href="{{ route('opcr.analytics.workflow') }}" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 text-white shadow-lg ring-1 ring-indigo-200/60 transition transform hover:-translate-y-1 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/80">
                    <div class="absolute -left-12 -bottom-12 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Workflow</p>
                                <h4 class="mt-1 text-2xl font-bold">Workflow Analytics</h4>
                                <p class="mt-2 text-sm text-white/80">Bottlenecks, cycle times, and pending actions across stages.</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <span>Track workflows</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Compliance Analytics -->
                <a href="{{ route('opcr.analytics.compliance') }}" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 via-amber-600 to-orange-600 text-white shadow-lg ring-1 ring-amber-200/60 transition transform hover:-translate-y-1 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/80">
                    <div class="absolute right-[-3rem] top-[-3rem] h-28 w-28 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Compliance</p>
                                <h4 class="mt-1 text-2xl font-bold">Compliance Reports</h4>
                                <p class="mt-2 text-sm text-white/80">On-time submissions, overdue items, and readiness for CSC audits.</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <span>Review compliance</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
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
