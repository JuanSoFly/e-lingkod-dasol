<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Performance Analytics
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Performance Analytics Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Performance Metrics & Analysis
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Detailed analysis of individual and office performance metrics
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
                                <option>{{ $filters['office_id'] ?? null ? 'Selected Office' : 'All Offices' }}</option>
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Average Performance Rating -->
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Average Rating</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ number_format($performanceData['average_rating'] ?? 0, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Top Performers</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $performanceData['top_performers_count'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Improvement -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Improvement Rate</dt>
                                    <dd class="text-lg font-medium text-gray-900">+{{ $performanceData['improvement_rate'] ?? 0 }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Target Achievement -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Target Achievement</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $performanceData['target_achievement'] ?? 0 }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Performance Distribution Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Distribution</h3>
                        <div class="h-64 bg-white rounded-lg border border-gray-200">
                            <canvas id="performanceDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Performance Trends Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Trends</h3>
                        <div class="h-64 bg-white rounded-lg border border-gray-200">
                            <canvas id="performanceTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Office Performance Comparison -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Office Performance Comparison</h3>
                        <div class="flex space-x-2">
                            <div class="relative">
                                <select id="exportFormat" class="px-2 py-1 border border-gray-300 text-sm rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="excel">Excel</option>
                                    <option value="csv">CSV</option>
                                </select>
                                <button id="exportBtn" onclick="exportOfficePerformanceData()" class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-r-md hover:bg-indigo-700 transition-colors duration-200">
                                    <span id="exportBtnText">Export</span>
                                    <span id="exportLoader" class="hidden">
                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Office</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Rating</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QET Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @if(isset($performanceData['office_performance']) && count($performanceData['office_performance']) > 0)
                                    @foreach($performanceData['office_performance'] as $office)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $office['name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($office['avg_rating'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $office['completion_rate'] }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($office['qet_score'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $office['status'] === 'Excellent' ? 'bg-green-100 text-green-800' : ($office['status'] === 'Good' ? 'bg-blue-100 text-blue-800' : ($office['status'] === 'Needs Attention' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                                    {{ $office['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No performance data available
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Individual Performance Highlights -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Highlights</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Top Performers -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3 text-green-600">Top Performers</h4>
                            <div class="space-y-2">
                                @if(isset($performanceData['top_individuals']) && count($performanceData['top_individuals']) > 0)
                                    @foreach(array_slice($performanceData['top_individuals'], 0, 3) as $performer)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">{{ $performer['name'] }}</span>
                                            <span class="text-sm font-medium text-gray-900">{{ number_format($performer['rating'], 2) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500">No data available</p>
                                @endif
                            </div>
                        </div>

                        <!-- Most Improved -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3 text-blue-600">Most Improved</h4>
                            <div class="space-y-2">
                                @if(isset($performanceData['most_improved']) && count($performanceData['most_improved']) > 0)
                                    @foreach(array_slice($performanceData['most_improved'], 0, 3) as $improved)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">{{ $improved['name'] }}</span>
                                            <span class="text-sm font-medium text-green-600">+{{ $improved['improvement'] }}%</span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500">No data available</p>
                                @endif
                            </div>
                        </div>

                        <!-- Consistent Performers -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3 text-purple-600">Consistent Performers</h4>
                            <div class="space-y-2">
                                @if(isset($performanceData['consistent_performers']) && count($performanceData['consistent_performers']) > 0)
                                    @foreach(array_slice($performanceData['consistent_performers'], 0, 3) as $consistent)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">{{ $consistent['name'] }}</span>
                                            <span class="text-sm font-medium text-gray-900">{{ number_format($consistent['avg_rating'], 2) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500">No data available</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Initialization Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Performance Distribution Chart
            const distributionCtx = document.getElementById('performanceDistributionChart');
            if (distributionCtx) {
                const distributionData = @json($performanceData['performance_distribution'] ?? []);

                new Chart(distributionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: distributionData.categories || ['No Data'],
                        datasets: [{
                            data: distributionData.counts || [0],
                            backgroundColor: [
                                '#10b981', // green for Outstanding
                                '#3b82f6', // blue for Very Satisfactory
                                '#f59e0b', // yellow for Satisfactory
                                '#f97316', // orange for Unsatisfactory
                                '#ef4444'  // red for Poor
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const percentage = distributionData.percentages?.[context.dataIndex] || 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Performance Trends Chart
            const trendsCtx = document.getElementById('performanceTrendsChart');
            if (trendsCtx) {
                const trendsData = @json($performanceData['performance_trends_data'] ?? []);

                new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: trendsData.labels || ['No Data'],
                        datasets: [
                            {
                                label: 'Average Rating',
                                data: trendsData.average_ratings || [0],
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Completion Rate (%)',
                                data: trendsData.completion_rates || [0],
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Average Rating'
                                },
                                min: 0,
                                max: 5
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Completion Rate (%)'
                                },
                                min: 0,
                                max: 100,
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    }
                });
            }
        });

        // Export functionality
        function exportOfficePerformanceData() {
            const exportBtn = document.getElementById('exportBtn');
            const exportBtnText = document.getElementById('exportBtnText');
            const exportLoader = document.getElementById('exportLoader');
            const format = document.getElementById('exportFormat').value;

            // Get current filter values
            const periodSelect = document.querySelector('select:nth-child(1)');
            const officeSelect = document.querySelector('select:nth-child(2)');

            const periodId = periodSelect && periodSelect.value !== 'All Periods' ? periodSelect.value : '';
            const officeId = officeSelect && officeSelect.value !== 'All Offices' ? officeSelect.value : '';

            // Show loading state
            exportBtn.disabled = true;
            exportBtnText.classList.add('hidden');
            exportLoader.classList.remove('hidden');

            // Build query parameters
            const params = new URLSearchParams({
                format: format,
                period_id: periodId,
                office_id: officeId
            });

            // Make the export request
            fetch(`/opcr/analytics/export?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': format === 'excel' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Export failed');
                }

                // Get filename from Content-Disposition header or create default
                const contentDisposition = response.headers.get('Content-Disposition');
                let filename = `office_performance_export_${new Date().toISOString().split('T')[0]}.${format}`;

                if (contentDisposition) {
                    const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (filenameMatch && filenameMatch[1]) {
                        filename = filenameMatch[1].replace(/['"]/g, '');
                    }
                }

                return response.blob().then(blob => ({ blob, filename }));
            })
            .then(({ blob, filename }) => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                // Show success message
                showNotification('Export completed successfully!', 'success');
            })
            .catch(error => {
                console.error('Export error:', error);
                showNotification('Export failed: ' + error.message, 'error');
            })
            .finally(() => {
                // Reset button state
                exportBtn.disabled = false;
                exportBtnText.classList.remove('hidden');
                exportLoader.classList.add('hidden');
            });
        }

        // Notification helper function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';

            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="mr-2">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
</x-app-layout>