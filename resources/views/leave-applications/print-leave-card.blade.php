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
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-4 py-2 text-left">Leave Type</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Earned</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Used</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveCredits as $credit)
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">{{ $credit->name }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-center">{{ number_format($credit->credits_earned, 3) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-center">{{ number_format($credit->credits_used, 3) }}</td>
                            <td class="border border-gray-300 px-4 py-2 text-center {{ $credit->credits_balance < 5 ? 'text-red-600 font-semibold' : '' }}">
                                {{ number_format($credit->credits_balance, 3) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                                No leave credits found for {{ $year }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Leave Applications History -->
        <div class="print-break">
            <h2 class="text-xl font-semibold mb-4">Leave Applications History</h2>
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-4 py-2 text-left">Date Filed</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Leave Type</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Period</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Duration</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveApplications as $application)
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">{{ $application->created_at->format('M d, Y') }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $application->leaveType->name }}</td>
                            <td class="border border-gray-300 px-4 py-2">
                                {{ $application->start_date->format('M d, Y') }} -
                                {{ $application->end_date->format('M d, Y') }}
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-center">
                                {{ number_format($application->days_requested, 1) }} {{ $application->days_requested == 1 ? 'day' : 'days' }}
                            </td>
                            <td class="border border-gray-300 px-4 py-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if($application->status === 'approved') bg-green-100 text-green-800
                                    @elseif($application->status === 'rejected') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                                No leave applications found for {{ $year }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-8 border-t border-gray-300">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="mb-8">
                        <p>_________________________</p>
                        <p class="text-sm mt-2">Employee Signature</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="mb-8">
                        <p>_________________________</p>
                        <p class="text-sm mt-2">Supervisor/Manager</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="mb-8">
                        <p>_________________________</p>
                        <p class="text-sm mt-2">HR Officer</p>
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