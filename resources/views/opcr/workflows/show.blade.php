<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $workflow->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Header Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('opcr.workflows.index') }}" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </a>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ $workflow->title }}</h1>
                                <p class="text-sm text-gray-600">
                                    {{ $workflow->office->name }} • {{ $workflow->period->year }} - {{ $workflow->period->semester }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Status Badge -->
                            @php
                                $stateClass = match($workflow->workflow_state) {
                                    'planning_review' => 'bg-cyan-100 text-cyan-800',
                                    'pmt_review' => 'bg-teal-100 text-teal-800',
                                    'committed' => 'bg-blue-100 text-blue-800',
                                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                                    'evaluation' => 'bg-orange-100 text-orange-800',
                                    'final_approval' => 'bg-green-100 text-green-800',
                                    'returned' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $stateClass }}">
                                {{ ucwords(str_replace('_', ' ', $workflow->workflow_state)) }}
                            </span>

                            <!-- Action Buttons -->
                            @if($canEdit)
                                <a href="{{ route('opcr.workflows.edit', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </a>
                            @endif

                            @if($canEvaluate)
                                <a href="{{ route('opcr.workflows.evaluate', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Evaluate
                                </a>
                            @endif

                            @if($workflow->workflow_state === \App\Models\OPCRWorkflow::STATE_PLANNING_REVIEW && auth()->user()->can('opcr.planning_review'))
                                <form method="POST" action="{{ route('opcr.workflows.planning.review', $workflow) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-cyan-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Approve (Planning)
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('opcr.workflows.planning.review', $workflow) }}" class="inline ml-2">
                                    @csrf
                                    <input type="hidden" name="action" value="return">
                                    <input type="hidden" name="remarks" value="Returned by Planning for revision">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Return (Planning)
                                    </button>
                                </form>
                            @endif

                            @if($workflow->workflow_state === \App\Models\OPCRWorkflow::STATE_PMT_REVIEW && auth()->user()->can('opcr.pmt_review'))
                                <form method="POST" action="{{ route('opcr.workflows.pmt.review', $workflow) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Approve (PMT)
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('opcr.workflows.pmt.review', $workflow) }}" class="inline ml-2">
                                    @csrf
                                    <input type="hidden" name="action" value="return">
                                    <input type="hidden" name="remarks" value="Returned by PMT for revision">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Return (PMT)
                                    </button>
                                </form>
                            @endif

                            @if($canApprove)
                                <a href="{{ route('opcr.workflows.review', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.586-4L17 7l-6 6-4-4" />
                                    </svg>
                                    Review
                                </a>
                            @endif

                            @if(auth()->user()->can('ipcr.cascade') && $workflow->workflow_state === \App\Models\OPCRWorkflow::STATE_FINAL_APPROVAL)
                                <form method="POST" action="{{ route('opcr.workflows.cascade', $workflow) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150" onclick="return confirm('Queue IPCR cascading for this OPCR?')">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M4 20v-5h-.581m15.356-2a8.003 8.003 0 01-15.356 2" />
                                        </svg>
                                        Cascade IPCR
                                    </button>
                                </form>
                            @endif

                            @if(in_array($workflow->workflow_state, ['draft', 'returned']) && $canEdit)
                                <form method="POST" action="{{ route('opcr.workflows.submit', $workflow) }}" class="inline" data-confirm="Are you sure you want to submit this OPCR for evaluation?">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Submit
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($workflow->description)
                        <div class="mb-4">
                            <p class="text-gray-700">{{ $workflow->description }}</p>
                        </div>
                    @endif

                    <!-- Workflow Information -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900">Office</h4>
                            <p class="text-sm text-gray-600">{{ $workflow->office->full_path }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900">Committed By</h4>
                            <p class="text-sm text-gray-600">{{ $workflow->committedBy->employee->full_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $workflow->committed_at ? $workflow->committed_at->format('M d, Y h:i A') : 'Not committed' }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900">Performance Period</h4>
                            <p class="text-sm text-gray-600">{{ $workflow->period->year }} - {{ $workflow->period->semester }}</p>
                            <p class="text-xs text-gray-500">{{ $workflow->period->start_date->format('M d') }} - {{ $workflow->period->end_date->format('M d, Y') }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <h4 class="text-sm font-medium text-gray-900">Deadlines</h4>
                            <p class="text-xs text-gray-600">Planning: {{ $workflow->period->planning_deadline ? $workflow->period->planning_deadline->format('M d, Y') : 'Unset' }}</p>
                            <p class="text-xs text-gray-600">PMT: {{ $workflow->period->pmt_deadline ? $workflow->period->pmt_deadline->format('M d, Y') : 'Unset' }}</p>
                            <p class="text-xs text-gray-600">LCE: {{ $workflow->period->lce_deadline ? $workflow->period->lce_deadline->format('M d, Y') : 'Unset' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900">Planning Reviewer</h4>
                            <p class="text-sm text-gray-700">{{ $workflow->planningReviewer?->employee?->full_name ?? 'Pending assignment' }}</p>
                            <p class="text-xs text-gray-500">{{ $workflow->planning_reviewed_at ? $workflow->planning_reviewed_at->format('M d, Y h:i A') : 'Awaiting review' }}</p>
                            @if($workflow->planning_remarks)
                                <p class="text-xs text-gray-600 mt-1">Remarks: {{ $workflow->planning_remarks }}</p>
                            @endif
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900">PMT Recommendation</h4>
                            <p class="text-sm text-gray-700">{{ $workflow->pmtRecommender?->employee?->full_name ?? 'Pending assignment' }}</p>
                            <p class="text-xs text-gray-500">{{ $workflow->pmt_recommended_at ? $workflow->pmt_recommended_at->format('M d, Y h:i A') : 'Awaiting PMT action' }}</p>
                            @if($workflow->pmt_remarks)
                                <p class="text-xs text-gray-600 mt-1">Remarks: {{ $workflow->pmt_remarks }}</p>
                            @endif
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900">HRMO Consistency</h4>
                            @php
                                $ipcrAvg = $workflow->ipcrs()->whereNotNull('overall_score')->avg('overall_score');
                            @endphp
                            <p class="text-sm text-gray-700">IPCR Avg: {{ $ipcrAvg ? number_format($ipcrAvg, 2) : 'N/A' }}</p>
                            <p class="text-xs text-gray-500">OPCR Rating: {{ $workflow->overall_rating ? number_format($workflow->overall_rating, 2) : 'N/A' }}</p>
                            @if($workflow->hrmo_override)
                                <p class="text-xs text-red-600 mt-1">Override used: {{ $workflow->hrmo_override_reason ?? '—' }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Targets -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Performance Targets</h3>
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <span>{{ $workflow->targets->count() }} targets</span>
                            @if($workflow->targets->whereNotNull('average_rating')->count() > 0)
                                <span>•</span>
                                <span>{{ $workflow->targets->whereNotNull('average_rating')->count() }} rated</span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-6">
                        @foreach($workflow->targets as $index => $target)
                            @php
                                $resolvedAccomplishedQuality = $target->accomplished_quality ?? $target->successIndicator->accomplished_quality;
                                $resolvedAccomplishedEfficiency = $target->accomplished_efficiency ?? $target->successIndicator->accomplished_efficiency;
                                $resolvedAccomplishedTimeliness = $target->accomplished_timeliness ?? $target->successIndicator->accomplished_timeliness;
                                $resolvedPerformancePercentage = $target->performance_percentage ?? $target->successIndicator->performance_percentage;
                                $resolvedTargetMet = $target->is_target_met ?? $target->successIndicator->is_target_met;
                            @endphp
                            <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                                <!-- Target Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-medium text-gray-900">Target {{ $index + 1 }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            MFO: {{ $target->mfo->full_code_path }} - {{ $target->mfo->title }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Success Indicator: {{ $target->successIndicator->code }} - {{ $target->successIndicator->title }}
                                        </p>
                                    </div>
                                    @if($target->average_rating)
                                        <div class="ml-4 text-right">
                                            <div class="text-2xl font-bold {{ $target->successIndicator->rating_color }} p-2 rounded-lg">
                                                {{ number_format($target->average_rating, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1">{{ $target->successIndicator->adjectival_rating }}</div>
                                        </div>
                                    @endif
                                </div>

                                <!-- QET Details -->
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <!-- Quality -->
                                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                                        <h5 class="text-sm font-medium text-gray-900 mb-3">Quality</h5>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Target:</span>
                                                <span class="text-sm font-medium">{{ $target->target_quality ?? 'N/A' }}</span>
                                            </div>
                                            @if(!is_null($resolvedAccomplishedQuality))
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Accomplished:</span>
                                                    <span class="text-sm font-medium">{{ $resolvedAccomplishedQuality }}</span>
                                                </div>
                                                @if(!is_null($resolvedPerformancePercentage))
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600">Performance:</span>
                                                        <span class="text-sm font-medium {{ $resolvedTargetMet ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $resolvedPerformancePercentage }}%
                                                        </span>
                                                    </div>
                                                @endif
                                            @endif
                                            @if($target->rating_quality !== null)
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Rating:</span>
                                                    <span class="text-sm font-medium">{{ $target->rating_quality }}/5</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Efficiency -->
                                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                                        <h5 class="text-sm font-medium text-gray-900 mb-3">Efficiency</h5>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Target:</span>
                                                <span class="text-sm font-medium">{{ $target->target_efficiency ?? 'N/A' }}</span>
                                            </div>
                                            @if($resolvedAccomplishedEfficiency)
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Accomplished:</span>
                                                    <span class="text-sm font-medium">{{ $resolvedAccomplishedEfficiency }}</span>
                                                </div>
                                            @endif
                                            @if($target->rating_efficiency !== null)
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Rating:</span>
                                                    <span class="text-sm font-medium">{{ $target->rating_efficiency }}/5</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Timeliness -->
                                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                                        <h5 class="text-sm font-medium text-gray-900 mb-3">Timeliness</h5>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Target:</span>
                                                <span class="text-sm font-medium">{{ $target->target_timeliness ?? 'N/A' }}</span>
                                            </div>
                                            @if($resolvedAccomplishedTimeliness)
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Accomplished:</span>
                                                    <span class="text-sm font-medium">{{ $resolvedAccomplishedTimeliness }}</span>
                                                </div>
                                            @endif
                                            @if($target->rating_timeliness !== null)
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Rating:</span>
                                                    <span class="text-sm font-medium">{{ $target->rating_timeliness }}/5</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks and Evidence -->
                                @if($target->remarks || $target->successIndicator->evidence_documents)
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        @if($target->remarks)
                                            <div class="mb-4">
                                                <h5 class="text-sm font-medium text-gray-900 mb-2">Remarks</h5>
                                                <p class="text-sm text-gray-700">{{ $target->remarks }}</p>
                                            </div>
                                        @endif

                                        @if($target->successIndicator->evidence_documents && count($target->successIndicator->evidence_documents) > 0)
                                            <div>
                                                <h5 class="text-sm font-medium text-gray-900 mb-2">Evidence Documents</h5>
                                                <div class="space-y-2">
                                                    @foreach($target->successIndicator->evidence_documents as $document)
                                                        <div class="flex items-center justify-between p-2 bg-white rounded border border-gray-200">
                                                            <div class="flex items-center">
                                                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                                <span class="text-sm text-gray-700">{{ $document['title'] ?? 'Document ' . $loop->index }}</span>
                                                            </div>
                                                            <a href="{{ $document['download_url'] ?? '#' }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                                                Download
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Workflow History -->
            @if(!empty($stateHistory))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Workflow History</h3>
                        <div class="space-y-4">
                            @foreach($stateHistory as $history)
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-{{ $history['color'] ?? 'gray' }}-400 rounded-full mt-2"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900">
                                            <span class="font-medium">{{ $history['user'] }}</span>
                                            changed status to
                                            <span class="font-medium">{{ $history['state'] }}</span>
                                        </p>
                                        <p class="text-sm text-gray-500">{{ $history['date'] }}</p>
                                        @if($history['comments'])
                                            <p class="text-sm text-gray-700 mt-1">{{ $history['comments'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
