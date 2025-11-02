<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Final Review: {{ $workflow->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Final Review and Approval</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $workflow->office->name }} • {{ $workflow->period->year }} - {{ $workflow->period->semester }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('opcr.workflows.show', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Back to Details
                            </a>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                Final Approval Required
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Overall Performance Summary -->
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Overall Performance Summary</h3>

                        @if($workflow->targets->isEmpty())
                            <!-- Empty Workflow State -->
                            <div class="text-center py-8">
                                <div class="text-yellow-600 mb-4">
                                    <svg class="mx-auto h-12 w-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">No Performance Targets Created</h4>
                                <p class="text-sm text-gray-600 mb-4">This OPCR workflow does not have any performance targets assigned yet.</p>

                                @if($workflow->overall_rating)
                                    <div class="bg-white rounded-lg border border-gray-200 p-4 max-w-md mx-auto">
                                        <h5 class="text-sm font-medium text-gray-900 mb-2">Workflow-Level Rating</h5>
                                        <div class="text-2xl font-bold text-purple-600">{{ number_format($workflow->overall_rating, 2) }}</div>
                                        <div class="text-sm text-gray-600">{{ $workflow->overall_adjectival_rating }}</div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Normal Performance Summary Display -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-purple-600">
                                        {{ $performanceSummary['average_rating'] > 0 ? number_format($performanceSummary['average_rating'], 2) : '--' }}
                                    </div>
                                    <div class="text-sm text-gray-600">Overall Rating</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-medium text-purple-600">
                                        {{ $performanceSummary['total_targets'] }}
                                    </div>
                                    <div class="text-sm text-gray-600">Total Targets</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-medium text-green-600">
                                        {{ $performanceSummary['total_accomplished'] }}
                                    </div>
                                    <div class="text-sm text-gray-600">Evaluated Targets</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-medium text-indigo-600">
                                        {{ $calculateAdjectivalRating($performanceSummary['average_rating']) }}
                                    </div>
                                    <div class="text-sm text-gray-600">Adjectival Rating</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Performance Rating Distribution -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Rating Distribution</h3>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            @php
                                if($workflow->targets->isEmpty()) {
                                    $distribution = [];
                                } else {
                                    $distribution = $performanceSummary['targets_by_rating'];
                                }
                            @endphp

                            @foreach(['Outstanding', 'Very Satisfactory', 'Satisfactory', 'Unsatisfactory', 'Poor'] as $rating)
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold
                                        @if($rating === 'Outstanding') text-green-600
                                        @elseif($rating === 'Very Satisfactory') text-blue-600
                                        @elseif($rating === 'Satisfactory') text-yellow-600
                                        @elseif($rating === 'Unsatisfactory') text-orange-600
                                        @else text-red-600
                                        @endif">
                                        {{ $distribution[$rating] ?? 0 }}
                                    </div>
                                    <div class="text-xs text-gray-600">{{ $rating }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Detailed Target Results -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Detailed Performance Results</h3>

                        @if($workflow->targets->isEmpty())
                            <div class="text-center py-8">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">No Performance Targets Available</h4>
                                <p class="text-sm text-gray-600">This workflow does not have any performance targets to display.</p>
                            </div>
                        @else
                            @foreach($workflow->targets as $index => $target)
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
                                    <div class="ml-4 text-right">
                                        @php
                                            $rating = $target->ratings?->first();
                                            $ratingValue = $rating?->final_rating ?? '--';
                                            $ratingCategory = $rating ? $calculateAdjectivalRating($rating->final_rating) : 'Not Rated';
                                        @endphp
                                        <div class="text-2xl font-bold
                                            @if($rating && $rating->final_rating >= 4.5) text-green-600
                                            @elseif($rating && $rating->final_rating >= 3.5) text-blue-600
                                            @elseif($rating && $rating->final_rating >= 2.5) text-yellow-600
                                            @elseif($rating && $rating->final_rating >= 1.5) text-orange-600
                                            @elseif($rating) text-red-600
                                            else text-gray-400
                                            @endif p-2 rounded-lg">
                                            {{ is_numeric($ratingValue) ? number_format($ratingValue, 2) : $ratingValue }}
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">{{ $ratingCategory }}</div>
                                    </div>
                                </div>

                                <!-- Performance Details -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Target vs Accomplishment -->
                                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                                        <h5 class="text-sm font-medium text-gray-900 mb-3">Performance Details</h5>
                                        <div class="space-y-3">
                                            <div>
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-sm text-gray-600">Quantity Achievement</span>
                                                    <span class="text-sm font-medium">{{ $target->performance_percentage ?? 0 }}%</span>
                                                </div>
                                                @if($target->performance_percentage)
                                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                                        <div class="bg-{{ $target->is_target_met ? 'green' : 'red' }}-600 h-2 rounded-full" style="width: {{ min($target->performance_percentage, 100) }}%"></div>
                                                    </div>
                                                @endif
                                            </div>

                                            @if($target->target_efficiency && $target->accomplished_efficiency)
                                                <div>
                                                    <span class="text-sm text-gray-600">Efficiency:</span>
                                                    <span class="text-sm font-medium ml-2">{{ $target->target_efficiency }} → {{ $target->accomplished_efficiency }}</span>
                                                </div>
                                            @endif

                                            @if($target->target_timeliness && $target->accomplished_timeliness)
                                                <div>
                                                    <span class="text-sm text-gray-600">Timeliness:</span>
                                                    <span class="text-sm font-medium ml-2">{{ $target->target_timeliness }} → {{ $target->accomplished_timeliness }}</span>
                                                </div>
                                            @endif

                                            @if(!$target->target_efficiency && !$target->target_timeliness && !$target->performance_percentage)
                                                <div class="text-center py-4">
                                                    <p class="text-sm text-gray-500">No performance details available</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- QET Scores -->
                                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                                        <h5 class="text-sm font-medium text-gray-900 mb-3">QET Scores</h5>
                                        <div class="space-y-2">
                                            @php
                                                $qetRating = $target->ratings?->first();
                                            @endphp
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Quantity:</span>
                                                <span class="text-sm font-medium">{{ $qetRating?->rating_quantity ?? 'Not Rated' }}/5</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Efficiency:</span>
                                                <span class="text-sm font-medium">{{ $qetRating?->rating_efficiency ?? 'Not Rated' }}/5</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Timeliness:</span>
                                                <span class="text-sm font-medium">{{ $qetRating?->rating_timeliness ?? 'Not Rated' }}/5</span>
                                            </div>
                                            <div class="pt-2 mt-2 border-t border-gray-200">
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-900">Average:</span>
                                                    <span class="text-sm font-bold">{{ is_numeric($qetRating?->final_rating) ? number_format($qetRating->final_rating, 2) : '--' }}/5</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks -->
                                @if($target->remarks)
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <h5 class="text-sm font-medium text-gray-900 mb-2">Assessor Remarks</h5>
                                        <p class="text-sm text-gray-700 bg-white p-3 rounded border border-gray-200">{{ $target->remarks }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        @endif
                    </div>

                    <!-- Assessor's Evaluation Summary -->
                    @if($workflow->assessor_remarks || $workflow->assessor_recommendation)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-blue-900 mb-4">Assessor's Evaluation Summary</h3>
                            @if($workflow->assessor_recommendation)
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-blue-900">Recommendation:</span>
                                    <span class="text-sm text-blue-800 ml-2">{{ ucfirst($workflow->assessor_recommendation) }}</span>
                                </div>
                            @endif
                            @if($workflow->assessor_remarks)
                                <div>
                                    <span class="text-sm font-medium text-blue-900">Overall Remarks:</span>
                                    <p class="text-sm text-blue-800 mt-2">{{ $workflow->assessor_remarks }}</p>
                                </div>
                            @endif
                            @if($workflow->next_steps_required)
                                <div class="mt-4">
                                    <span class="text-sm font-medium text-blue-900">Next Steps Required:</span>
                                    <p class="text-sm text-blue-800 mt-2">{{ $workflow->next_steps_required }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Final Review Actions -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Final Review Actions</h3>

                        <form method="POST" action="{{ route('opcr.workflows.approve', $workflow) }}" id="review-form">
                            @csrf
                            <div class="space-y-4">
                                <!-- Approval Status -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <x-input-label for="approval_status" value="Final Decision" />
                                        <select id="approval_status" name="approval_status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                            <option value="">Select Decision</option>
                                            <option value="approved">Approve - Final Approval</option>
                                            <option value="returned">Return for Revision</option>
                                            <option value="rejected">Reject - Significant Issues</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('approval_status')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="final_rating" value="Final Rating Override (Optional)" />
                                        <select id="final_rating" name="final_rating" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="">Use Computed Rating</option>
                                            <option value="5">5 - Outstanding</option>
                                            <option value="4">4 - Very Satisfactory</option>
                                            <option value="3">3 - Satisfactory</option>
                                            <option value="2">2 - Unsatisfactory</option>
                                            <option value="1">1 - Poor</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('final_rating')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="performance_level" value="Performance Level" />
                                        <select id="performance_level" name="performance_level" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="">Select Level</option>
                                            <option value="exceeds_expectations">Exceeds Expectations</option>
                                            <option value="meets_expectations">Meets Expectations</option>
                                            <option value="needs_improvement">Needs Improvement</option>
                                            <option value="unsatisfactory">Unsatisfactory</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('performance_level')" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Final Remarks -->
                                <div>
                                    <x-input-label for="final_remarks" value="Final Review Remarks" />
                                    <textarea id="final_remarks" name="final_remarks" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Provide final review comments and decisions...">{{ old('final_remarks') }}</textarea>
                                    <x-input-error :messages="$errors->get('final_remarks')" class="mt-2" />
                                </div>

                                <!-- Recommendations for Next Period -->
                                <div>
                                    <x-input-label for="recommendations_next_period" value="Recommendations for Next Period" />
                                    <textarea id="recommendations_next_period" name="recommendations_next_period" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Suggestions for improvement in the next performance period...">{{ old('recommendations_next_period') }}</textarea>
                                    <x-input-error :messages="$errors->get('recommendations_next_period')" class="mt-2" />
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                                    <a href="{{ route('opcr.workflows.show', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Cancel
                                    </a>
                                    <button type="button" id="return-btn" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150" style="display: none;">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        Return for Revision
                                    </button>
                                    <button type="button" id="reject-btn" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150" style="display: none;">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Reject
                                    </button>
                                    <button type="submit" id="approve-btn" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Approve OPCR
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        function calculateAdjectivalRating($rating) {
            if ($rating === null) return '--';
            if ($rating >= 4.51) return 'Outstanding';
            if ($rating >= 3.76) return 'Very Satisfactory';
            if ($rating >= 3.01) return 'Satisfactory';
            if ($rating >= 2.51) return 'Fairly Satisfactory';
            if ($rating >= 1.51) return 'Poor';
            return 'Very Poor';
        }
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const approvalStatus = document.getElementById('approval_status');
            const approveBtn = document.getElementById('approve-btn');
            const returnBtn = document.getElementById('return-btn');
            const rejectBtn = document.getElementById('reject-btn');

            // Show/hide action buttons based on approval status
            function updateActionButtons() {
                const status = approvalStatus.value;

                // Hide all buttons first
                approveBtn.style.display = 'none';
                returnBtn.style.display = 'none';
                rejectBtn.style.display = 'none';

                // Show relevant button
                if (status === 'approved') {
                    approveBtn.style.display = 'inline-flex';
                } else if (status === 'returned') {
                    returnBtn.style.display = 'inline-flex';
                } else if (status === 'rejected') {
                    rejectBtn.style.display = 'inline-flex';
                }
            }

            approvalStatus.addEventListener('change', updateActionButtons);

            // Handle different action buttons
            returnBtn.addEventListener('click', function() {
                if (!document.getElementById('final_remarks').value.trim()) {
                    alert('Please provide final review remarks explaining why the OPCR is being returned for revision.');
                    return;
                }

                if (confirm('Are you sure you want to return this OPCR for revision? The Department Head will need to make the requested changes.')) {
                    document.getElementById('review-form').submit();
                }
            });

            rejectBtn.addEventListener('click', function() {
                if (!document.getElementById('final_remarks').value.trim()) {
                    alert('Please provide detailed final review remarks explaining why the OPCR is being rejected.');
                    return;
                }

                if (confirm('Are you sure you want to reject this OPCR? This indicates significant issues that need to be addressed.')) {
                    document.getElementById('review-form').submit();
                }
            });

            approveBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (!document.getElementById('final_remarks').value.trim()) {
                    alert('Please provide final review remarks for the approval.');
                    return;
                }

                if (confirm('Are you sure you want to approve this OPCR? This action is final and cannot be undone.')) {
                    document.getElementById('review-form').submit();
                }
            });

            // Form validation on submit
            document.getElementById('review-form').addEventListener('submit', function(e) {
                if (!approvalStatus.value) {
                    e.preventDefault();
                    alert('Please select a final decision.');
                    return false;
                }

                if (!document.getElementById('final_remarks').value.trim()) {
                    e.preventDefault();
                    alert('Please provide final review remarks.');
                    return false;
                }

                return true;
            });
        });
    </script>
</x-app-layout>