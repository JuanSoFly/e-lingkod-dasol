<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <!-- Stat Card: Total Employees -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Employees</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalEmployees) }}</p>
                    </div>
                </div>
            </div>
            <!-- Stat Card: On Leave Today -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4M8 7H3a1 1 0 00-1 1v2a1 1 0 001 1h5M8 7h8m8 0v12a1 1 0 01-1 1H5a1 1 0 01-1-1V8a1 1 0 011-1h3"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">On Leave Today</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($leaveToday) }}</p>
                    </div>
                </div>
            </div>
            <!-- Stat Card: Pending Requests -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Pending Requests</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($pendingLeaveApps) }}</p>
                    </div>
                </div>
            </div>
            <!-- Stat Card: Upcoming Birthdays -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4M8 7H3a1 1 0 00-1 1v2a1 1 0 001 1h5M8 7h8m0 0V6a2 2 0 012-2h1a2 2 0 012 2v1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Upcoming Birthdays</h3>
                        <div class="mt-2 space-y-1 max-h-16 overflow-y-auto">
                            @forelse($upcomingBirthdays->take(3) as $employee)
                                <div class="text-sm text-gray-700 truncate">{{ $employee->first_name }} {{ $employee->last_name }} - {{ $employee->birth_date?->format('M d') ?? 'Date unknown' }}</div>
                            @empty
                                <div class="text-sm text-gray-400">No upcoming birthdays</div>
                            @endforelse
                            @if($upcomingBirthdays->count() > 3)
                                <div class="text-xs text-blue-600">+{{ $upcomingBirthdays->count() - 3 }} more</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-lg text-gray-900">Employees by Department</h3>
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="employeesByDeptChart" class="max-w-full max-h-full"></canvas>
                </div>
            </div>
            
            <!-- Quick Overview Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-lg text-gray-900">Quick Overview</h3>
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Staff</span>
                            <span class="font-semibold text-gray-900">{{ number_format($totalEmployees) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Present Today</span>
                            <span class="font-semibold text-gray-900">{{ number_format($totalEmployees - $leaveToday) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">On Leave</span>
                            <span class="font-semibold text-gray-900">{{ number_format($leaveToday) }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="font-medium text-gray-900 mb-3">Upcoming Birthdays</h4>
                            <div class="max-h-40 overflow-y-auto space-y-3">
                                @forelse($upcomingBirthdays as $employee)
                                    <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg">
                                        <div class="w-8 h-8 bg-gradient-to-br from-pink-400 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $employee->birth_date?->format('F j') ?? 'Date unknown' }}</div>
                                        </div>
                                        <div class="text-xs text-pink-600 font-medium">
                                            🎂
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-gray-400 py-6">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4M8 7H3a1 1 0 00-1 1v2a1 1 0 001 1h5M8 7h8m0 0V6a2 2 0 012-2h1a2 2 0 012 2v1"></path>
                                        </svg>
                                        <div class="text-sm">No upcoming birthdays</div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    // Employees by Department Chart
    const deptCtx = document.getElementById('employeesByDeptChart').getContext('2d');
    const employeesByDeptData = @json($employeesByDept);
    
    // Check if we have data before creating the chart
    if (Object.keys(employeesByDeptData).length === 0) {
        // Show message if no data
        document.getElementById('employeesByDeptChart').parentElement.innerHTML = 
            '<div class="text-center py-8">' +
            '<p class="text-gray-500">No department data available</p>' +
            '</div>';
        return;
    }

    new Chart(deptCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(employeesByDeptData),
            datasets: [{
                label: 'Employees',
                data: Object.values(employeesByDeptData),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',  // Blue
                    'rgba(16, 185, 129, 0.8)',  // Green
                    'rgba(245, 158, 11, 0.8)',  // Yellow
                    'rgba(239, 68, 68, 0.8)',   // Red
                    'rgba(139, 92, 246, 0.8)',  // Purple
                    'rgba(6, 182, 212, 0.8)'    // Cyan
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} employees (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

</x-app-layout>