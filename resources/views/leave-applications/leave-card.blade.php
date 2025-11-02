@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <h1 class="text-2xl font-bold text-gray-900">Leave Card</h1>
            <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-3">
                <!-- Year Selector -->
                <select x-model="selectedYear" @change="window.location.href=`{{ route('leave-card.show') }}?year=${selectedYear}`"
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full sm:w-auto">
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>

                <!-- Print Button -->
                <button onclick="window.open('{{ route('leave-card.print-view', ['employeeId' => $employee->id, 'year' => $year]) }}', '_blank')"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Leave Card
                </button>
            </div>
        </div>
    </div>

    <!-- Employee Information Card -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500">Employee Name</p>
                <p class="text-lg font-semibold text-gray-900">{{ $employee->full_name }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Employee ID</p>
                <p class="text-lg font-semibold text-gray-900">{{ $employee->employee_number ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Position</p>
                <p class="text-lg font-semibold text-gray-900">{{ $employee->position ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Leave Credits Summary -->
    <div class="bg-white shadow rounded-lg p-4 sm:p-6 mb-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Leave Credits Summary - {{ $year }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 min-w-[280px] sm:min-w-[350px] md:min-w-[400px] lg:min-w-[500px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px] sm:min-w-[150px] md:min-w-[180px]">Leave Type</th>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[60px] sm:min-w-[70px] md:min-w-[80px]">Earned</th>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[60px] sm:min-w-[70px] md:min-w-[80px]">Used</th>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[60px] sm:min-w-[70px] md:min-w-[80px]">Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leaveCredits as $credit)
                        <tr>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 min-w-[120px] sm:min-w-[150px] md:min-w-[180px] truncate" title="{{ $credit->name }}">{{ $credit->name }}</td>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-xs sm:text-sm text-gray-900 min-w-[60px] sm:min-w-[70px] md:min-w-[80px] font-mono text-center">{{ number_format($credit->credits_earned, 3) }}</td>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-xs sm:text-sm text-gray-900 min-w-[60px] sm:min-w-[70px] md:min-w-[80px] font-mono text-center">{{ number_format($credit->credits_used, 3) }}</td>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-xs sm:text-sm min-w-[60px] sm:min-w-[70px] md:min-w-[80px] text-center">
                                <span class="{{ $credit->credits_balance < 5 ? 'text-red-600 font-semibold' : 'text-gray-900' }} font-mono">
                                    {{ number_format($credit->credits_balance, 3) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 sm:px-6 py-4 text-center text-sm text-gray-500">
                                No leave credits found for {{ $year }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Leave Applications History -->
    <div class="bg-white shadow rounded-lg p-4 sm:p-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Leave Applications History - {{ $year }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 min-w-[320px] sm:min-w-[450px] md:min-w-[550px] lg:min-w-[650px] xl:min-w-[700px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[80px] sm:min-w-[90px] md:min-w-[100px]">Date Filed</th>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[90px] sm:min-w-[110px] md:min-w-[120px] hidden sm:table-cell">Leave Type</th>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px] sm:min-w-[150px] md:min-w-[180px]">Period</th>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[60px] sm:min-w-[70px] md:min-w-[80px]">Duration</th>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[60px] sm:min-w-[70px] md:min-w-[80px] hidden md:table-cell">Status</th>
                        <th class="px-2 sm:px-3 lg:px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[50px] sm:min-w-[60px]"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leaveApplications as $application)
                        <tr>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-xs sm:text-sm text-gray-900 min-w-[80px] sm:min-w-[90px] md:min-w-[100px]">
                                <div class="sm:hidden">
                                    <div class="font-medium">{{ $application->created_at->format('m/d') }}</div>
                                    <div class="mt-1">
                                        <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded-full
                                            @if($application->status === 'approved') bg-green-100 text-green-700
                                            @elseif($application->status === 'rejected') bg-red-100 text-red-700
                                            @else bg-yellow-100 text-yellow-700
                                            @endif">
                                            {{ ucfirst($application->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="hidden sm:block">{{ $application->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-xs sm:text-sm text-gray-900 min-w-[90px] sm:min-w-[110px] md:min-w-[120px] truncate hidden sm:table-cell" title="{{ $application->leaveType->name }}">
                                {{ $application->leaveType->name }}
                            </td>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-xs sm:text-sm text-gray-900 min-w-[120px] sm:min-w-[150px] md:min-w-[180px]">
                                <div class="text-xs">
                                    <div class="sm:hidden">
                                        <div class="font-medium text-gray-700 truncate" title="{{ $application->leaveType->name }}">{{ $application->leaveType->name }}</div>
                                        <div class="text-gray-600">{{ $application->start_date->format('m/d') }}-{{ $application->end_date->format('m/d') }}</div>
                                    </div>
                                    <div class="hidden sm:block">
                                        <div>{{ $application->start_date->format('M d, Y') }}</div>
                                        <div>{{ $application->end_date->format('M d, Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-xs sm:text-sm text-gray-900 min-w-[60px] sm:min-w-[70px] md:min-w-[80px] font-mono text-center">
                                {{ number_format($application->days_requested, 1) }}
                                <span class="hidden sm:inline"> {{ $application->days_requested == 1 ? 'day' : 'days' }}</span>
                            </td>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap min-w-[60px] sm:min-w-[70px] md:min-w-[80px] text-center hidden md:table-cell">
                                <span class="inline-flex px-1.5 sm:px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap
                                    @if($application->status === 'approved') bg-green-100 text-green-800
                                    @elseif($application->status === 'rejected') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </td>
                            <td class="px-2 sm:px-3 lg:px-4 py-3 whitespace-nowrap text-xs sm:text-sm font-medium min-w-[50px] sm:min-w-[60px] text-center">
                                <a href="{{ route('leave-applications.show', $application) }}"
                                   class="inline-flex items-center justify-center px-2 sm:px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 whitespace-nowrap touch-target">
                                    <span class="hidden sm:inline">View</span>
                                    <span class="sm:hidden">V</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 sm:px-6 py-4 text-center text-sm text-gray-500">
                                No leave applications found for {{ $year }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Initialize Alpine.js reactive data
document.addEventListener('alpine:init', () => {
    Alpine.data('leaveCard', () => ({
        selectedYear: {{ $year }},
        refreshData() {
            window.location.href = `{{ route('leave-card.show') }}?year=${this.selectedYear}`;
        }
    }));
});
</script>
@endsection