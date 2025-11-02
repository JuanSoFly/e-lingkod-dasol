@extends('layouts.app')

@section('title', 'OPCR Archive - ' . config('app.name'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">OPCR Archive</h1>
                <p class="text-gray-600 mt-1">Historical performance commitments and reviews</p>
            </div>
            <div class="flex space-x-3">
                @auth
                    @can('opcr.export')
                        <a href="{{ route('opcr.archive.export', $filters) }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export to Excel
                        </a>
                    @endcan
                @endauth
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    @if(isset($stats))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Archived OPCRs</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_workflows']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Average Rating</p>
                    <p class="text-2xl font-bold text-gray-900">{{ round($stats['average_rating'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        @if(isset($stats['current_period']))
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Current Period</p>
                    <p class="text-lg font-bold text-gray-900">{{ $stats['current_period']->name }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-orange-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Top Performing Office</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ $stats['top_performing_offices']->first()->name ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Advanced Filters -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Filters</h3>
            <form method="GET" action="{{ route('opcr.archive.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Period Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Performance Period</label>
                        <select name="period_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Periods</option>
                            @foreach($periods as $period)
                                <option value="{{ $period->id }}" {{ $filters['period_id'] == $period->id ? 'selected' : '' }}>
                                    {{ $period->name }} ({{ $period->start_date->format('M Y') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Office Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Office</label>
                        <select name="office_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Offices</option>
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}" {{ $filters['office_id'] == $office->id ? 'selected' : '' }}>
                                    {{ $office->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Date To Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Department Head Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Head</label>
                        <select name="committed_by" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Department Heads</option>
                            @foreach($departmentHeads as $dh)
                                <option value="{{ $dh->user_id }}" {{ $filters['committed_by'] == $dh->user_id ? 'selected' : '' }}>
                                    {{ $dh->first_name }} {{ $dh->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rating Range Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rating Range</label>
                        <select name="rating_range" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Ratings</option>
                            <option value="4-5" {{ ($filters['rating_range'] ?? '') == '4-5' ? 'selected' : '' }}>Outstanding (4-5)</option>
                            <option value="3-4" {{ ($filters['rating_range'] ?? '') == '3-4' ? 'selected' : '' }}>Very Satisfactory (3-4)</option>
                            <option value="2-3" {{ ($filters['rating_range'] ?? '') == '2-3' ? 'selected' : '' }}>Satisfactory (2-3)</option>
                            <option value="1-2" {{ ($filters['rating_range'] ?? '') == '1-2' ? 'selected' : '' }}>Needs Improvement (1-2)</option>
                            <option value="0-1" {{ ($filters['rating_range'] ?? '') == '0-1' ? 'selected' : '' }}>Poor (0-1)</option>
                        </select>
                    </div>

                    <!-- Search Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                               placeholder="Search title, office, or name..."
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Sort Options -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <div class="flex space-x-2">
                            <select name="sort_by" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="updated_at" {{ ($filters['sort_by'] ?? 'updated_at') == 'updated_at' ? 'selected' : '' }}>Last Updated</option>
                                <option value="created_at" {{ ($filters['sort_by'] ?? '') == 'created_at' ? 'selected' : '' }}>Date Created</option>
                                <option value="title" {{ ($filters['sort_by'] ?? '') == 'title' ? 'selected' : '' }}>Title</option>
                            </select>
                            <select name="sort_order" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="desc" {{ ($filters['sort_order'] ?? 'desc') == 'desc' ? 'selected' : '' }}>Desc</option>
                                <option value="asc" {{ ($filters['sort_order'] ?? '') == 'asc' ? 'selected' : '' }}>Asc</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4 space-x-3">
                    <button type="button" onclick="clearFilters()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        Clear Filters
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($workflows->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Title & Office
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Period
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Department Head
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Final Rating
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($workflows as $workflow)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $workflow->title }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $workflow->office->name }}
                                        </div>
                                        @if($workflow->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mt-1">
                                                Deleted
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        {{ $workflow->period->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $workflow->period->start_date->format('M Y') }} - {{ $workflow->period->end_date->format('M Y') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($workflow->committedBy && $workflow->committedBy->employee)
                                        <div class="text-sm text-gray-900">
                                            {{ $workflow->committedBy->employee->first_name }} {{ $workflow->committedBy->employee->last_name }}
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500">Unknown</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $avgRating = $workflow->targets->flatMap(function($target) {
                                            return $target->ratings->pluck('final_rating');
                                        })->avg();
                                    @endphp
                                    @if($avgRating)
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-900">{{ round($avgRating, 2) }}</span>
                                            <div class="ml-2 flex items-center">
                                                @php
                                                    $color = $avgRating >= 4 ? 'green' : ($avgRating >= 3 ? 'blue' : ($avgRating >= 2 ? 'yellow' : 'red'));
                                                @endphp
                                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-{{ $color }}-500 h-2 rounded-full" style="width: {{ ($avgRating / 5) * 100 }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500">No rating</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Approved
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        {{ $workflow->updated_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $workflow->updated_at->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <a href="{{ route('opcr.workflows.show', $workflow) }}"
                                       class="text-blue-600 hover:text-blue-900 mr-3">
                                        View
                                    </a>
                                    @can('opcr.export')
                                        <a href="javascript:void(0)"
                                           class="text-green-600 hover:text-green-900"
                                           onclick="downloadOPCRPDF({{ $workflow->id }}, this)">
                                            PDF
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    {{ $workflows->links() }}
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{ $workflows->firstItem() }}</span>
                            to
                            <span class="font-medium">{{ $workflows->lastItem() }}</span>
                            of
                            <span class="font-medium">{{ $workflows->total() }}</span>
                            results
                        </p>
                    </div>
                    <div>
                        {{ $workflows->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No archived OPCRs found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if(request()->hasAny(['period_id', 'office_id', 'search']))
                        Try adjusting your filters to see more results.
                    @else
                        No OPCR workflows have been approved and archived yet.
                    @endif
                </p>
                <div class="mt-6">
                    <button type="button" onclick="clearFilters()"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Clear Filters
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function clearFilters() {
        window.location.href = '{{ route('opcr.archive.index') }}';
    }

    // PDF Download Handler with Fallback Mechanisms
    function downloadOPCRPDF(workflowId, linkElement) {
        // Show loading state
        const originalContent = linkElement.innerHTML;
        linkElement.innerHTML = '<svg class="animate-spin h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Downloading...';
        linkElement.classList.add('opacity-75', 'cursor-not-allowed');
        linkElement.style.pointerEvents = 'none';

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Try direct download first (best for streaming)
        tryDirectDownload(workflowId, linkElement, originalContent, csrfToken);
    }

    function tryDirectDownload(workflowId, linkElement, originalContent, csrfToken) {
        // Create a hidden iframe for direct download
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = `/opcr/export/pdf/${workflowId}?_token=${encodeURIComponent(csrfToken)}`;

        document.body.appendChild(iframe);

        // Set a timeout to check if download started
        const downloadTimeout = setTimeout(() => {
            // If iframe didn't trigger download, try fetch method
            document.body.removeChild(iframe);
            tryFetchDownload(workflowId, linkElement, originalContent, csrfToken);
        }, 3000);

        // Listen for iframe load (indicates download may have started)
        iframe.onload = () => {
            clearTimeout(downloadTimeout);

            // Check if iframe contains error content
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                if (iframeDoc && iframeDoc.body && iframeDoc.body.textContent.includes('error')) {
                    // Error detected, try fallback
                    document.body.removeChild(iframe);
                    tryFetchDownload(workflowId, linkElement, originalContent, csrfToken);
                } else {
                    // Success - show completion state
                    setTimeout(() => {
                        if (iframe.parentNode) {
                            document.body.removeChild(iframe);
                        }
                        showDownloadSuccess(linkElement, originalContent);
                    }, 1000);
                }
            } catch (e) {
                // Cross-origin restrictions, assume success
                setTimeout(() => {
                    if (iframe.parentNode) {
                        document.body.removeChild(iframe);
                    }
                    showDownloadSuccess(linkElement, originalContent);
                }, 1000);
            }
        };

        iframe.onerror = () => {
            clearTimeout(downloadTimeout);
            if (iframe.parentNode) {
                document.body.removeChild(iframe);
            }
            tryFetchDownload(workflowId, linkElement, originalContent, csrfToken);
        };
    }

    async function tryFetchDownload(workflowId, linkElement, originalContent, csrfToken) {
        try {
            const response = await fetch(`/opcr/export/pdf/${workflowId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/pdf',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            const blob = await extractPdfBlob(response);

            // Verify blob is PDF
            if (!blob || blob.size === 0) {
                const emptyError = new Error('Empty PDF file received');
                emptyError.userMessage = 'The server returned an empty PDF file. Please try again.';
                emptyError.details = { status: response.status };
                throw emptyError;
            }

            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;

            // Generate filename from current date and workflow info
            const date = new Date();
            const dateStr = date.toISOString().split('T')[0].replace(/-/g, '_');
            const filename = `OPCR_Export_${workflowId}_${dateStr}.pdf`;

            a.download = filename;
            document.body.appendChild(a);
            a.click();

            // Clean up
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            showDownloadSuccess(linkElement, originalContent);
        } catch (error) {
            console.error('PDF download failed:', error);
            showDownloadError(linkElement, originalContent, error);
        }
    }

    async function extractPdfBlob(response) {
        const contentType = response.headers.get('Content-Type') || '';

        if (!response.ok) {
            throw await buildResponseError(response, contentType);
        }

        if (!contentType.includes('application/pdf')) {
            throw await buildResponseError(response, contentType, true);
        }

        return response.blob();
    }

    async function buildResponseError(response, contentType, unexpectedType = false) {
        if (contentType.includes('application/json')) {
            let payload = {};
            try {
                payload = await response.json();
            } catch (jsonError) {
                payload = {};
            }

            const message = payload.message
                || (unexpectedType
                    ? 'The server returned JSON instead of a PDF document.'
                    : `Download failed (HTTP ${response.status}).`);

            const error = new Error(message);
            error.userMessage = message;
            error.details = {
                status: response.status,
                retryAfter: payload.retry_after ?? payload.retryAfter ?? response.headers.get('Retry-After')
            };

            return error;
        }

        let bodySnippet = '';
        try {
            bodySnippet = await response.text();
        } catch (readError) {
            bodySnippet = '';
        }

        const fallbackMessage = unexpectedType
            ? 'The server response was not a PDF file.'
            : `Download failed (HTTP ${response.status}).`;

        const fallbackError = new Error(fallbackMessage);
        fallbackError.userMessage = `${fallbackMessage} Please try again later.`;
        fallbackError.details = {
            status: response.status,
            contentType,
            snippet: bodySnippet ? bodySnippet.slice(0, 200) : ''
        };

        return fallbackError;
    }

    function showDownloadSuccess(linkElement, originalContent) {
        // Show success state
        linkElement.innerHTML = '<svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Downloaded!';
        linkElement.classList.remove('opacity-75', 'cursor-not-allowed');
        linkElement.classList.add('text-green-700');

        // Reset after 2 seconds
        setTimeout(() => {
            linkElement.innerHTML = originalContent;
            linkElement.classList.remove('text-green-700');
            linkElement.style.pointerEvents = 'auto';
        }, 2000);
    }

    function showDownloadError(linkElement, originalContent, error) {
        // Show error state
        linkElement.innerHTML = '<svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Error!';
        linkElement.classList.remove('opacity-75', 'cursor-not-allowed');
        linkElement.classList.add('text-red-600');

        // Reset after 3 seconds
        setTimeout(() => {
            linkElement.innerHTML = originalContent;
            linkElement.classList.remove('text-red-600');
            linkElement.style.pointerEvents = 'auto';
        }, 3000);

        // Show user-friendly error message
        let errorMessage;

        if (error?.userMessage) {
            errorMessage = error.userMessage;
            if (error.details?.retryAfter) {
                const seconds = parseInt(error.details.retryAfter, 10);
                if (!Number.isNaN(seconds) && seconds > 0) {
                    errorMessage += ` Please wait ${seconds} seconds before trying again.`;
                }
            }
        } else if (error?.message && error.message.includes('Playwright')) {
            errorMessage = 'Download blocked by browser extension. Please try again or temporarily disable the extension.';
        } else {
            errorMessage = 'Failed to download PDF. Please try again or contact support if the issue persists.';
        }

        // Create a toast notification instead of alert
        showToast(errorMessage, 'error');
    }

    function showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toast = document.createElement('div');
        const bgColor = type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-blue-500';

        toast.style.cssText = `
            ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg mb-4 transform transition-all duration-300 ease-in-out translate-x-full;
        `;

        toast.innerHTML = `
            <div class="flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'error'
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                    }
                </svg>
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }, 5000);
    }

    // Auto-submit filter form on change for better UX
    document.addEventListener('DOMContentLoaded', function() {
        const filterInputs = document.querySelectorAll('select[name="period_id"], select[name="office_id"], select[name="rating_range"]');

        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value !== '') {
                    this.form.submit();
                }
            });
        });
    });
</script>
@endpush
