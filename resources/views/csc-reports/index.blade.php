<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('CSC Reports') }}
            </h2>
            <x-primary-button onclick="window.location.href='{{ route('csc-reports.create') }}'">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Generate New Report
            </x-primary-button>
        </div>
    </x-slot>

    <div class="space-y-6">
            
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Reports</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_reports'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Pending Submission</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['pending_submission'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Submitted This Month</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['submitted_this_month'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Acknowledged</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['acknowledged'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Types Overview -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Report Types Overview</h3>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-blue-700">Monthly Accession</h4>
                            <p class="text-sm text-gray-600 mt-1">New hires, appointments, transfers-in</p>
                            <p class="text-2xl font-bold mt-2">{{ $reportTypeCounts['accession'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-red-700">Monthly Separation</h4>
                            <p class="text-sm text-gray-600 mt-1">Resignations, retirements, terminations</p>
                            <p class="text-2xl font-bold mt-2">{{ $reportTypeCounts['separation'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-orange-700">Monthly DIBAR</h4>
                            <p class="text-sm text-gray-600 mt-1">Dropped from the Rolls</p>
                            <p class="text-2xl font-bold mt-2">{{ $reportTypeCounts['dibar'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-pink-700">Sexual Harassment</h4>
                            <p class="text-sm text-gray-600 mt-1">Case tracking and resolution</p>
                            <p class="text-2xl font-bold mt-2">{{ $reportTypeCounts['harassment'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-green-700">Annual IGHR</h4>
                            <p class="text-sm text-gray-600 mt-1">Inventory of Government HR</p>
                            <p class="text-2xl font-bold mt-2">{{ $reportTypeCounts['ighr'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Filters -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <form method="GET" action="{{ route('csc-reports.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-48">
                            <label for="report_type" class="block text-sm font-medium text-gray-700">Report Type</label>
                            <select name="report_type" id="report_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Types</option>
                                <option value="accession" {{ request('report_type') == 'accession' ? 'selected' : '' }}>Monthly Accession</option>
                                <option value="separation" {{ request('report_type') == 'separation' ? 'selected' : '' }}>Monthly Separation</option>
                                <option value="dibar" {{ request('report_type') == 'dibar' ? 'selected' : '' }}>Monthly DIBAR</option>
                                <option value="harassment" {{ request('report_type') == 'harassment' ? 'selected' : '' }}>Sexual Harassment</option>
                                <option value="ighr" {{ request('report_type') == 'ighr' ? 'selected' : '' }}>Annual IGHR</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-32">
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Statuses</option>
                                <option value="generating" {{ request('status') == 'generating' ? 'selected' : '' }}>Generating</option>
                                <option value="generated" {{ request('status') == 'generated' ? 'selected' : '' }}>Generated</option>
                                <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                                <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-32">
                            <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                            <select name="year" id="year" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Years</option>
                                @for($i = 2020; $i <= date('Y'); $i++)
                                    <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <x-secondary-button type="submit">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                </svg>
                                Filter
                            </x-secondary-button>
                        </div>
                    </form>
                </div>
            </div>

        <!-- Reports Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reports as $report)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <a href="{{ route('csc-reports.show', $report) }}" class="text-blue-600 hover:text-blue-900">
                                                {{ $report->report_number }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($report->report_type == 'accession') bg-blue-100 text-blue-800
                                                @elseif($report->report_type == 'separation') bg-red-100 text-red-800
                                                @elseif($report->report_type == 'dibar') bg-orange-100 text-orange-800
                                                @elseif($report->report_type == 'harassment') bg-pink-100 text-pink-800
                                                @elseif($report->report_type == 'ighr') bg-green-100 text-green-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($report->report_type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($report->report_month)
                                                {{ \Carbon\Carbon::create($report->report_year, $report->report_month, 1)->format('F Y') }}
                                            @else
                                                {{ $report->report_year }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($report->status == 'acknowledged') bg-purple-100 text-purple-800
                                                @elseif($report->status == 'submitted') bg-green-100 text-green-800
                                                @elseif($report->status == 'approved') bg-blue-100 text-blue-800
                                                @elseif($report->status == 'reviewed') bg-indigo-100 text-indigo-800
                                                @elseif($report->status == 'generated') bg-gray-100 text-gray-800
                                                @elseif($report->status == 'generating') bg-yellow-100 text-yellow-800
                                                @elseif($report->status == 'failed') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($report->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $report->generation_completed_at?->format('M d, Y H:i') ?? 'In progress' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="{{ route('csc-reports.show', $report) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View
                                                </a>
                                                
                                                @if($report->pdf_file_path)
                                                    <a href="{{ route('csc-reports.download.pdf', $report) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        PDF
                                                    </a>
                                                @endif
                                                
                                                @if($report->excel_file_path)
                                                    <a href="{{ route('csc-reports.download.excel', $report) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 7h16"></path>
                                                        </svg>
                                                        Excel
                                                    </a>
                                                @endif
                                                
                                                @if($report->isEditable())
                                                    <a href="{{ route('csc-reports.edit', $report) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No CSC reports found. <a href="{{ route('csc-reports.create') }}" class="text-blue-600 hover:text-blue-900">Generate your first report</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if(method_exists($reports, 'links'))
                        <div class="mt-6">
                            {{ $reports->links() }}
                        </div>
                    @endif
                </div>
            </div>
    </div>
</x-app-layout>