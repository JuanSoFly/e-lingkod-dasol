@extends('layouts.full-width')

@section('content')
<div class="min-h-screen bg-gray-50/50">
    <!-- Hero Section -->
    <div class="relative z-0 bg-blue-600 bg-gradient-to-r from-blue-600 to-indigo-700 pb-10 isolation-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 flex-wrap">
                <div class="text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 rounded-full bg-white/20 text-xs font-semibold backdrop-blur-sm border border-white/10">
                            {{ $userRole ?? 'Dashboard' }}
                        </span>
                        <p class="text-blue-100 font-medium">Welcome back,</p>
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight">{{ Auth::user()->full_name }}</h1>
                    <p class="text-blue-100 mt-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>{{ now()->format('l, F j, Y') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 space-y-8">
        
        @php
            $roleView = $dashboardData['role_view'] ?? 'employee';
        @endphp

        <!-- Core HR Metrics -->
        @if($roleView !== 'assessor')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Employees -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Employees</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalEmployees) }}</h3>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-600">
                    <span class="text-green-600 font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        {{ number_format($totalEmployees - $leaveToday) }}
                    </span>
                    <span class="ml-1">active today</span>
                </div>
            </div>

            <!-- On Leave -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">On Leave Today</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($leaveToday) }}</h3>
                    </div>
                    <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <a href="{{ route('leave-applications.index') }}?status=approved" class="hover:text-orange-600 hover:underline">View details &rarr;</a>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pending Approvals</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($pendingLeaveApps) }}</h3>
                    </div>
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    @if($pendingLeaveApps > 0)
                        <span class="text-yellow-600 font-medium">Action required</span>
                        <a href="{{ route('leave-applications.index') }}?status=pending" class="ml-1 hover:text-yellow-700 underline">Review now</a>
                    @else
                        <span class="text-green-600">All caught up!</span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- OPCR Section -->
        @if($canViewOPCR || $isDepartmentHead || $isAssessor || $isFinalApprover)
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="w-1 h-6 bg-purple-600 rounded-full"></span>
                Performance Management (OPCR)
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Workflows -->
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl p-6 border border-purple-100">
                    <div class="flex justify-between items-center mb-4">
                        <div class="p-2 bg-purple-100 text-purple-600 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <span class="text-xs font-semibold text-purple-600 bg-purple-100 px-2 py-1 rounded-full">Period Total</span>
                    </div>
                    <h3 class="text-2xl font-bold text-purple-900">{{ number_format($opcrData['metrics']['total_workflows'] ?? 0) }}</h3>
                    <p class="text-sm text-purple-700 mt-1">Active Workflows</p>
                    <a href="{{ route('opcr.dashboard') }}" class="mt-4 block text-sm font-medium text-purple-600 hover:text-purple-800">View Dashboard &rarr;</a>
                </div>

                <!-- Pending Tasks -->
                @if($isDepartmentHead || $isAssessor || $isFinalApprover)
                @php
                    $pendingCount = ($isDepartmentHead ? ($opcrData['pending_actions']['draft_workflows'] ?? 0) + ($opcrData['pending_actions']['returned_workflows'] ?? 0) : 0) +
                                    ($isAssessor ? ($opcrData['pending_assessments']['evaluation_workflows'] ?? 0) : 0) +
                                    ($isFinalApprover ? ($opcrData['pending_approvals']['final_approval_workflows'] ?? 0) : 0);
                @endphp
                <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-2xl p-6 border border-orange-100">
                    <div class="flex justify-between items-center mb-4">
                        <div class="p-2 bg-orange-100 text-orange-600 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        @if($pendingCount > 0)
                            <span class="text-xs font-semibold text-red-600 bg-red-100 px-2 py-1 rounded-full animate-pulse">Action Required</span>
                        @endif
                    </div>
                    <h3 class="text-2xl font-bold text-orange-900">{{ number_format($pendingCount) }}</h3>
                    <p class="text-sm text-orange-700 mt-1">Pending Tasks</p>
                    <a href="{{ route('opcr.workflows.index') }}" class="mt-4 block text-sm font-medium text-orange-600 hover:text-orange-800">Process Now &rarr;</a>
                </div>
                @endif

                <!-- Completed Tasks -->
                @if(!$isDepartmentHead)
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-100">
                    <div class="flex justify-between items-center mb-4">
                        <div class="p-2 bg-green-100 text-green-600 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-green-900">{{ number_format($opcrData['metrics']['completed_workflows'] ?? 0) }}</h3>
                    <p class="text-sm text-green-700 mt-1">Completed Reviews</p>
                    <a href="{{ route('opcr.archive.index') }}" class="mt-4 block text-sm font-medium text-green-600 hover:text-green-800">View Archive &rarr;</a>
                </div>
                @endif

                <!-- Average Rating -->
                @if($isAssessor || $isFinalApprover || $canManageOPCR)
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-6 border border-blue-100">
                    <div class="flex justify-between items-center mb-4">
                        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-blue-900">{{ number_format($opcrData['metrics']['average_rating'] ?? 0, 2) }}</h3>
                    <p class="text-sm text-blue-700 mt-1">Average Rating</p>
                    <a href="{{ route('opcr.analytics.index') }}" class="mt-4 block text-sm font-medium text-blue-600 hover:text-blue-800">View Analytics &rarr;</a>
                </div>
                @endif
            </div>

            <!-- Enhanced Actions Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @if($canManageOPCR || $isDepartmentHead)
                <a href="{{ route('opcr.workflows.create') }}" class="group flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:border-purple-400 hover:shadow-md transition-all">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-lg group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="font-semibold text-gray-900">Create New OPCR</h4>
                        <p class="text-sm text-gray-500">Start a new workflow</p>
                    </div>
                </a>
                @endif
                
                @if($canViewOPCR)
                <a href="{{ route('opcr.analytics.index') }}" class="group flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-md transition-all">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-lg group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="font-semibold text-gray-900">Full Analytics</h4>
                        <p class="text-sm text-gray-500">View detailed reports</p>
                    </div>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Bottom Section: Charts & Birthdays -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Chart Section -->
            <div class="{{ $roleView === 'assessor' ? 'lg:col-span-3' : 'lg:col-span-2' }} bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">Workforce Distribution</h3>
                        <p class="text-sm text-gray-500">Employees by Department</p>
                    </div>
                </div>
                <div class="h-80 relative">
                     <canvas id="employeesByDeptChart"></canvas>
                </div>
            </div>

            <!-- Right Column: Quick Stats & Birthdays -->
            @if($roleView !== 'assessor')
            <div class="space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 text-lg mb-4">Quick Overview</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <span class="text-gray-500">Total Staff</span>
                            <span class="font-mono font-bold text-xl text-gray-900">{{ number_format($totalEmployees) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <span class="text-gray-500">Present</span>
                            <span class="font-mono font-bold text-xl text-emerald-600">{{ number_format($totalEmployees - $leaveToday) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">On Leave</span>
                            <span class="font-mono font-bold text-xl text-orange-500">{{ number_format($leaveToday) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Birthdays -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9V3m0 0a.75.75 0 110-1.5.75.75 0 010 1.5zM20 21H4a1 1 0 01-1-1v-4a4 4 0 014-4h10a4 4 0 014 4v4a1 1 0 01-1 1zm-3-9H7M12 12V9" />
                        </svg>
                        Upcoming Birthdays
                    </h3>
                    <div class="space-y-4">
                        @forelse($upcomingBirthdays as $employee)
                            <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                                <div class="w-10 h-10 bg-rose-50 border border-rose-100 text-rose-700 rounded-full flex items-center justify-center font-bold text-sm select-none">
                                    {{ $employee->avatar_initials }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 truncate">{{ $employee->full_name }}</h4>
                                    <p class="text-xs text-gray-500">{{ $employee->birth_date?->format('F j') ?? 'Date unknown' }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-gray-400 text-sm">
                                No upcoming birthdays this week.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Employees by Department Chart
    const deptCtx = document.getElementById('employeesByDeptChart').getContext('2d');
    const employeesByDeptData = @json($employeesByDept);
    
    if (Object.keys(employeesByDeptData).length === 0) {
        document.getElementById('employeesByDeptChart').parentElement.innerHTML = 
            '<div class="flex items-center justify-center h-full text-gray-400 bg-gray-50 rounded-lg">' +
            'No department data available' +
            '</div>';
        return;
    }

    new Chart(deptCtx, {
        type: 'bar', // Changed to bar for better readability in this layout
        data: {
            labels: Object.keys(employeesByDeptData),
            datasets: [{
                label: 'Employees',
                data: Object.values(employeesByDeptData),
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4
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
                    grid: {
                        display: true,
                        drawBorder: false,
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>
@endpush