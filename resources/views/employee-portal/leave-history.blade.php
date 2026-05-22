@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="leaveHistory()">
    <!-- Page Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Leave History & Analytics</h1>
            <p class="text-gray-600">View your complete leave history and usage patterns</p>
        </div>
        <div class="flex space-x-3">
            <button @click="exportData()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                Export Data
            </button>
            <button @click="printHistory()" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                Print
            </button>
        </div>
    </div>

    <!-- Analytics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Leave Used -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Leave Used</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="analytics.total_used"></p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- VL Used -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">VL Used</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="analytics.vl_used"></p>
                </div>
                <div class="bg-emerald-50 p-3 rounded-lg text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- SL Used -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">SL Used</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="analytics.sl_used"></p>
                </div>
                <div class="bg-amber-50 p-3 rounded-lg text-amber-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Average/Year -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Average/Year</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="analytics.average_yearly"></p>
                </div>
                <div class="bg-purple-50 p-3 rounded-lg text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
                <select x-model="filters.leave_type" @change="loadHistory" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="VL">Vacation Leave</option>
                    <option value="SL">Sick Leave</option>
                    <option value="FL">Force Leave</option>
                    <option value="ML">Maternity Leave</option>
                    <option value="PL">Paternity Leave</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select x-model="filters.status" @change="loadHistory" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                    <option value="withdrawn">Withdrawn</option>
                    <option value="draft">Draft</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                <select x-model="filters.year" @change="loadHistory" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Years</option>
                    <template x-for="year in availableYears" :key="year">
                        <option :value="year" x-text="year"></option>
                    </template>
                </select>
            </div>

            <div class="flex items-end">
                <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Usage Patterns Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Usage Patterns</h2>
        <div class="relative h-64">
            <canvas id="usageChart"></canvas>
        </div>
    </div>

    <!-- Balance Projections -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Balance Projections</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">Current VL Balance</p>
                <p class="text-2xl font-bold text-blue-600" x-text="projections.current_vl"></p>
                <p class="text-xs text-gray-500 mt-1">As of today</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">Projected VL (End of Year)</p>
                <p class="text-2xl font-bold" :class="projections.projected_vl >= 0 ? 'text-green-600' : 'text-red-600'" x-text="projections.projected_vl"></p>
                <p class="text-xs text-gray-500 mt-1">Based on current usage</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">Current SL Balance</p>
                <p class="text-2xl font-bold text-green-600" x-text="projections.current_sl"></p>
                <p class="text-xs text-gray-500 mt-1">As of today</p>
            </div>
        </div>
    </div>

    <!-- Leave History Table -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="record in history" :key="record.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="record.leave_type"></div>
                                <div class="text-sm text-gray-500" x-text="record.leave_type_code"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="record.start_date"></div>
                                <div class="text-sm text-gray-500" x-text="'to ' + record.end_date"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="record.days_requested"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                                    'bg-yellow-100 text-yellow-800': record.status === 'pending',
                                    'bg-green-100 text-green-800': record.status === 'approved',
                                    'bg-red-100 text-red-800': record.status === 'rejected',
                                    'bg-gray-100 text-gray-800': record.status === 'withdrawn',
                                    'bg-blue-100 text-blue-800': record.status === 'draft'
                                }" x-text="record.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="record.applied_date"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button @click="viewDetails(record)" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                <button x-show="record.status === 'draft'" @click="editDraft(record)" class="text-green-600 hover:text-green-900 mr-3">Edit</button>
                                <button x-show="record.status === 'pending'" @click="withdrawApplication(record.id)" class="text-red-600 hover:text-red-900">Withdraw</button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="loading">
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            <div class="flex justify-center">
                                <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading history...
                            </div>
                        </td>
                    </tr>

                    <tr x-show="!loading && history.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="text-sm">No leave history found</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function leaveHistory() {
    return {
        history: [],
        analytics: {
            total_used: 0,
            vl_used: 0,
            sl_used: 0,
            average_yearly: 0
        },
        projections: {
            current_vl: 0,
            projected_vl: 0,
            current_sl: 0
        },
        filters: {
            leave_type: '',
            status: '',
            year: ''
        },
        availableYears: [],
        loading: false,
        usageChart: null,

        init() {
            this.availableYears = this.generateYearOptions();
            this.loadHistory();
            this.loadAnalytics();
            this.initChart();
        },

        generateYearOptions() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let year = currentYear; year >= currentYear - 10; year--) {
                years.push(year.toString());
            }
            return years;
        },

        async loadHistory() {
            this.loading = true;

            try {
                const params = new URLSearchParams(
                    Object.fromEntries(Object.entries(this.filters).filter(([_, v]) => v !== ''))
                );

                const response = await fetch(`/employee-portal/leave-history?${params}`);
                const data = await response.json();

                this.history = data.history;
                this.analytics = data.analytics;
                this.projections = data.projections;

                this.updateChart();
            } catch (error) {
                console.error('Error loading history:', error);
                this.showNotification('Error loading leave history', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadAnalytics() {
            try {
                const response = await fetch('/employee-portal/leave-analytics');
                const data = await response.json();

                this.analytics = data.analytics;
                this.projections = data.projections;
            } catch (error) {
                console.error('Error loading analytics:', error);
            }
        },

        initChart() {
            const ctx = document.getElementById('usageChart');
            if (ctx) {
                this.usageChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Vacation Leave',
                            data: [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Sick Leave',
                            data: [],
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4
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
            }
        },

        updateChart() {
            if (this.usageChart && this.history.length > 0) {
                const monthlyData = this.calculateMonthlyUsage();
                this.usageChart.data.datasets[0].data = monthlyData.vl;
                this.usageChart.data.datasets[1].data = monthlyData.sl;
                this.usageChart.update();
            }
        },

        calculateMonthlyUsage() {
            const monthlyUsage = {
                vl: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                sl: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
            };

            this.history.forEach(record => {
                if (record.status === 'approved') {
                    const startMonth = new Date(record.start_date).getMonth();
                    const endMonth = new Date(record.end_date).getMonth();

                    for (let month = startMonth; month <= endMonth; month++) {
                        if (record.leave_type_code === 'VL') {
                            monthlyUsage.vl[month] += record.days_requested;
                        } else if (record.leave_type_code === 'SL') {
                            monthlyUsage.sl[month] += record.days_requested;
                        }
                    }
                }
            });

            return monthlyUsage;
        },

        resetFilters() {
            this.filters = {
                leave_type: '',
                status: '',
                year: ''
            };
            this.loadHistory();
        },

        viewDetails(record) {
            // Open modal with detailed information
            console.log('View details:', record);
        },

        editDraft(record) {
            // Redirect to dashboard Quick Apply with draft preloaded
            window.location.href = `/employee-portal/leave-dashboard?draft_id=${record.id}`;
        },

        async withdrawApplication(applicationId) {
            const message = 'Are you sure you want to withdraw this application?';
            const confirmed = window.confirmDialog
                ? await window.confirmDialog({
                    title: 'Withdraw Application',
                    message,
                    confirmLabel: 'Withdraw',
                    cancelLabel: 'Keep Submitted'
                })
                : window.confirm(message);

            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch(`/employee-portal/leave-applications/${applicationId}/withdraw`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    this.showNotification('Application withdrawn successfully', 'success');
                    this.loadHistory();
                } else {
                    const data = await response.json();
                    this.showNotification(data.error || 'Failed to withdraw application', 'error');
                }
            } catch (error) {
                console.error('Error withdrawing application:', error);
                this.showNotification('Error withdrawing application', 'error');
            }
        },

        exportData() {
            // Generate CSV export
            let csv = 'Leave Type,Start Date,End Date,Days,Status,Applied Date\n';

            this.history.forEach(record => {
                csv += `${record.leave_type},${record.start_date},${record.end_date},${record.days_requested},${record.status},${record.applied_date}\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `leave-history-${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        },

        printHistory() {
            window.print();
        },

        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }
}
</script>
@endpush
@endsection
