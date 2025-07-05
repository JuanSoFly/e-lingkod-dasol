<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Document Analytics') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Documents</dt>
                                    <dd class="text-lg font-medium text-gray-900">1,234</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 110 2h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 010-2h4zM9 3v1h6V3H9zm0 5v8h6V8H9z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">This Month</dt>
                                    <dd class="text-lg font-medium text-gray-900">89</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">OCR Processed</dt>
                                    <dd class="text-lg font-medium text-gray-900">956</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2v0"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Storage Used</dt>
                                    <dd class="text-lg font-medium text-gray-900">2.4 GB</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Document Types Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Documents by Type</h3>
                        <div class="h-64">
                            <canvas id="documentTypesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Upload Trend -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Upload Trends</h3>
                        <div class="h-64">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Analysis -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Top Departments -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Top Departments by Document Count</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">Human Resources</span>
                                <div class="flex items-center">
                                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
                                    </div>
                                    <span class="text-sm text-gray-500">425</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">Finance</span>
                                <div class="flex items-center">
                                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 68%"></div>
                                    </div>
                                    <span class="text-sm text-gray-500">342</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">Engineering</span>
                                <div class="flex items-center">
                                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-yellow-600 h-2 rounded-full" style="width: 45%"></div>
                                    </div>
                                    <span class="text-sm text-gray-500">226</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">IT</span>
                                <div class="flex items-center">
                                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-purple-600 h-2 rounded-full" style="width: 32%"></div>
                                    </div>
                                    <span class="text-sm text-gray-500">158</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">Others</span>
                                <div class="flex items-center">
                                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-red-600 h-2 rounded-full" style="width: 25%"></div>
                                    </div>
                                    <span class="text-sm text-gray-500">83</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Size Distribution -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">File Size Distribution</h3>
                        <div class="h-48">
                            <canvas id="fileSizeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Search Activity -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Popular Search Terms</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-900">performance rating</span>
                                <span class="text-sm text-gray-500">156 searches</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-900">leave application</span>
                                <span class="text-sm text-gray-500">134 searches</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-900">training certificate</span>
                                <span class="text-sm text-gray-500">98 searches</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-900">appointment</span>
                                <span class="text-sm text-gray-500">87 searches</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-900">service record</span>
                                <span class="text-sm text-gray-500">72 searches</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Health & Quality -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Document Quality Metrics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Document Quality Metrics</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span>OCR Quality Score</span>
                                    <span>94%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: 94%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span>Searchable Documents</span>
                                    <span>87%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: 87%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span>Complete Metadata</span>
                                    <span>76%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 76%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span>Version Control</span>
                                    <span>91%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: 91%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Document Activity</h3>
                        <div class="flow-root">
                            <ul class="-mb-8">
                                <li>
                                    <div class="relative pb-8">
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        <span class="font-medium text-gray-900">Maria Santos</span> uploaded a new document
                                                    </p>
                                                    <p class="mt-0.5 text-sm text-gray-500">2 hours ago</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="relative pb-8">
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        OCR processing completed for <span class="font-medium text-gray-900">15 documents</span>
                                                    </p>
                                                    <p class="mt-0.5 text-sm text-gray-500">4 hours ago</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="relative">
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        <span class="font-medium text-gray-900">5 documents</span> require metadata update
                                                    </p>
                                                    <p class="mt-0.5 text-sm text-gray-500">1 day ago</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Analytics Export</h3>
                    <div class="flex space-x-4">
                        <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            Export to PDF
                        </button>
                        <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            Export to Excel
                        </button>
                        <button class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Schedule Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Document Types Pie Chart
            const docTypesCtx = document.getElementById('documentTypesChart').getContext('2d');
            new Chart(docTypesCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Performance Rating', 'Training Certificates', 'Leave Forms', 'Service Records', 'Other'],
                    datasets: [{
                        data: [30, 25, 20, 15, 10],
                        backgroundColor: [
                            '#3B82F6',
                            '#10B981',
                            '#F59E0B',
                            '#EF4444',
                            '#8B5CF6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Monthly Trends Line Chart
            const trendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Documents Uploaded',
                        data: [65, 78, 90, 85, 112, 89],
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // File Size Distribution Chart
            const fileSizeCtx = document.getElementById('fileSizeChart').getContext('2d');
            new Chart(fileSizeCtx, {
                type: 'bar',
                data: {
                    labels: ['<1MB', '1-5MB', '5-10MB', '>10MB'],
                    datasets: [{
                        data: [45, 35, 15, 5],
                        backgroundColor: [
                            '#10B981',
                            '#F59E0B',
                            '#EF4444',
                            '#8B5CF6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>