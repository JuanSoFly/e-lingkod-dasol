@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Employee Self-Service Portal</h1>
                    <p class="text-lg text-gray-600 mt-1">Welcome back, {{ $employee->first_name }} {{ $employee->last_name }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg px-4 py-3 border border-gray-200">
                    <div class="text-sm text-gray-600 space-y-1">
                        <div><span class="font-medium">Employee ID:</span> {{ $employee->employee_number }}</div>
                        <div><span class="font-medium">Position:</span> {{ $employee->position }}</div>
                        <div><span class="font-medium">Department:</span> {{ $employee->department }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Quick Actions -->
        <div class="mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-bolt text-blue-600 mr-3"></i>
                        Quick Actions
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <a href="{{ route('leave-applications.create') }}"
                           class="group relative bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-lg p-6 transition-all duration-200 hover:shadow-md border border-blue-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-alt text-blue-600 text-2xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-900 group-hover:text-blue-800">Apply for Leave</h3>
                                    <p class="text-xs text-blue-700 mt-1">Submit leave requests</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('employee-portal.document-requests') }}"
                           class="group relative bg-gradient-to-r from-emerald-50 to-emerald-100 hover:from-emerald-100 hover:to-emerald-200 rounded-lg p-6 transition-all duration-200 hover:shadow-md border border-emerald-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-file-alt text-emerald-600 text-2xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-emerald-900 group-hover:text-emerald-800">Request Documents</h3>
                                    <p class="text-xs text-emerald-700 mt-1">Official certificates</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('pds.dashboard', auth()->user()->employee) }}"
                           class="group relative bg-gradient-to-r from-amber-50 to-amber-100 hover:from-amber-100 hover:to-amber-200 rounded-lg p-6 transition-all duration-200 hover:shadow-md border border-amber-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-id-card text-amber-600 text-2xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-amber-900 group-hover:text-amber-800">Update Personal Data Sheet</h3>
                                    <p class="text-xs text-amber-700 mt-1">Complete PDS panels</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('employee-portal.service-record') }}"
                           class="group relative bg-gradient-to-r from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-lg p-6 transition-all duration-200 hover:shadow-md border border-purple-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-history text-purple-600 text-2xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-purple-900 group-hover:text-purple-800">View Service Record</h3>
                                    <p class="text-xs text-purple-700 mt-1">Employment history</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('employee-portal.my-201-file') }}"
                           class="group relative bg-gradient-to-r from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 rounded-lg p-6 transition-all duration-200 hover:shadow-md border border-indigo-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-folder-open text-indigo-600 text-2xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-indigo-900 group-hover:text-indigo-800">View 201 File</h3>
                                    <p class="text-xs text-indigo-700 mt-1">Complete employee record</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Metrics -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Personal Metrics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Leave Balance -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-600">Leave Balance</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ $metrics['leave_balance']['total'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                Used: {{ $metrics['leave_balance']['used_this_year'] ?? 0 }} days this year
                            </p>
                        </div>
                    </div>
                    @if(($metrics['leave_balance']['pending_applications'] ?? 0) > 0)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                {{ $metrics['leave_balance']['pending_applications'] }} pending applications
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Document Requests -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-alt text-emerald-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-600">Document Requests</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ $metrics['document_requests']['total_this_year'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-1">This year</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
                        @if(($metrics['document_requests']['pending'] ?? 0) > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                {{ $metrics['document_requests']['pending'] }} pending
                            </span>
                        @endif
                        @if(($metrics['document_requests']['ready'] ?? 0) > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $metrics['document_requests']['ready'] }} ready
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Change Requests -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-edit text-amber-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-600">Change Requests</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ ($metrics['change_requests']['pending'] ?? 0) + ($metrics['change_requests']['under_review'] ?? 0) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Active requests</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
                        @if(($metrics['change_requests']['pending'] ?? 0) > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                {{ $metrics['change_requests']['pending'] }} pending
                            </span>
                        @endif
                        @if(($metrics['change_requests']['approved'] ?? 0) > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $metrics['change_requests']['approved'] }} approved
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Performance -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-600">Performance Rating</h3>
                            <p class="text-2xl font-bold text-gray-900">
                                @if($metrics['performance']['latest_rating'])
                                    {{ number_format($metrics['performance']['latest_rating'], 2) }}
                                @else
                                    N/A
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Latest rating</p>
                        </div>
                    </div>
                    @if(($metrics['performance']['targets_this_period'] ?? 0) > 0)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                @php
                                    $completionRate = ($metrics['performance']['completed_targets'] ?? 0) / $metrics['performance']['targets_this_period'] * 100;
                                @endphp
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $completionRate }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ $metrics['performance']['completed_targets'] ?? 0 }}/{{ $metrics['performance']['targets_this_period'] }} targets completed
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Notifications and Announcements Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Notifications -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-bell text-blue-600 mr-3"></i>
                            Notifications & Alerts
                        </h2>
                    </div>
                    <div class="p-6">
                        @if(count($notifications) > 0)
                            <div class="space-y-4">
                                @foreach($notifications as $notification)
                                    <div class="flex items-start space-x-4 p-4 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors duration-200">
                                        <div class="flex-shrink-0 mt-1">
                                            @switch($notification['type'])
                                                @case('success')
                                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-check-circle text-green-600"></i>
                                                    </div>
                                                    @break
                                                @case('warning')
                                                    <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-exclamation-triangle text-amber-600"></i>
                                                    </div>
                                                    @break
                                                @case('danger')
                                                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-exclamation-circle text-red-600"></i>
                                                    </div>
                                                    @break
                                                @default
                                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-info-circle text-blue-600"></i>
                                                    </div>
                                            @endswitch
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-medium text-gray-900 mb-1">{{ $notification['title'] }}</h3>
                                            <p class="text-sm text-gray-600 mb-2">{{ $notification['message'] }}</p>
                                            <p class="text-xs text-gray-500">{{ $notification['date']->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-bell-slash text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
                                <p class="text-gray-500">You're all caught up! No new notifications at this time.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Announcements -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-bullhorn text-blue-600 mr-3"></i>
                            Announcements
                        </h2>
                    </div>
                    <div class="p-6">
                        @if(count($announcements) > 0)
                            <div class="space-y-4">
                                @foreach($announcements as $announcement)
                                    <div class="relative pl-4 border-l-4 @if($announcement['is_important']) border-amber-400 @else border-blue-400 @endif">
                                        <div class="flex items-start justify-between">
                                            <h3 class="text-sm font-medium text-gray-900 mb-1">
                                                {{ $announcement['title'] }}
                                                @if($announcement['is_important'])
                                                    <i class="fas fa-star text-amber-500 ml-1"></i>
                                                @endif
                                            </h3>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-2">{{ $announcement['message'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $announcement['date']->diffForHumans() }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-bullhorn text-gray-400 text-xl"></i>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900 mb-1">No announcements</h3>
                                <p class="text-xs text-gray-500">Check back later for updates</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Additional Tailwind-compatible styles for enhanced hover effects */
.group:hover {
    transform: translateY(-1px);
}

/* Custom scrollbar for notifications if needed */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Smooth transitions for all interactive elements */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}
</style>
@endpush