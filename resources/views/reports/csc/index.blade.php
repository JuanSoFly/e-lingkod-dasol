<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('CSC Reports') }}
            </h2>
            <a href="{{ route('csc-reports.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Generate New Report
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total Reports</h3>
                    <p class="text-3xl font-bold mt-2 text-blue-600">{{ $stats['total_reports'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Pending Submission</h3>
                    <p class="text-3xl font-bold mt-2 text-yellow-600">{{ $stats['pending_submission'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Submitted This Month</h3>
                    <p class="text-3xl font-bold mt-2 text-green-600">{{ $stats['submitted_this_month'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Acknowledged</h3>
                    <p class="text-3xl font-bold mt-2 text-purple-600">{{ $stats['acknowledged'] ?? 0 }}</p>
                </div>
            </div>

            <!-- Report Types Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
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
                            <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <a href="{{ route('csc-reports.show', $report) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                            
                                            @if($report->pdf_file_path)
                                                <a href="{{ route('csc-reports.download.pdf', $report) }}" class="text-green-600 hover:text-green-900">PDF</a>
                                            @endif
                                            
                                            @if($report->excel_file_path)
                                                <a href="{{ route('csc-reports.download.excel', $report) }}" class="text-purple-600 hover:text-purple-900">Excel</a>
                                            @endif
                                            
                                            @if($report->isEditable())
                                                <a href="{{ route('csc-reports.edit', $report) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            @endif
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
    </div>
</x-app-layout>