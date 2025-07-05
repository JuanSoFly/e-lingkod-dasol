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
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
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
                        <div class="col-md-4">
                            <div class="bg-light rounded p-3 h-100">
                                <h6 class="fw-bold mb-3">Enrollment Compliance</h6>
                                @if(isset($benefitsCompliance))
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Compliance Rate</span>
                                            <span class="fw-bold">{{ $benefitsCompliance['compliance_rate'] }}%</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $benefitsCompliance['compliance_rate'] >= 100 ? 'success' : ($benefitsCompliance['compliance_rate'] >= 75 ? 'warning' : 'danger') }}" 
                                                 role="progressbar" style="width: {{ $benefitsCompliance['compliance_rate'] }}%"></div>
                                        </div>
                                    </div>
                                    @if(count($benefitsCompliance['enrolled_benefits']) > 0)
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Enrolled Benefits:</small>
                                            @foreach($benefitsCompliance['enrolled_benefits'] as $benefit)
                                                <span class="badge bg-success me-1 mb-1">{{ $benefit }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if(count($benefitsCompliance['missing_benefits']) > 0)
                                        <div class="mb-3">
                                            <small class="text-danger d-block mb-1">Missing Benefits:</small>
                                            @foreach($benefitsCompliance['missing_benefits'] as $benefit)
                                                <span class="badge bg-danger me-1 mb-1">{{ $benefit }}</span>
                                            @endforeach
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
    <div class="row mb-4">
        <!-- Current Year Contributions -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt text-success me-2"></i>{{ now()->year }} Contributions Summary
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($contributionsSummary) > 0)
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead class="table-light">
                                    <tr>
                                        <th>Benefit Type</th>
                                        <th class="text-end">Employee Share</th>
                                        <th class="text-end">Employer Share</th>
                                        <th class="text-end">Total Contributions</th>
                                        <th class="text-end">Loan Payments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contributionsSummary as $benefitType => $totals)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @switch($benefitType)
                                                        @case('GSIS')
                                                            <i class="fas fa-university text-primary me-2"></i>
                                                            @break
                                                        @case('PhilHealth')
                                                            <i class="fas fa-heart text-danger me-2"></i>
                                                            @break
                                                        @case('Pag-IBIG')
                                                            <i class="fas fa-home text-success me-2"></i>
                                                            @break
                                                        @case('SSS')
                                                            <i class="fas fa-users text-info me-2"></i>
                                                            @break
                                                    @endswitch
                                                    <strong>{{ $benefitType }}</strong>
                                                </div>
                                            </td>
                                            <td class="text-end">₱{{ number_format($totals['employee_total'], 2) }}</td>
                                            <td class="text-end">₱{{ number_format($totals['employer_total'], 2) }}</td>
                                            <td class="text-end"><strong>₱{{ number_format($totals['grand_total'], 2) }}</strong></td>
                                            <td class="text-end">
                                                @if($totals['loan_payments'] > 0)
                                                    <span class="text-info">₱{{ number_format($totals['loan_payments'], 2) }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">₱{{ number_format(collect($contributionsSummary)->sum('employee_total'), 2) }}</th>
                                        <th class="text-end">₱{{ number_format(collect($contributionsSummary)->sum('employer_total'), 2) }}</th>
                                        <th class="text-end">₱{{ number_format(collect($contributionsSummary)->sum('grand_total'), 2) }}</th>
                                        <th class="text-end">₱{{ number_format(collect($contributionsSummary)->sum('loan_payments'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar text-muted fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No contribution records found for {{ now()->year }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Active Loans -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-coins text-warning me-2"></i>Active Loans
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($activeLoansSummary) && $activeLoansSummary['total_active_loans'] > 0)
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center">
                                        <h4 class="text-primary mb-0">{{ $activeLoansSummary['total_active_loans'] }}</h4>
                                        <small class="text-muted">Active Loans</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <h4 class="text-danger mb-0">₱{{ number_format($activeLoansSummary['total_outstanding_balance'], 2) }}</h4>
                                        <small class="text-muted">Outstanding</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-center">
                                <h5 class="text-info mb-0">₱{{ number_format($activeLoansSummary['total_monthly_payments'], 2) }}</h5>
                                <small class="text-muted">Monthly Payment</small>
                            </div>
                        </div>
                        @if(count($activeLoansSummary['loans_by_type']) > 0)
                            <div class="border-top pt-3">
                                <h6 class="fw-bold mb-3">Loan Breakdown</h6>
                                @foreach($activeLoansSummary['loans_by_type'] as $loanType => $loanDetails)
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">{{ $loanType }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>Outstanding: ₱{{ number_format($loanDetails['outstanding_balance'], 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small">
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
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No active loans</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Balances -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-check text-info me-2"></i>Leave Balances
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($leaveBalances) > 0)
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead class="table-light">
                                    <tr>
                                        <th>Leave Type</th>
                                        <th class="text-center">Earned</th>
                                        <th class="text-center">Used</th>
                                        <th class="text-center">Balance</th>
                                        <th class="text-center">Year</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leaveBalances as $balance)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-calendar text-primary me-2"></i>
                                                    <strong>{{ $balance['leave_type'] }}</strong>
                                                </div>
                                            </td>
                                            <td class="text-center">{{ $balance['earned'] }}</td>
                                            <td class="text-center">
                                                @if($balance['used'] > 0)
                                                    <span class="text-warning">{{ $balance['used'] }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <strong class="text-{{ $balance['balance'] > 0 ? 'success' : 'danger' }}">
                                                    {{ $balance['balance'] }}
                                                </strong>
                                            </td>
                                            <td class="text-center">{{ $balance['year'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times text-muted fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No leave balance records found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Leave Utilization -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie text-primary me-2"></i>{{ now()->year }} Leave Usage
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($leaveUtilization) > 0)
                        @foreach($leaveUtilization as $utilization)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">{{ $utilization['leave_type'] }}</span>
                                    <span class="badge bg-primary">{{ $utilization['total_days'] }} days</span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>{{ $utilization['applications_count'] }} application(s)</span>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-2">
                                @endif
                            </div>
                        @endforeach
                        <div class="border-top pt-3 mt-3">
                            <div class="text-center">
                                <h5 class="text-primary mb-0">{{ collect($leaveUtilization)->sum('total_days') }}</h5>
                                <small class="text-muted">Total Days Used</small>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-plus text-success fa-2x mb-3"></i>
                            <p class="text-muted mb-0">No leave taken this year</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Contributions Alert -->
    @if(isset($overdueContributions) && $overdueContributions['total_overdue_count'] > 0)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">Overdue Contributions</h5>
                            <p class="mb-2">
                                You have <strong>{{ $overdueContributions['total_overdue_count'] }}</strong> overdue contribution(s) 
                                totaling <strong>₱{{ number_format($overdueContributions['total_overdue_amount'], 2) }}</strong>.
                            </p>
                            @if($overdueContributions['total_penalties'] > 0)
                                <p class="mb-2 text-danger">
                                    Accumulated penalties: <strong>₱{{ number_format($overdueContributions['total_penalties'], 2) }}</strong>
                                </p>
                            @endif
                            <p class="mb-0">
                                <small class="text-muted">Please contact HR or Payroll department to resolve these overdue contributions.</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
}

.progress {
    background-color: #e9ecef;
}

.border {
    border-color: #dee2e6 !important;
}

.alert {
    border-radius: 0.375rem;
}

.text-primary { color: #0d6efd !important; }
.text-success { color: #198754 !important; }
.text-info { color: #0dcaf0 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }
</style>
@endpush