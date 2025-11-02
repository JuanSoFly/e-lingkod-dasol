<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Compliance Analytics
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Compliance Analytics Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Compliance & Adherence Analytics
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Monitoring of submission rates, deadline adherence, and compliance metrics
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <select class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option>{{ $filters['period_id'] ?? null ? 'Selected Period' : 'All Periods' }}</option>
                                @foreach($periods as $period)
                                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                                @endforeach
                            </select>
                            <select class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option>{{ $filters['compliance_type'] ?? null ? 'Selected Type' : 'All Types' }}</option>
                                <option value="submission_rate">Submission Rate</option>
                                <option value="deadline_adherence">Deadline Adherence</option>
                                <option value="document_completeness">Document Completeness</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compliance Metrics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Overall Compliance Rate -->
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Compliance Rate</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $complianceData['overall_compliance_rate'] ?? 0 }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- On-Time Submissions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">On-Time</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $complianceData['on_time_submission_rate'] ?? 0 }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Completeness -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Completeness</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $complianceData['document_completeness'] ?? 0 }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Items -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $complianceData['overdue_count'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compliance Trends Chart -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Compliance Rate Over Time -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Compliance Rate Trends</h3>
                        <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">Compliance trends chart will be displayed here</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deadline Adherence Analysis -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Deadline Adherence Analysis</h3>
                        <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">Deadline adherence chart will be displayed here</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Office Compliance Breakdown -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Office Compliance Breakdown</h3>
                        <div class="flex space-x-2">
                            <button class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                Export Report
                            </button>
                            <button class="px-3 py-1 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700">
                                Send Reminders
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Office</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">On-Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completeness</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overdue</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @if(isset($complianceData['office_compliance']) && count($complianceData['office_compliance']) > 0)
                                    @foreach($complianceData['office_compliance'] as $office)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $office['name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $office['compliance_rate'] }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $office['on_time_rate'] }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $office['completeness_rate'] }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $office['overdue_count'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{
                                                    $office['compliance_status'] === 'Excellent' ? 'bg-green-100 text-green-800' :
                                                    ($office['compliance_status'] === 'Good' ? 'bg-blue-100 text-blue-800' :
                                                    ($office['compliance_status'] === 'Needs Attention' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'))
                                                }}">
                                                    {{ $office['compliance_status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No compliance data available
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Compliance Alerts and Notifications -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Critical Compliance Issues -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Critical Issues</h3>
                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                                {{ $complianceData['critical_issues_count'] ?? 0 }} Items
                            </span>
                        </div>
                        <div class="space-y-3">
                            @if(isset($complianceData['critical_issues']) && count($complianceData['critical_issues']) > 0)
                                @foreach($complianceData['critical_issues'] as $issue)
                                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-red-900">{{ $issue['title'] }}</p>
                                                <p class="text-sm text-red-700">{{ $issue['description'] }}</p>
                                            </div>
                                            <button class="text-sm text-red-600 hover:text-red-800">Action</button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <svg class="mx-auto h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-green-600">No critical issues</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Upcoming Deadlines -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Upcoming Deadlines</h3>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                                {{ $complianceData['upcoming_deadlines_count'] ?? 0 }} Items
                            </span>
                        </div>
                        <div class="space-y-3">
                            @if(isset($complianceData['upcoming_deadlines']) && count($complianceData['upcoming_deadlines']) > 0)
                                @foreach($complianceData['upcoming_deadlines'] as $deadline)
                                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-yellow-900">{{ $deadline['title'] }}</p>
                                                <p class="text-sm text-yellow-700">Due: {{ $deadline['due_date'] }}</p>
                                            </div>
                                            <span class="text-xs text-yellow-600">{{ $deadline['days_remaining'] }} days</span>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No upcoming deadlines</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compliance Improvement Recommendations -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Compliance Improvement Recommendations</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if(isset($complianceData['recommendations']) && count($complianceData['recommendations']) > 0)
                            @foreach($complianceData['recommendations'] as $recommendation)
                                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="text-sm font-medium text-blue-900">{{ $recommendation['title'] }}</h4>
                                            <p class="text-sm text-blue-700 mt-1">{{ $recommendation['description'] }}</p>
                                            <div class="mt-2">
                                                <span class="text-xs text-blue-600">Impact: {{ $recommendation['impact'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-span-3 text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-green-900">Great compliance performance!</h3>
                                <p class="mt-1 text-sm text-green-700">No immediate recommendations needed.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>