@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="employeeDashboard()">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Leave Dashboard</h1>
        <p class="text-gray-600">Manage your leave applications and view your leave balances</p>
    </div>

    <!-- Leave Balance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Vacation Leave Balance -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Vacation Leave Balance</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="balances.vl_balance + ' days'"></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Sick Leave Balance -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Sick Leave Balance</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="balances.sl_balance + ' days'"></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Applications -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Pending Applications</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="statistics.pending_applications"></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Apply for Leave -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Apply for Leave</h2>

                <form @submit.prevent="submitLeaveApplication" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
                            <select x-model="application.leave_type_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="">Select Leave Type</option>
                                <template x-for="type in leaveTypes" :key="type.id">
                                    <option :value="type.id" x-text="type.name + ' (' + (type.current_balance || 'N/A') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Days</label>
                            <input type="number" x-model="application.days_requested" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" readonly>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" x-model="application.start_date" @change="calculateDays" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <input type="date" x-model="application.end_date" @change="calculateDays" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                    </div>

                    <div x-show="availability.nonWorking.length" class="bg-yellow-50 border border-yellow-200 rounded-md p-3 text-xs text-yellow-800">
                        <p class="font-medium">Non-working dates in this range:</p>
                        <ul class="list-disc list-inside space-y-0.5 mt-1">
                            <template x-for="date in availability.nonWorking" :key="date">
                                <li x-text="date"></li>
                            </template>
                        </ul>
                        <template x-if="availability.holidays.length">
                            <div class="mt-2">
                                <p class="font-medium">Holidays:</p>
                                <ul class="list-disc list-inside space-y-0.5 mt-1">
                                    <template x-for="holiday in availability.holidays" :key="holiday.date">
                                        <li>
                                            <span x-text="holiday.date"></span>
                                            <span class="ml-1" x-text="'- ' + holiday.name"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                        <textarea x-model="application.reason" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Please provide a reason for your leave application..." required></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Supporting Documents (Optional)</label>
                        <div class="space-y-2">
                            <template x-for="(doc, index) in application.documents" :key="index">
                                <div class="flex items-center space-x-2">
                                    <input type="file" @change="handleDocumentChange($event, index)" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="flex-1 text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <button type="button" @click="removeDocument(index)" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="addDocumentField()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                + Add Document
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">PDF, DOC, DOCX, JPG, JPEG, PNG files only (Max 2MB each)</p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-blue-900 mb-2">Approval of Department Head</h3>
                                <p class="text-sm text-blue-700 mb-3">By submitting the application, you confirm that your respective department head has already been informed.</p>
                                <div class="space-y-2">
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="radio" name="dept_head_informed" x-model="application.dept_head_informed" value="1" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="text-sm text-gray-700">Yes, my department head has been informed</span>
                                    </label>
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="radio" name="dept_head_informed" x-model="application.dept_head_informed" value="0" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="text-sm text-gray-700">No, my department head has not been informed</span>
                                    </label>
                                </div>
                                <p x-show="application.dept_head_informed === '0'" class="text-sm text-red-600 mt-2">
                                    <strong>Please note:</strong> You must inform your department head before submitting this leave application.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="saveAsDraft" :disabled="submitting" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Save as Draft
                        </button>
                        <button type="button" @click="resetApplication" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Clear
                        </button>
                        <button type="submit" :disabled="submitting || rateLimitActive" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                            <span x-show="!submitting && !rateLimitActive">Submit Application</span>
                            <span x-show="submitting">Submitting...</span>
                            <span x-show="rateLimitActive && !submitting" class="flex items-center">
                                <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Wait <span x-text="rateLimitCountdown"></span>s
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Applications -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Applications</h2>

                <div class="space-y-3">
                    <template x-for="app in recentApplications" :key="app.id">
                        <div class="border-l-4" :class="{
                            'border-green-500': app.status === 'approved',
                            'border-yellow-500': app.status === 'pending',
                            'border-red-500': app.status === 'rejected'
                        }">
                            <div class="pl-3">
                                <p class="text-sm font-medium text-gray-900" x-text="app.leave_type"></p>
                                <p class="text-xs text-gray-600" x-text="app.start_date + ' - ' + app.end_date"></p>
                                <p class="text-xs text-gray-500" x-text="app.days_requested + ' days'"></p>
                                <span class="inline-block px-2 py-1 text-xs rounded-full mt-1" :class="{
                                    'bg-green-100 text-green-800': app.status === 'approved',
                                    'bg-yellow-100 text-yellow-800': app.status === 'pending',
                                    'bg-red-100 text-red-800': app.status === 'rejected'
                                }" x-text="app.status"></span>
                            </div>
                        </div>
                    </template>

                    <div x-show="recentApplications.length === 0" class="text-center py-4 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <p class="text-sm">No recent applications</p>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="/employee-portal/leave-applications" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        View All Applications →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Calendar & Announcements -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Leave Calendar -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Leave Calendar</h2>
            <div class="calendar-container" x-data="calendarComponent()">
                <div class="calendar-header flex justify-between items-center mb-4">
                    <button @click="previousMonth()" class="p-2 hover:bg-gray-100 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <h3 class="text-center font-medium" x-text="currentMonthYear"></h3>
                    <button @click="nextMonth()" class="p-2 hover:bg-gray-100 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-7 gap-1 text-center text-xs">
                    <div class="font-medium text-gray-500 py-2">Sun</div>
                    <div class="font-medium text-gray-500 py-2">Mon</div>
                    <div class="font-medium text-gray-500 py-2">Tue</div>
                    <div class="font-medium text-gray-500 py-2">Wed</div>
                    <div class="font-medium text-gray-500 py-2">Thu</div>
                    <div class="font-medium text-gray-500 py-2">Fri</div>
                    <div class="font-medium text-gray-500 py-2">Sat</div>
                    <template x-for="day in calendarDays" :key="day.date">
                        <div
                            class="p-2 text-sm border rounded transition-colors"
                            :class="{
                                'bg-blue-500 text-white border-blue-500 cursor-pointer': day.hasLeave,
                                'bg-red-100 border-red-200 text-red-700 cursor-not-allowed': !day.hasLeave && day.isHoliday && day.isNonWorking,
                                'bg-yellow-100 border-yellow-300 text-yellow-800 cursor-not-allowed': !day.hasLeave && day.isHoliday && !day.isNonWorking,
                                'bg-gray-100 text-gray-500 cursor-not-allowed': !day.hasLeave && !day.isHoliday && day.isNonWorking,
                                'bg-blue-100 text-blue-800 border-blue-200 cursor-pointer': !day.hasLeave && day.isToday,
                                'text-gray-900 hover:bg-gray-100 cursor-pointer': !day.hasLeave && !day.isHoliday && !day.isNonWorking && day.isCurrentMonth,
                                'text-gray-400 cursor-not-allowed': !day.isCurrentMonth
                            }"
                            :title="day.leaveInfo"
                        >
                            <span x-text="day.day"></span>
                        </div>
                    </template>
                </div>

                <div class="mt-3 text-xs text-gray-600 space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="inline-block w-3 h-3 rounded bg-blue-500 border border-blue-500"></span>
                        <span>Approved leave schedules</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-block w-3 h-3 rounded bg-red-100 border border-red-200"></span>
                        <span>Non-working holidays</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-block w-3 h-3 rounded bg-yellow-100 border border-yellow-300"></span>
                        <span>Special working holidays</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-block w-3 h-3 rounded bg-gray-100 border border-gray-100"></span>
                        <span>Regular non-working days per your calendar</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">HR Announcements</h2>
            <div class="space-y-3" x-data="announcementsComponent()">
                <template x-for="announcement in announcements" :key="announcement.id">
                    <div class="border-l-4 border-yellow-400 pl-4 py-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-900" x-text="announcement.title"></p>
                                <p class="text-sm text-gray-600 mt-1" x-text="announcement.excerpt"></p>
                            </div>
                            <span class="text-xs text-gray-500 whitespace-nowrap ml-2" x-text="announcement.date"></span>
                        </div>
                        <a
                            x-show="announcement.link"
                            :href="announcement.link"
                            class="text-blue-600 hover:text-blue-800 text-sm mt-1 inline-block"
                            x-text="'Read more →'"
                        ></a>
                    </div>
                </template>

                <div x-show="announcements.length === 0" class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                    <p class="text-sm">No new announcements</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Leave -->
    <div class="mt-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Approved Leave</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <template x-for="leave in upcomingLeave" :key="leave.id">
                    <div class="border rounded-lg p-4 bg-blue-50 border-blue-200">
                        <p class="font-medium text-blue-900" x-text="leave.leave_type"></p>
                        <p class="text-sm text-blue-700" x-text="leave.start_date + ' - ' + leave.end_date"></p>
                        <p class="text-xs text-blue-600" x-text="leave.days + ' days (' + leave.remaining_days + ' remaining)'"></p>
                    </div>
                </template>

                <div x-show="upcomingLeave.length === 0" class="col-span-3 text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-sm">No upcoming approved leave</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function employeeDashboard() {
    return {
        balances: @json($current_balances),
        recentApplications: @json($recent_applications),
        upcomingLeave: @json($upcoming_leave),
        statistics: @json($statistics),
        leaveTypes: [],
        availability: {
            nonWorking: [],
            holidays: []
        },
        application: {
            leave_type_id: '',
            start_date: '',
            end_date: '',
            days_requested: 0,
            reason: '',
            documents: [],
            dept_head_informed: ''
        },
        submitting: false,
        rateLimitActive: false,
        rateLimitCountdown: 0,

        init() {
            this.loadLeaveTypes();
        },

  
        async loadLeaveTypes() {
            try {
                const response = await fetch('/employee-portal/leave-applications/create');
                const data = await response.json();

                this.leaveTypes = data.leave_types;
            } catch (error) {
                console.error('Error loading leave types:', error);
            }
        },

        async calculateDays() {
            if (!this.application.start_date || !this.application.end_date) {
                this.application.days_requested = 0;
                return;
            }

            const start = new Date(this.application.start_date);
            const end = new Date(this.application.end_date);

            if (end < start) {
                this.application.days_requested = 0;
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]').content;

            try {
                const response = await fetch('/employee-portal/leave-applications/calculate-days', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        start_date: this.application.start_date,
                        end_date: this.application.end_date,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    this.application.days_requested = data.days;
                    this.availability.nonWorking = data.non_working_dates || [];
                    this.availability.holidays = data.holidays || [];
                } else {
                    this.application.days_requested = 0;
                    this.availability.nonWorking = [];
                    this.availability.holidays = [];
                    if (data.message) {
                        this.showNotification(data.message, 'error');
                    }
                }
            } catch (error) {
                console.error('Error calculating working days:', error);
                this.application.days_requested = 0;
                this.availability.nonWorking = [];
                this.availability.holidays = [];
                this.showNotification('Unable to calculate working days right now. Please try again later.', 'error');
            }
        },

        async submitLeaveApplication() {
            if (!this.validateApplication()) {
                return;
            }

            this.submitting = true;

            try {
                const formData = new FormData();

                // Add form fields
                formData.append('leave_type_id', this.application.leave_type_id);
                formData.append('start_date', this.application.start_date);
                formData.append('end_date', this.application.end_date);
                formData.append('reason', this.application.reason);
                formData.append('dept_head_informed', this.application.dept_head_informed === '1' ? '1' : '0');

                // Add documents
                this.application.documents.forEach((doc, index) => {
                    if (doc.file) {
                        formData.append(`documents[${index}]`, doc.file);
                    }
                });

                const response = await fetch('/employee-portal/leave-applications', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    this.showNotification('Leave application submitted successfully!', 'success');
                    this.resetApplication();
                    // Reload page to show updated data
                    setTimeout(() => location.reload(), 1500);
                } else if (response.status === 429) {
                    // Handle rate limiting with user-friendly message
                    this.handleRateLimitResponse(data);
                } else {
                    this.showNotification(data.error || 'Failed to submit application', 'error');
                }
            } catch (error) {
                console.error('Error submitting application:', error);
                this.showNotification('An error occurred while submitting your application', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async saveAsDraft() {
            if (!this.application.leave_type_id && !this.application.start_date) {
                this.showNotification('Please add at least a leave type or start date to save as draft', 'error');
                return;
            }

            this.submitting = true;

            try {
                const formData = new FormData();

                // Add form fields
                formData.append('leave_type_id', this.application.leave_type_id);
                formData.append('start_date', this.application.start_date);
                formData.append('end_date', this.application.end_date);
                formData.append('reason', this.application.reason);
                formData.append('dept_head_informed', this.application.dept_head_informed === '1' ? '1' : '0');
                formData.append('is_draft', 'true');

                // Add documents
                this.application.documents.forEach((doc, index) => {
                    if (doc.file) {
                        formData.append(`documents[${index}]`, doc.file);
                    }
                });

                const response = await fetch('/employee-portal/leave-applications/draft', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    this.showNotification('Application saved as draft!', 'success');
                } else {
                    this.showNotification(data.error || 'Failed to save draft', 'error');
                }
            } catch (error) {
                console.error('Error saving draft:', error);
                this.showNotification('An error occurred while saving your draft', 'error');
            } finally {
                this.submitting = false;
            }
        },

        addDocumentField() {
            this.application.documents.push({ file: null, name: '' });
        },

        removeDocument(index) {
            this.application.documents.splice(index, 1);
        },

        handleDocumentChange(event, index) {
            const file = event.target.files[0];
            if (file) {
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    this.showNotification('File size must be less than 2MB', 'error');
                    return;
                }

                // Validate file type
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    this.showNotification('Invalid file type. Only PDF, DOC, DOCX, JPG, JPEG, PNG files are allowed', 'error');
                    return;
                }

                this.application.documents[index] = {
                    file: file,
                    name: file.name
                };
            }
        },

        validateApplication() {
            if (!this.application.leave_type_id) {
                this.showNotification('Please select a leave type', 'error');
                return false;
            }

            if (!this.application.start_date || !this.application.end_date) {
                this.showNotification('Please select start and end dates', 'error');
                return false;
            }

            if (!this.application.reason.trim()) {
                this.showNotification('Please provide a reason for your leave', 'error');
                return false;
            }

            if (this.application.dept_head_informed === '') {
                this.showNotification('Please confirm if your department head has been informed', 'error');
                return false;
            }

            if (this.application.dept_head_informed === '0' || this.application.dept_head_informed === 0) {
                this.showNotification('You must inform your department head before submitting this leave application', 'error');
                return false;
            }

            return true;
        },

        resetApplication() {
            this.application = {
                leave_type_id: '',
                start_date: '',
                end_date: '',
                days_requested: 0,
                reason: '',
                documents: [],
                dept_head_informed: ''
            };
        },

        handleRateLimitResponse(data) {
            const retryAfter = data.retry_after || 60;
            const attemptsUsed = data.attempts_used || 0;
            const maxAttempts = data.max_attempts || 10;

            // Start countdown timer
            this.startRateLimitCountdown(retryAfter);

            // Show detailed rate limit message
            const message = `Rate limit reached (${attemptsUsed}/${maxAttempts} attempts). Please wait ${retryAfter} seconds before trying again.`;
            this.showRateLimitNotification(message, retryAfter);
        },

        startRateLimitCountdown(seconds) {
            this.rateLimitCountdown = seconds;
            this.rateLimitActive = true;

            const countdownInterval = setInterval(() => {
                this.rateLimitCountdown--;

                if (this.rateLimitCountdown <= 0) {
                    clearInterval(countdownInterval);
                    this.rateLimitActive = false;
                }
            }, 1000);
        },

        showRateLimitNotification(message, retryAfter) {
            // Create a special rate limit notification with countdown
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 bg-yellow-500 text-white max-w-md';
            notification.innerHTML = `
                <div class="flex items-start">
                    <svg class="w-6 h-6 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div>
                        <p class="font-medium">Please Slow Down</p>
                        <p class="text-sm mt-1">${message}</p>
                        <div class="mt-2 text-sm font-medium">
                            You can try again in: <span id="countdown">${retryAfter}</span> seconds
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(notification);

            // Update countdown display
            const countdownElement = notification.querySelector('#countdown');
            const countdownInterval = setInterval(() => {
                const currentSeconds = parseInt(countdownElement.textContent);
                if (currentSeconds > 0) {
                    countdownElement.textContent = currentSeconds - 1;
                } else {
                    clearInterval(countdownInterval);
                    notification.remove();
                }
            }, 1000);

            // Auto-remove after retryAfter + 5 seconds
            setTimeout(() => {
                clearInterval(countdownInterval);
                if (notification.parentNode) {
                    notification.remove();
                }
            }, (retryAfter + 5) * 1000);
        },

        showNotification(message, type = 'info') {
            // Simple notification implementation
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

// Calendar Component
function calendarComponent() {
    return {
        currentDate: new Date(),
        calendarDays: [],
        currentMonthYear: '',
        approvedLeave: [],
        holidayMap: {},
        nonWorkingLookup: {},
        workWeek: null,

        async init() {
            await this.refreshMonth();
        },

        async refreshMonth() {
            const { startDate, endDate } = this.getCurrentRange();
            await this.loadCalendarData(startDate, endDate);
            this.generateCalendar();
        },

        getCurrentRange() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const start = new Date(year, month, 1);
            const end = new Date(year, month + 1, 0);

            return {
                startDate: start,
                endDate: end,
            };
        },

        async loadCalendarData(start, end) {
            const params = new URLSearchParams({
                start_date: this.formatDate(start),
                end_date: this.formatDate(end),
            });

            try {
                const response = await fetch(`/employee-portal/calendar-data?${params.toString()}`);
                const data = await response.json();

                this.approvedLeave = data.approved_leave || [];
                this.workWeek = data.work_week || null;

                this.holidayMap = (data.holidays || []).reduce((acc, holiday) => {
                    acc[holiday.date] = holiday;
                    return acc;
                }, {});

                this.nonWorkingLookup = (data.non_working_dates || []).reduce((acc, date) => {
                    acc[date] = true;
                    return acc;
                }, {});
            } catch (error) {
                console.error('Error loading calendar data:', error);
                this.approvedLeave = [];
                this.holidayMap = {};
                this.nonWorkingLookup = {};
            }
        },

        generateCalendar() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            this.currentMonthYear = this.currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const prevLastDay = new Date(year, month, 0);

            const startingDayOfWeek = firstDay.getDay();
            const monthLength = lastDay.getDate();
            const prevMonthLength = prevLastDay.getDate();

            this.calendarDays = [];

            for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                const day = prevMonthLength - i;
                const date = new Date(year, month - 1, day);
                this.calendarDays.push(this.createCalendarDay(date, day, false));
            }

            for (let day = 1; day <= monthLength; day++) {
                const date = new Date(year, month, day);
                this.calendarDays.push(this.createCalendarDay(date, day, true));
            }

            const remainingDays = 42 - this.calendarDays.length;
            for (let day = 1; day <= remainingDays; day++) {
                const date = new Date(year, month + 1, day);
                this.calendarDays.push(this.createCalendarDay(date, day, false));
            }
        },

        createCalendarDay(date, day, isCurrentMonth) {
            const dateStr = this.formatDate(date);
            const isToday = date.toDateString() === new Date().toDateString();

            const leaveOnDate = this.approvedLeave.find(leave =>
                dateStr >= leave.start_date && dateStr <= leave.end_date
            );

            const holiday = this.holidayMap[dateStr] || null;
            const isHoliday = !!holiday;
            const isNonWorking = !!this.nonWorkingLookup[dateStr];

            let tooltip = '';
            if (leaveOnDate) {
                tooltip = `${leaveOnDate.leave_type}: ${leaveOnDate.start_date} to ${leaveOnDate.end_date}`;
            }

            if (holiday) {
                const holidayLabel = `${holiday.name}${holiday.is_non_working ? ' (non-working holiday)' : ' (special working day)'}`;
                tooltip = tooltip ? `${holidayLabel}\n${tooltip}` : holidayLabel;
            }

            return {
                date: dateStr,
                day,
                isCurrentMonth,
                isToday,
                hasLeave: !!leaveOnDate,
                leaveInfo: tooltip,
                isHoliday,
                isNonWorking,
                holiday,
            };
        },

        async previousMonth() {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            await this.refreshMonth();
        },

        async nextMonth() {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            await this.refreshMonth();
        },

        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    }
}

// Announcements Component
function announcementsComponent() {
    return {
        announcements: [],

        init() {
            this.loadAnnouncements();
        },

        async loadAnnouncements() {
            try {
                const response = await fetch('/employee-portal/announcements');
                const data = await response.json();
                this.announcements = data.announcements || this.getDefaultAnnouncements();
            } catch (error) {
                console.error('Error loading announcements:', error);
                this.announcements = this.getDefaultAnnouncements();
            }
        },

        getDefaultAnnouncements() {
            return [
                {
                    id: 1,
                    title: 'Leave Policy Update',
                    excerpt: 'Updated guidelines for vacation and sick leave applications are now available.',
                    date: 'Oct 15, 2025',
                    link: '/policies/leave-updates'
                },
                {
                    id: 2,
                    title: 'Holiday Schedule 2025',
                    excerpt: 'The list of official holidays for 2025 has been published.',
                    date: 'Oct 10, 2025',
                    link: '/holidays/2025'
                }
            ];
        }
    }
}
</script>
@endpush
@endsection
