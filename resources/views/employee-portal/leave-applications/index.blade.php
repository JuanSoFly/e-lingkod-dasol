@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="leaveApplications()">
    <!-- Page Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Leave Applications</h1>
            <p class="text-gray-600">View and manage your leave application history</p>
        </div>
        <a href="/employee-portal/dashboard" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            ← Back to Dashboard
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select x-model="filters.status" @change="loadApplications" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="withdrawn">Withdrawn</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                <select x-model="filters.year" @change="loadApplications" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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

    <!-- Applications List -->
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
                    <template x-for="application in applications" :key="application.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="application.leave_type"></div>
                                <div class="text-sm text-gray-500" x-text="application.leave_type_code"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="application.start_date"></div>
                                <div class="text-sm text-gray-500" x-text="'to ' + application.end_date"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="application.days_requested"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                                    'bg-yellow-100 text-yellow-800': application.status === 'pending',
                                    'bg-green-100 text-green-800': application.status === 'approved',
                                    'bg-red-100 text-red-800': application.status === 'rejected',
                                    'bg-gray-100 text-gray-800': application.status === 'withdrawn'
                                }" x-text="application.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="application.applied_date"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button @click="viewApplication(application)" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                <button x-show="application.status === 'pending'" @click="withdrawApplication(application.id)" class="text-red-600 hover:text-red-900">Withdraw</button>
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
                                Loading applications...
                            </div>
                        </td>
                    </tr>

                    <tr x-show="!loading && applications.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="text-sm">No leave applications found</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button @click="previousPage" :disabled="pagination.current_page === 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </button>
                    <button @click="nextPage" :disabled="pagination.current_page === pagination.last_page" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium" x-text="(pagination.current_page - 1) * pagination.per_page + 1"></span>
                            to
                            <span class="font-medium" x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                            of
                            <span class="font-medium" x-text="pagination.total"></span>
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <button @click="previousPage" :disabled="pagination.current_page === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                Previous
                            </button>
                            <button @click="nextPage" :disabled="pagination.current_page === pagination.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                Next
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                Application Details
                            </h3>

                            <div x-show="selectedApplication" class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Leave Type</label>
                                    <p class="mt-1 text-sm text-gray-900" x-text="selectedApplication?.leave_type"></p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Start Date</label>
                                        <p class="mt-1 text-sm text-gray-900" x-text="selectedApplication?.start_date"></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">End Date</label>
                                        <p class="mt-1 text-sm text-gray-900" x-text="selectedApplication?.end_date"></p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Days Requested</label>
                                        <p class="mt-1 text-sm text-gray-900" x-text="selectedApplication?.days_requested"></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Status</label>
                                        <p class="mt-1">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                                                'bg-yellow-100 text-yellow-800': selectedApplication?.status === 'pending',
                                                'bg-green-100 text-green-800': selectedApplication?.status === 'approved',
                                                'bg-red-100 text-red-800': selectedApplication?.status === 'rejected',
                                                'bg-gray-100 text-gray-800': selectedApplication?.status === 'withdrawn'
                                            }" x-text="selectedApplication?.status"></span>
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Reason</label>
                                    <p class="mt-1 text-sm text-gray-900" x-text="selectedApplication?.reason"></p>
                                </div>

                                <div x-show="selectedApplication?.remarks">
                                    <label class="text-sm font-medium text-gray-700">Remarks</label>
                                    <p class="mt-1 text-sm text-gray-900" x-text="selectedApplication?.remarks"></p>
                                </div>

                                <div x-show="selectedApplication?.approver">
                                    <label class="text-sm font-medium text-gray-700">Processed By</label>
                                    <p class="mt-1 text-sm text-gray-900" x-text="selectedApplication?.approver"></p>
                                    <p class="text-xs text-gray-500" x-text="'on ' + (selectedApplication?.approved_date || '')"></p>
                                </div>

                                <div x-show="selectedApplication?.documents?.length > 0">
                                    <label class="text-sm font-medium text-gray-700">Supporting Documents</label>
                                    <div class="mt-2 space-y-2">
                                        <template x-for="doc in selectedApplication.documents" :key="doc.id">
                                            <a :href="'/documents/' + doc.id + '/download'" class="flex items-center text-blue-600 hover:text-blue-800">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span x-text="doc.filename"></span>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="showModal = false" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function leaveApplications() {
    return {
        applications: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        },
        filters: {
            status: '',
            year: ''
        },
        availableYears: [],
        loading: false,
        showModal: false,
        selectedApplication: null,

        init() {
            this.availableYears = this.generateYearOptions();
            this.loadApplications();
        },

        generateYearOptions() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let year = currentYear; year >= currentYear - 5; year--) {
                years.push(year.toString());
            }
            return years;
        },

        async loadApplications(page = 1) {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    page: page,
                    ...Object.fromEntries(Object.entries(this.filters).filter(([_, v]) => v !== ''))
                });

                const response = await fetch(`/employee-portal/leave-applications/data?${params}`);
                const data = await response.json();

                this.applications = data.applications;
                this.pagination = data.pagination;
            } catch (error) {
                console.error('Error loading applications:', error);
                this.showNotification('Error loading applications', 'error');
            } finally {
                this.loading = false;
            }
        },

        viewApplication(application) {
            this.selectedApplication = application;
            this.showModal = true;
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
                    this.loadApplications(this.pagination.current_page);
                } else {
                    const data = await response.json();
                    this.showNotification(data.error || 'Failed to withdraw application', 'error');
                }
            } catch (error) {
                console.error('Error withdrawing application:', error);
                this.showNotification('Error withdrawing application', 'error');
            }
        },

        resetFilters() {
            this.filters = {
                status: '',
                year: ''
            };
            this.loadApplications(1);
        },

        previousPage() {
            if (this.pagination.current_page > 1) {
                this.loadApplications(this.pagination.current_page - 1);
            }
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.loadApplications(this.pagination.current_page + 1);
            }
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
