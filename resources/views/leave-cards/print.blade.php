<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Card - {{ $employee->full_name }} ({{ $year }})</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-break { page-break-inside: avoid; }
        }
        @page {
            margin: 0.5in;
            size: portrait;
        }
    </style>
</head>
<body class="bg-white text-gray-900">
    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold">Leave Card</h1>
            <p class="text-lg text-gray-600 mt-2">{{ $year }}</p>
            <p class="text-sm text-gray-500 mt-1">Municipality of Dasol, Pangasinan</p>
        </div>

        <!-- Employee Information -->
        <div class="mb-8 print-break">
            <h2 class="text-xl font-semibold mb-4">Employee Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="font-medium">Name:</span> {{ $employee->full_name }}
                </div>
                <div>
                    <span class="font-medium">Employee ID:</span> {{ $employee->employee_number ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">Position:</span> {{ $employee->position ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">Department:</span> {{ $employee->department ?? 'N/A' }}
                </div>
            </div>
        </div>

        <!-- Leave Credits Summary -->
        <div class="mb-8 print-break">
            <h2 class="text-xl font-semibold mb-4">Leave Credits Summary</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="border-2 border-gray-300 rounded-lg p-4">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Vacation Leave Balance</p>
                        <p class="text-3xl font-bold {{ $currentBalances['vl_balance'] < 5 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ number_format($currentBalances['vl_balance'], 2) }}
                        </p>
                    </div>
                </div>
                <div class="border-2 border-gray-300 rounded-lg p-4">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Sick Leave Balance</p>
                        <p class="text-3xl font-bold {{ $currentBalances['sl_balance'] < 5 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ number_format($currentBalances['sl_balance'], 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave History -->
        <div class="mb-8 print-break">
            <h2 class="text-xl font-semibold mb-4">Leave History</h2>
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-4 py-2 text-left">Date</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Leave Type</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Days</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveHistory['entries'] as $entry)
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">{{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $entry['leave_type'] }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-center">{{ number_format($entry['days'], 1) }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $entry['remarks'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                                No leave entries found for {{ $year }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Remarks Column -->
        <div class="mb-12 print-break">
            <h2 class="text-xl font-semibold mb-4">Remarks Column</h2>
            <div class="border-2 border-gray-300 rounded-lg p-4 min-h-32">
                @if(isset($leaveHistory['current_balances']['remarks']) && !empty($leaveHistory['current_balances']['remarks']))
                    <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ $leaveHistory['current_balances']['remarks'] }}</pre>
                @else
                    <p class="text-sm text-gray-500 italic">No remarks recorded for {{ $year }}</p>
                @endif
            </div>
        </div>

        <!-- Summary and Certification -->
        <div class="mt-12">
            <table class="w-full border-collapse border border-gray-300 mb-8">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-4 py-2 text-left">Leave Type</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Earned</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Used</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-medium">Vacation Leave</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">15.00</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">{{ number_format(15 - $currentBalances['vl_balance'], 2) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center font-semibold">{{ number_format($currentBalances['vl_balance'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-medium">Sick Leave</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">15.00</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">{{ number_format(15 - $currentBalances['sl_balance'], 2) }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center font-semibold">{{ number_format($currentBalances['sl_balance'], 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Certification -->
            <div class="text-center">
                <p class="font-semibold mb-6">Certification</p>
                <p class="text-sm mb-8">Certified that the above leave credits and records are true and correct.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-8 border-t border-gray-300">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="mb-2">
                        <p class="text-sm">Prepared by:</p>
                        <p class="font-semibold">HR Officer</p>
                    </div>
                    <div class="mb-8 mt-6">
                        <p>_________________________</p>
                        <p class="text-sm mt-2">Signature & Date</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="mb-2">
                        <p class="text-sm">Verified by:</p>
                        <p class="font-semibold">Department Head</p>
                    </div>
                    <div class="mb-8 mt-6">
                        <p>_________________________</p>
                        <p class="text-sm mt-2">Signature & Date</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="mb-2">
                        <p class="text-sm">Approved by:</p>
                        <p class="font-semibold">Municipal Mayor</p>
                    </div>
                    <div class="mb-8 mt-6">
                        <p>_________________________</p>
                        <p class="text-sm mt-2">Signature & Date</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Button (hidden when printing) -->
        <div class="no-print mt-8 text-center">
            <button onclick="window.print()"
                    class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Print Document
            </button>
        </div>
    </div>
</body>
</html>