<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $report->title }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $report->report_number }}</p>
            </div>
            <div class="flex space-x-2">
                @if($report->pdf_file_path)
                    <a href="{{ route('csc-reports.download.pdf', $report) }}" 
                       class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        Download PDF
                    </a>
                @endif
                @if($report->excel_file_path)
                    <a href="{{ route('csc-reports.download.excel', $report) }}" 
                       class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Download Excel
                    </a>
                @endif
                <a href="{{ route('csc-reports.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Reports
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Report Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Report Overview</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Report Type</label>
                            <p class="mt-1 text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $report->report_type) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reporting Period</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($report->report_month)
                                    {{ \Carbon\Carbon::create($report->report_year, $report->report_month, 1)->format('F Y') }}
                                @else
                                    {{ $report->report_year }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <p class="mt-1">
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
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Records</label>
                            <p class="mt-1 text-sm text-gray-900 font-semibold">{{ number_format($report->total_records ?? 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Statistics -->
            @if($report->summary_statistics)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Summary Statistics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- By Department -->
                        @if(isset($report->summary_statistics['by_department']))
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">By Department</h4>
                            <div class="space-y-2">
                                @foreach($report->summary_statistics['by_department'] as $dept => $count)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">{{ $dept }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- By Employment Status -->
                        @if(isset($report->summary_statistics['by_employment_status']))
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">By Employment Status</h4>
                            <div class="space-y-2">
                                @foreach($report->summary_statistics['by_employment_status'] as $status => $count)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 capitalize">{{ $status }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Report-specific statistics -->
                        @if($report->report_data)
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Report Metrics</h4>
                            <div class="space-y-2">
                                @foreach($report->report_data as $key => $value)
                                    @if(is_numeric($value))
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                        <span class="text-sm font-medium text-gray-900">{{ $value }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Workflow Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Workflow & Timeline</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Generation Information -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Generation Details</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Generated By</label>
                                    <p class="text-sm text-gray-900">{{ $report->generatedBy?->name ?? 'Unknown' }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Generation Started</label>
                                    <p class="text-sm text-gray-900">{{ $report->generation_started_at?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Generation Completed</label>
                                    <p class="text-sm text-gray-900">{{ $report->generation_completed_at?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                                </div>
                                @if($report->generation_duration_seconds)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Duration</label>
                                    <p class="text-sm text-gray-900">{{ $report->generation_duration_seconds }} seconds</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Approval Workflow -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Approval Workflow</h4>
                            <div class="space-y-3">
                                @if($report->reviewed_by)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Reviewed By</label>
                                    <p class="text-sm text-gray-900">{{ $report->reviewedBy?->name ?? 'Unknown' }}</p>
                                </div>
                                @endif
                                
                                @if($report->approved_by)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Approved By</label>
                                    <p class="text-sm text-gray-900">{{ $report->approvedBy?->name ?? 'Unknown' }}</p>
                                </div>
                                @endif
                                
                                @if($report->submitted_by)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Submitted By</label>
                                    <p class="text-sm text-gray-900">{{ $report->submittedBy?->name ?? 'Unknown' }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Submitted At</label>
                                    <p class="text-sm text-gray-900">{{ $report->submitted_at?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                                </div>
                                @endif
                                
                                @if($report->acknowledged_at)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Acknowledged At</label>
                                    <p class="text-sm text-gray-900">{{ $report->acknowledged_at?->format('M d, Y H:i:s') ?? 'N/A' }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submission Information -->
            @if($report->submitted_at)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Submission Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Submission Method</label>
                            <p class="text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $report->submission_method ?? 'N/A') }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Reference Number</label>
                            <p class="text-sm text-gray-900">{{ $report->submission_reference ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">File Format</label>
                            <p class="text-sm text-gray-900 uppercase">{{ $report->file_format ?? 'N/A' }}</p>
                        </div>
                    </div>
                    
                    @if($report->submission_notes)
                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-700">Submission Notes</label>
                        <p class="text-sm text-gray-900 mt-1">{{ $report->submission_notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- File Information -->
            @if($report->hasFiles())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Generated Files</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($report->pdf_file_path)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">PDF Report</h4>
                                    <p class="text-sm text-gray-600">{{ basename($report->pdf_file_path) }}</p>
                                </div>
                                <a href="{{ route('csc-reports.download.pdf', $report) }}" 
                                   class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Download
                                </a>
                            </div>
                        </div>
                        @endif
                        
                        @if($report->excel_file_path)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">Excel Report</h4>
                                    <p class="text-sm text-gray-600">{{ basename($report->excel_file_path) }}</p>
                                </div>
                                <a href="{{ route('csc-reports.download.excel', $report) }}" 
                                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Download
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    @if($report->file_size)
                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-700">Total File Size</label>
                        <p class="text-sm text-gray-900">{{ number_format($report->file_size / 1024 / 1024, 2) }} MB</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Description -->
            @if($report->description)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Description</h3>
                    <p class="text-sm text-gray-900">{{ $report->description }}</p>
                </div>
            </div>
            @endif

            <!-- Error Information -->
            @if($report->status === 'failed' && $report->error_message)
            <div class="bg-red-50 border border-red-200 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-red-800">Error Information</h3>
                    <p class="text-sm text-red-700">{{ $report->error_message }}</p>
                    
                    @if($report->generation_log)
                    <div class="mt-4">
                        <label class="block text-xs font-medium text-red-700">Generation Log</label>
                        <pre class="text-xs text-red-600 mt-2 bg-red-100 p-3 rounded overflow-x-auto">{{ $report->generation_log }}</pre>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Report created {{ $report->created_at->diffForHumans() }}
                            @if($report->updated_at != $report->created_at)
                                • Last updated {{ $report->updated_at->diffForHumans() }}
                            @endif
                        </div>
                        
                        <div class="flex space-x-2">
                            @if($report->canBeSubmitted())
                                <form method="POST" action="{{ route('csc-reports.submit', $report) }}" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                                            onclick="return confirm('Are you sure you want to submit this report to CSC?')">
                                        Submit to CSC
                                    </button>
                                </form>
                            @endif
                            
                            @if($report->isEditable())
                                <a href="{{ route('csc-reports.edit', $report) }}" 
                                   class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Edit Report
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>