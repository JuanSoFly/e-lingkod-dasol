@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="leaveCard()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Leave Card</h1>
            <div class="flex space-x-3">
                <!-- Employee Selector (HR/Admin only) -->
                @if(Auth::user()->can('employee.manage') || Auth::user()->can('leave.approve'))
                    <select x-model="selectedEmployee" @change="loadLeaveCard()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Employee</option>
                        @foreach($employees ?? [] as $emp)
                            <option value="{{ $emp->id }}" {{ isset($employee) && $employee->id == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_number ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                @endif

                <!-- Year Selector -->
                <select x-model="selectedYear" @change="loadLeaveCard()"
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>

                <!-- Initialize Balances Button (HR/Admin only) -->
                @if(Auth::user()->can('employee.manage') && isset($employee))
                    <button @click="initializeBalances()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Initialize Balances
                    </button>
                @endif

                <!-- Print Button -->
                @if(isset($employee))
                    <button onclick="window.open('{{ route('leave-card.print-view', ['employeeId' => $employee->id, 'year' => $year]) }}', '_blank')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print Leave Card
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if(isset($employee))
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
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Leave Credits Summary - {{ $year }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Vacation Leave</span>
                        <span class="text-lg font-semibold {{ $currentBalances['vl_balance'] < 5 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ number_format($currentBalances['vl_balance'], 2) }}
                        </span>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, ($currentBalances['vl_balance'] / 15) * 100) }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Sick Leave</span>
                        <span class="text-lg font-semibold {{ $currentBalances['sl_balance'] < 5 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ number_format($currentBalances['sl_balance'], 2) }}
                        </span>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ min(100, ($currentBalances['sl_balance'] / 15) * 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave History and Remarks -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Leave Applications History -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Leave Applications History</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date Filed</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Leave Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($leaveHistory['entries'] as $entry)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        {{ $entry['leave_type'] }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($entry['days'], 1) }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Recorded
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-center text-sm text-gray-500">
                                        No leave entries found for {{ $year }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Remarks Column -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Remarks Column</h2>
                <div class="bg-gray-50 rounded-lg p-4 h-96 overflow-y-auto">
                    @if(isset($leaveHistory['current_balances']['remarks']) && !empty($leaveHistory['current_balances']['remarks']))
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ $leaveHistory['current_balances']['remarks'] }}</pre>
                    @else
                        <p class="text-sm text-gray-500 italic">No remarks recorded for {{ $year }}</p>
                    @endif
                </div>
                @if(Auth::user()->can('employee.manage'))
                    <button @click="showManualEntryModal = true"
                            class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Manual Entry
                    </button>
                @endif
            </div>
        </div>
    @else
        <!-- Employee Selection Prompt -->
        <div class="bg-white shadow rounded-lg p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Select an Employee</h3>
            <p class="mt-1 text-sm text-gray-500">Please select an employee to view their leave card.</p>
        </div>
    @endif

    <!-- Manual Entry Modal (HR/Admin only) -->
    @if(Auth::user()->can('employee.manage'))
        <div x-show="showManualEntryModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" @click="showManualEntryModal = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="createManualEntry()">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Add Manual Entry</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Leave Type</label>
                                    <input type="text" x-model="manualEntry.leave_type_code" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date</label>
                                    <input type="date" x-model="manualEntry.date" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Days</label>
                                    <input type="number" step="0.1" x-model="manualEntry.days" required min="0.1"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Entry Type</label>
                                    <select x-model="manualEntry.entry_type" required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="credit">Credit</option>
                                        <option value="deduction">Deduction</option>
                                        <option value="adjustment">Adjustment</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                    <textarea x-model="manualEntry.remarks" required rows="3"
                                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Add Entry
                            </button>
                            <button type="button" @click="showManualEntryModal = false"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function leaveCard() {
    return {
        selectedEmployee: @isset($employee) ? '{{ $employee->id }}' : '',
        selectedYear: {{ $year }},
        showManualEntryModal: false,
        manualEntry: {
            leave_type_code: '',
            date: '',
            days: '',
            entry_type: 'adjustment',
            remarks: ''
        },

        loadLeaveCard() {
            const url = this.selectedEmployee
                ? `{{ route('leave-cards.show', ':employee:') }}`.replace(':employee:', this.selectedEmployee)
                : '{{ route('leave-cards.index') }}';

            window.location.href = `${url}?year=${this.selectedYear}`;
        },

        async initializeBalances() {
            if (!this.selectedEmployee) return;

            const message = 'Are you sure you want to initialize balances for this employee and year?';
            const confirmed = window.confirmDialog
                ? await window.confirmDialog({
                    title: 'Initialize Leave Balances',
                    message,
                    confirmLabel: 'Initialize',
                    cancelLabel: 'Cancel'
                })
                : window.confirm(message);

            if (confirmed) {
                fetch(`/leave-cards/${this.selectedEmployee}/initialize`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        year: this.selectedYear
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        alert(data.message);
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to initialize balances');
                });
            }
        },

        createManualEntry() {
            if (!this.selectedEmployee) return;

            const data = {
                employee_id: this.selectedEmployee,
                ...this.manualEntry
            };

            fetch('{{ route("leave-cards.manual-entry") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    alert(data.message);
                    this.showManualEntryModal = false;
                    location.reload();
                } else if (data.error) {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create manual entry');
            });
        }
    }
}
</script>
@endsection
