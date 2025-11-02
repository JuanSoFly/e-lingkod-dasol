<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Workflow Analytics
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Workflow Analytics Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Workflow Process Analytics
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Analysis of OPCR workflow stages, bottlenecks, and efficiency metrics
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
                                <option>{{ $filters['workflow_stage'] ?? null ? 'Selected Stage' : 'All Stages' }}</option>
                                <option value="draft">Draft</option>
                                <option value="committed">Committed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="evaluation">Evaluation</option>
                                <option value="final_approval">Final Approval</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workflow Metrics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Workflows -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Workflows</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $workflowData['total_workflows'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Workflows -->
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Active Workflows</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $workflowData['active_workflows'] ?? 0 }}</dd>
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
                                    <dd class="text-lg font-medium text-gray-900">{{ $workflowData['completed_workflows'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Processing Time -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Avg Processing</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $workflowData['avg_processing_days'] ?? 0 }}d</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workflow Stages Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Workflow Stages Distribution</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        @foreach(['draft', 'committed', 'in_progress', 'evaluation', 'final_approval', 'completed'] as $stage)
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-{{
                                    $stage === 'completed' ? 'green' :
                                    ($stage === 'evaluation' ? 'purple' :
                                    ($stage === 'final_approval' ? 'blue' :
                                    ($stage === 'in_progress' ? 'yellow' :
                                    ($stage === 'committed' ? 'indigo' : 'gray')))) }}-600">
                                    {{ $workflowData['stage_counts'][$stage] ?? 0 }}
                                </div>
                                <div class="text-sm font-medium text-gray-900 mt-1">{{ ucfirst(str_replace('_', ' ', $stage)) }}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $workflowData['stage_percentages'][$stage] ?? 0 }}%
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Workflow Efficiency Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Workflow Timeline Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Workflow Processing Timeline</h3>
                        <div class="h-64 bg-gray-50 rounded-lg p-4">
                            <canvas id="workflowTimelineChart" class="w-full h-full"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Bottleneck Analysis Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Bottleneck Analysis</h3>
                        <div class="h-64 bg-gray-50 rounded-lg p-4">
                            <canvas id="bottleneckAnalysisChart" class="w-full h-full"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Workflow Activity -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Recent Workflow Activity</h3>
                        <a href="{{ route('opcr.workflows.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">View All Workflows</a>
                    </div>
                    <div class="space-y-4">
                        @if(isset($workflowData['recent_activities']) && count($workflowData['recent_activities']) > 0)
                            @foreach($workflowData['recent_activities'] as $activity)
                                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-{{ $activity['color'] ?? 'gray' }}-400 rounded-full"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900">
                                            <span class="font-medium">{{ $activity['user'] }}</span>
                                            {{ $activity['action'] }}
                                            <a href="{{ $activity['link'] ?? '#' }}" class="text-indigo-600 hover:text-indigo-900">{{ $activity['workflow'] }}</a>
                                        </p>
                                        <p class="text-sm text-gray-500">{{ $activity['time'] }} • {{ $activity['stage'] }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $activity['status_color'] ?? 'gray' }}-100 text-{{ $activity['status_color'] ?? 'gray' }}-800">
                                            {{ $activity['status'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No recent workflow activity</h3>
                                <p class="mt-1 text-sm text-gray-500">Recent workflow changes will appear here.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Workflow Performance Insights -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Fastest Processing -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-900 mb-3 text-green-600">Fastest Processing</h3>
                        <div class="space-y-2">
                            @if(isset($workflowData['fastest_processing']) && count($workflowData['fastest_processing']) > 0)
                                @foreach(array_slice($workflowData['fastest_processing'], 0, 3) as $item)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">{{ $item['office'] }}</span>
                                        <span class="text-sm font-medium text-green-600">{{ $item['processing_days'] }}d</span>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-500">No data available</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Pending Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-900 mb-3 text-yellow-600">Pending Actions</h3>
                        <div class="space-y-2">
                            @if(isset($workflowData['pending_actions_by_stage']) && count($workflowData['pending_actions_by_stage']) > 0)
                                @foreach(array_slice($workflowData['pending_actions_by_stage'], 0, 3) as $stage => $item)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $stage)) }}</span>
                                        <span class="text-sm font-medium text-yellow-600">{{ $item['count'] }}</span>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-500">No pending actions</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Workflow Efficiency Score -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-900 mb-3 text-blue-600">Efficiency Score</h3>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600">{{ $workflowData['efficiency_score'] ?? 0 }}%</div>
                            <div class="text-sm text-gray-500 mt-1">Overall workflow efficiency</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OPCR Analytics Charts JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Workflow Timeline Chart
            initWorkflowTimelineChart();

            // Initialize Bottleneck Analysis Chart
            initBottleneckAnalysisChart();
        });

        function initWorkflowTimelineChart() {
            const ctx = document.getElementById('workflowTimelineChart');
            if (!ctx) return;

            const timelineData = @json($workflowData['processing_timeline'] ?? []);

            if (!timelineData || timelineData.length === 0) {
                showEmptyState(ctx, 'No timeline data available');
                return;
            }

            // Transform data for Chart.js
            const labels = timelineData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            const counts = timelineData.map(item => item.count);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Workflows Created',
                        data: counts,
                        borderColor: 'rgb(79, 70, 229)', // indigo-600
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: 'rgb(79, 70, 229)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#374151',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgb(79, 70, 229)',
                            borderWidth: 1,
                            callbacks: {
                                title: function(context) {
                                    const index = context[0].dataIndex;
                                    return 'Date: ' + timelineData[index].date;
                                },
                                label: function(context) {
                                    return 'Workflows: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            display: true,
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 11
                                },
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        function initBottleneckAnalysisChart() {
            const ctx = document.getElementById('bottleneckAnalysisChart');
            if (!ctx) return;

            const bottleneckData = @json($workflowData['bottleneck_analysis'] ?? []);

            if (!bottleneckData || Object.keys(bottleneckData).length === 0) {
                showEmptyState(ctx, 'No bottleneck data available');
                return;
            }

            // Transform data for Chart.js
            const labels = [];
            const avgDays = [];
            const backgroundColors = [];
            const borderColors = [];

            Object.entries(bottleneckData).forEach(([stage, data]) => {
                labels.push(stage.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
                avgDays.push(data.avg_days);

                // Color coding: Red for bottlenecks, Green for normal, Yellow for warning
                if (data.is_bottleneck) {
                    backgroundColors.push('rgba(239, 68, 68, 0.5)'); // red-500 with opacity
                    borderColors.push('rgb(239, 68, 68)'); // red-500
                } else if (data.avg_days > 10) {
                    backgroundColors.push('rgba(245, 158, 11, 0.5)'); // amber-500 with opacity
                    borderColors.push('rgb(245, 158, 11)'); // amber-500
                } else {
                    backgroundColors.push('rgba(34, 197, 94, 0.5)'); // green-500 with opacity
                    borderColors.push('rgb(34, 197, 94)'); // green-500
                }
            });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Average Days in Stage',
                        data: avgDays,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 2,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#374151',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                afterLabel: function(context) {
                                    const index = context.dataIndex;
                                    const stage = Object.keys(bottleneckData)[index];
                                    const data = bottleneckData[stage];
                                    return [
                                        'Workflows: ' + data.total_workflows,
                                        'Max Days: ' + data.max_days,
                                        'Status: ' + (data.is_bottleneck ? 'Bottleneck' : 'Normal')
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            display: true,
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 11
                                }
                            },
                            title: {
                                display: true,
                                text: 'Average Days',
                                color: '#6B7280',
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }

        function showEmptyState(canvas, message) {
            const container = canvas.parentElement;
            container.innerHTML = `
                <div class="h-full flex items-center justify-center">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">${message}</p>
                    </div>
                </div>
            `;
        }
    </script>
</x-app-layout>