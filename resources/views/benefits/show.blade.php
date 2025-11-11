<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-gray-500">Benefit Enrollment</p>
                <h2 class="text-2xl font-semibold text-gray-900">
                    {{ $benefit->employee->full_name ?? 'Unknown Employee' }} • {{ $benefit->benefit_type }}
                </h2>
                <p class="text-sm text-gray-500">Member No. {{ $benefit->member_number }}</p>
            </div>
            <div class="flex flex-wrap gap-3 md:justify-end">
                <a href="{{ route('benefits.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Back to Enrollments
                </a>
                <a href="{{ route('benefits.edit', $benefit) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                    Edit Enrollment
                </a>
                <form action="{{ route('benefits.destroy', $benefit) }}" method="POST" data-confirm="Are you sure you want to delete this enrollment? This action cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-100 rounded-lg text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $statusStyles = [
                    'active' => 'bg-green-100 text-green-800',
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'inactive' => 'bg-gray-100 text-gray-800',
                    'suspended' => 'bg-red-100 text-red-800',
                ];
                $statusClass = $statusStyles[$benefit->enrollment_status] ?? 'bg-gray-100 text-gray-800';
                $recentContributions = $benefit->benefitContributions->sortByDesc('payroll_date')->take(5);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <p class="text-sm text-gray-500">Benefit Type</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $benefit->benefit_type }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <p class="text-sm text-gray-500">Member Number</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $benefit->member_number }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <p class="text-sm text-gray-500">Enrollment Status</p>
                    <span class="inline-flex mt-1 px-3 py-1 text-sm font-medium rounded-full {{ $statusClass }}">
                        {{ ucfirst($benefit->enrollment_status) }}
                    </span>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <p class="text-sm text-gray-500">Enrollment Date</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">
                        {{ optional($benefit->enrollment_date)->format('M d, Y') ?? 'Not set' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white shadow-sm rounded-lg p-6 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Enrollment Details</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500">Employee</dt>
                            <dd class="text-base font-medium text-gray-900">
                                {{ $benefit->employee->full_name ?? 'N/A' }}<br>
                                <span class="text-sm text-gray-500">{{ $benefit->employee->office->name ?? '—' }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Coverage Type</dt>
                            <dd class="text-base font-medium text-gray-900">{{ $benefit->coverage_type ?? 'Not specified' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Coverage Amount</dt>
                            <dd class="text-base font-medium text-gray-900">
                                {{ $benefit->coverage_amount ? '₱' . number_format($benefit->coverage_amount, 2) : 'Not set' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Processing Status</dt>
                            <dd class="text-base font-medium text-gray-900">{{ $benefit->processing_status ? ucwords(str_replace('_', ' ', $benefit->processing_status)) : 'Not tracked' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Verified By</dt>
                            <dd class="text-base font-medium text-gray-900">
                                {{ $benefit->verifiedBy->full_name ?? 'Not verified' }}
                                @if($benefit->last_verified_date)
                                    <span class="block text-sm text-gray-500">{{ $benefit->last_verified_date->format('M d, Y') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Created / Updated</dt>
                            <dd class="text-base font-medium text-gray-900">
                                {{ $benefit->createdBy->full_name ?? 'System' }}
                                <span class="text-sm text-gray-500">(Created {{ $benefit->created_at?->format('M d, Y') }})</span>
                                @if($benefit->updated_at)
                                    <span class="block text-sm text-gray-500">Last updated {{ $benefit->updated_at->format('M d, Y h:i A') }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if($benefit->remarks)
                        <div class="rounded-md bg-indigo-50 p-4">
                            <p class="text-sm font-medium text-indigo-800">HR Remarks</p>
                            <p class="mt-1 text-sm text-indigo-700">{{ $benefit->remarks }}</p>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Contribution Summary</h3>
                        <dl class="mt-4 space-y-3">
                            <div>
                                <dt class="text-sm text-gray-500">Employee Rate</dt>
                                <dd class="text-base font-medium text-gray-900">{{ $benefit->employee_contribution_rate ? $benefit->employee_contribution_rate . '%' : 'Not set' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Employer Rate</dt>
                                <dd class="text-base font-medium text-gray-900">{{ $benefit->employer_contribution_rate ? $benefit->employer_contribution_rate . '%' : 'Not set' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Monthly Cap</dt>
                                <dd class="text-base font-medium text-gray-900">{{ $benefit->monthly_contribution_cap ? '₱' . number_format($benefit->monthly_contribution_cap, 2) : 'Not set' }}</dd>
                            </div>
                        </dl>
                    </div>

                    @if($benefit->has_active_loan)
                        <div class="bg-white shadow-sm rounded-lg p-6 border-l-4 border-amber-400">
                            <h3 class="text-lg font-semibold text-gray-900">Active Loan</h3>
                            <dl class="mt-4 space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-500">Outstanding Balance</dt>
                                    <dd class="text-base font-medium text-gray-900">{{ $benefit->loan_balance ? '₱' . number_format($benefit->loan_balance, 2) : 'Not available' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Monthly Payment</dt>
                                    <dd class="text-base font-medium text-gray-900">{{ $benefit->monthly_loan_payment ? '₱' . number_format($benefit->monthly_loan_payment, 2) : 'Not set' }}</dd>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <p>Loan Term: {{ optional($benefit->loan_start_date)->format('M Y') ?? '—' }} - {{ optional($benefit->loan_maturity_date)->format('M Y') ?? '—' }}</p>
                                    <p>Interest Rate: {{ $benefit->loan_interest_rate ? $benefit->loan_interest_rate . '%' : 'Not set' }}</p>
                                </div>
                            </dl>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Recent Contributions</h3>
                        <p class="text-sm text-gray-500">Past 5 payroll periods</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('benefits.index', ['employee' => $benefit->employee->last_name ?? null]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All History</a>
                    </div>
                </div>

                @if($recentContributions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">Payroll Date</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">Contribution</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">Payment Status</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-500">Remittance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($recentContributions as $contribution)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">
                                            {{ optional($contribution->payroll_date)->format('M d, Y') ?? '—' }}
                                            <span class="block text-xs text-gray-500">{{ $contribution->contribution_year }}-{{ str_pad($contribution->contribution_month, 2, '0', STR_PAD_LEFT) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-900">
                                            ₱{{ number_format($contribution->total_contribution_amount ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $contribution->payment_status === 'paid' ? 'bg-green-100 text-green-800' : ($contribution->payment_status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                                {{ $contribution->payment_status ? ucwords(str_replace('_', ' ', $contribution->payment_status)) : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-900">
                                            {{ $contribution->remittance_status ? ucwords(str_replace('_', ' ', $contribution->remittance_status)) : 'Not recorded' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No contribution records available for this enrollment yet.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
