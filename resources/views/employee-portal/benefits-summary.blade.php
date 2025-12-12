@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Benefits Summary</h1>
                    <p class="text-lg text-gray-600 mt-1">Overview of your benefits, contributions, and leave balances</p>
                </div>
                <div>
                    <a href="{{ route('employee-portal.dashboard') }}" 
                       class="inline-flex items-center justify-center btn-responsive btn-touch border border-gray-300 rounded-md shadow-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Government Benefits Overview -->
        <div class="mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-shield-alt text-blue-600 mr-3"></i>
                        Government Benefits Enrollment
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
                        <div class="xl:col-span-3">
                            @if(count($benefitsSummary) > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @foreach($benefitsSummary as $benefitType => $summary)
                                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                                            <div class="flex items-center mb-4">
                                                <div class="flex-shrink-0 mr-4">
                                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center
                                                        @switch($benefitType)
                                                            @case('GSIS') bg-blue-100 @break
                                                            @case('PhilHealth') bg-red-100 @break
                                                            @case('Pag-IBIG') bg-green-100 @break
                                                            @case('SSS') bg-indigo-100 @break
                                                            @default bg-gray-100
                                                        @endswitch">
                                                        @switch($benefitType)
                                                            @case('GSIS')
                                                                <i class="fas fa-university text-blue-600 text-xl"></i>
                                                                @break
                                                            @case('PhilHealth')
                                                                <i class="fas fa-heart text-red-600 text-xl"></i>
                                                                @break
                                                            @case('Pag-IBIG')
                                                                <i class="fas fa-home text-green-600 text-xl"></i>
                                                                @break
                                                            @case('SSS')
                                                                <i class="fas fa-users text-indigo-600 text-xl"></i>
                                                                @break
                                                            @default
                                                                <i class="fas fa-certificate text-gray-600 text-xl"></i>
                                                        @endswitch
                                                    </div>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900">{{ $benefitType }}</h3>
                                                    <div class="flex items-center text-sm text-green-600">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        <span class="font-medium">Enrolled</span>
                                                    </div>
                                                </div>
                                            </div>
                                            @if(isset($summary['member_id']))
                                                <div class="text-sm text-gray-600 mb-2">
                                                    <span class="font-medium">Member ID:</span> {{ $summary['member_id'] }}
                                                </div>
                                            @endif
                                            @if(isset($summary['enrollment_date']))
                                                <div class="text-sm text-gray-600">
                                                    <span class="font-medium">Enrolled:</span> {{ \Carbon\Carbon::parse($summary['enrollment_date'])->format('M d, Y') }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-16">
                                    <div class="w-24 h-24 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <i class="fas fa-exclamation-triangle text-amber-600 text-3xl"></i>
                                    </div>
                                    <h3 class="text-xl font-medium text-gray-900 mb-2">No Benefits Enrollment</h3>
                                    <p class="text-gray-500">No government benefits enrollment found. Please contact HR to enroll in required benefits.</p>
                                </div>
                            @endif
                        </div>
                        <div class="xl:col-span-1">
                            <div class="bg-gray-50 rounded-lg p-4 h-full border border-gray-200">
                                <h6 class="font-semibold text-gray-900 mb-3">Enrollment Compliance</h6>
                                @if(isset($benefitsCompliance))
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-1 text-sm text-gray-600">
                                            <span>Compliance Rate</span>
                                            <span class="font-semibold text-gray-900">{{ $benefitsCompliance['compliance_rate'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div @class([
                                                'h-2 rounded-full transition-all duration-300',
                                                'bg-green-500' => $benefitsCompliance['compliance_rate'] >= 100,
                                                'bg-yellow-500' => $benefitsCompliance['compliance_rate'] >= 75 && $benefitsCompliance['compliance_rate'] < 100,
                                                'bg-red-500' => $benefitsCompliance['compliance_rate'] < 75,
                                            ]) style="width: {{ $benefitsCompliance['compliance_rate'] }}%"></div>
                                        </div>
                                    </div>
                                    @if(count($benefitsCompliance['enrolled_benefits']) > 0)
                                        <div class="mb-4">
                                            <p class="text-xs font-medium text-gray-500 mb-2">Enrolled Benefits:</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($benefitsCompliance['enrolled_benefits'] as $benefit)
                                                    <span class="badge bg-green-100 text-green-800">{{ $benefit }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if(count($benefitsCompliance['missing_benefits']) > 0)
                                        <div>
                                            <p class="text-xs font-medium text-red-600 mb-2">Missing Benefits:</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($benefitsCompliance['missing_benefits'] as $benefit)
                                                    <span class="badge bg-red-100 text-red-800">{{ $benefit }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contributions and Loans -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
        <!-- Current Year Contributions -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-calendar-alt text-green-600 mr-2"></i>{{ now()->year }} Contributions Summary
                    </h5>
                </div>
                <div class="p-6">
                    @if(count($contributionsSummary) > 0)
                        <div class="table-responsive scrollbar-thin scrollbar-stable">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="table-header-responsive text-left">Benefit Type</th>
                                        <th class="table-header-responsive text-right">Employee Share</th>
                                        <th class="table-header-responsive text-right">Employer Share</th>
                                        <th class="table-header-responsive text-right">Total Contributions</th>
                                        <th class="table-header-responsive text-right">Loan Payments</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($contributionsSummary as $benefitType => $totals)
                                        <tr>
                                            <td class="table-cell-responsive">
                                                <div class="flex items-center">
                                                    @switch($benefitType)
                                                        @case('GSIS')
                                                            <i class="fas fa-university text-blue-600 mr-2"></i>
                                                            @break
                                                        @case('PhilHealth')
                                                            <i class="fas fa-heart text-red-600 mr-2"></i>
                                                            @break
                                                        @case('Pag-IBIG')
                                                            <i class="fas fa-home text-green-600 mr-2"></i>
                                                            @break
                                                        @case('SSS')
                                                            <i class="fas fa-users text-indigo-600 mr-2"></i>
                                                            @break
                                                    @endswitch
                                                    <span class="font-semibold text-gray-900">{{ $benefitType }}</span>
                                                </div>
                                            </td>
                                            <td class="table-cell-responsive text-right">₱{{ number_format($totals['employee_total'], 2) }}</td>
                                            <td class="table-cell-responsive text-right">₱{{ number_format($totals['employer_total'], 2) }}</td>
                                            <td class="table-cell-responsive text-right font-semibold">₱{{ number_format($totals['grand_total'], 2) }}</td>
                                            <td class="table-cell-responsive text-right">
                                                @if($totals['loan_payments'] > 0)
                                                    <span class="text-indigo-600">₱{{ number_format($totals['loan_payments'], 2) }}</span>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <th class="table-header-responsive text-left">Total</th>
                                        <th class="table-header-responsive text-right">₱{{ number_format(collect($contributionsSummary)->sum('employee_total'), 2) }}</th>
                                        <th class="table-header-responsive text-right">₱{{ number_format(collect($contributionsSummary)->sum('employer_total'), 2) }}</th>
                                        <th class="table-header-responsive text-right">₱{{ number_format(collect($contributionsSummary)->sum('grand_total'), 2) }}</th>
                                        <th class="table-header-responsive text-right">₱{{ number_format(collect($contributionsSummary)->sum('loan_payments'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-chart-bar text-gray-400 text-2xl mb-3"></i>
                            <p class="text-gray-500">No contribution records found for {{ now()->year }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Active Loans -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-coins text-amber-500 mr-2"></i>Active Loans
                    </h5>
                </div>
                <div class="p-6">
                    @if(isset($activeLoansSummary) && $activeLoansSummary['total_active_loans'] > 0)
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center">
                                <h4 class="text-2xl font-bold text-blue-600">{{ $activeLoansSummary['total_active_loans'] }}</h4>
                                <p class="text-xs text-gray-500">Active Loans</p>
                            </div>
                            <div class="text-center">
                                <h4 class="text-2xl font-bold text-red-600">₱{{ number_format($activeLoansSummary['total_outstanding_balance'], 2) }}</h4>
                                <p class="text-xs text-gray-500">Outstanding</p>
                            </div>
                        </div>
                        <div class="mb-4 text-center">
                            <h5 class="text-lg font-semibold text-indigo-600">₱{{ number_format($activeLoansSummary['total_monthly_payments'], 2) }}</h5>
                            <p class="text-xs text-gray-500">Monthly Payment</p>
                        </div>
                        @if(count($activeLoansSummary['loans_by_type']) > 0)
                            <div class="border-t border-gray-200 pt-4">
                                <h6 class="text-sm font-semibold text-gray-900 mb-3">Loan Breakdown</h6>
                                @foreach($activeLoansSummary['loans_by_type'] as $loanType => $loanDetails)
                                    <div class="mb-3 last:mb-0">
                                        <div class="flex justify-between">
                                            <span class="text-sm font-semibold text-gray-900">{{ $loanType }}</span>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500 mt-0.5">
                                            <span>Outstanding: ₱{{ number_format($loanDetails['outstanding_balance'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500">
                                            <span>Monthly: ₱{{ number_format($loanDetails['monthly_payment'], 2) }}</span>
                                            @if($loanDetails['maturity_date'])
                                                <span>Due: {{ \Carbon\Carbon::parse($loanDetails['maturity_date'])->format('M Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-check-circle text-green-500 text-2xl mb-3"></i>
                            <p class="text-gray-500">No active loans</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Balances -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
        <div class="lg:col-span-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-calendar-check text-indigo-600 mr-2"></i>Leave Balances
                    </h5>
                </div>
                <div class="p-6">
                    @if(count($leaveBalances) > 0)
                        <div class="table-responsive scrollbar-thin scrollbar-stable">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="table-header-responsive text-left">Leave Type</th>
                                        <th class="table-header-responsive text-center">Earned</th>
                                        <th class="table-header-responsive text-center">Used</th>
                                        <th class="table-header-responsive text-center">Balance</th>
                                        <th class="table-header-responsive text-center">Year</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($leaveBalances as $balance)
                                        <tr>
                                            <td class="table-cell-responsive">
                                                <div class="flex items-center">
                                                    <i class="fas fa-calendar text-blue-600 mr-2"></i>
                                                    <span class="font-semibold text-gray-900">{{ $balance['leave_type'] }}</span>
                                                </div>
                                            </td>
                                            <td class="table-cell-responsive text-center">{{ $balance['earned'] }}</td>
                                            <td class="table-cell-responsive text-center">
                                                @if($balance['used'] > 0)
                                                    <span class="text-amber-600 font-semibold">{{ $balance['used'] }}</span>
                                                @else
                                                    <span class="text-gray-500">0</span>
                                                @endif
                                            </td>
                                            <td class="table-cell-responsive text-center">
                                                <span @class([
                                                    'font-semibold',
                                                    'text-green-600' => $balance['balance'] > 0,
                                                    'text-red-600' => $balance['balance'] <= 0,
                                                ])>{{ $balance['balance'] }}</span>
                                            </td>
                                            <td class="table-cell-responsive text-center">{{ $balance['year'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-calendar-times text-gray-400 text-2xl mb-3"></i>
                            <p class="text-gray-500">No leave balance records found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Leave Utilization -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 h-full overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-chart-pie text-blue-600 mr-2"></i>{{ now()->year }} Leave Usage
                    </h5>
                </div>
                <div class="p-6">
                    @if(count($leaveUtilization) > 0)
                        @foreach($leaveUtilization as $utilization)
                            <div class="mb-3 last:mb-0">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-semibold text-gray-900">{{ $utilization['leave_type'] }}</span>
                                    <span class="badge bg-blue-100 text-blue-800">{{ $utilization['total_days'] }} days</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>{{ $utilization['applications_count'] }} application(s)</span>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-2">
                                @endif
                            </div>
                        @endforeach
                        <div class="border-t border-gray-200 pt-4 mt-4 text-center">
                            <h5 class="text-xl font-bold text-blue-600">{{ collect($leaveUtilization)->sum('total_days') }}</h5>
                            <p class="text-xs text-gray-500">Total Days Used</p>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-calendar-plus text-green-500 text-2xl mb-3"></i>
                            <p class="text-gray-500">No leave taken this year</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Contributions Alert -->
    @if(isset($overdueContributions) && $overdueContributions['total_overdue_count'] > 0)
        <div class="mt-6">
            <div class="alert alert-warning border-0 shadow-sm flex items-start">
                <div class="shrink-0 mr-4">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h5 class="text-lg font-semibold text-yellow-900 mb-2">Overdue Contributions</h5>
                    <p class="text-sm text-yellow-900 mb-2">
                        You have <strong>{{ $overdueContributions['total_overdue_count'] }}</strong> overdue contribution(s) 
                        totaling <strong>₱{{ number_format($overdueContributions['total_overdue_amount'], 2) }}</strong>.
                    </p>
                    @if($overdueContributions['total_penalties'] > 0)
                        <p class="text-sm text-red-700 mb-2">
                            Accumulated penalties: <strong>₱{{ number_format($overdueContributions['total_penalties'], 2) }}</strong>
                        </p>
                    @endif
                    <p class="text-xs text-gray-600">
                        Please contact HR or Payroll department to resolve these overdue contributions.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
