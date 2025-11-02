<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Evaluate OPCR: {{ $workflow->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">OPCR Evaluation</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $workflow->office->name }} • {{ $workflow->period->year }} - {{ $workflow->period->semester }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('opcr.workflows.show', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Back to Details
                            </a>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                Evaluation Required
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('opcr.workflows.submit.evaluation', $workflow) }}" id="evaluation-form">
                    @csrf
                    <div class="p-6 space-y-6">
                        <!-- Workflow Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Commitment Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <span class="text-sm text-gray-600">Committed By:</span>
                                    <span class="text-sm font-medium ml-2">{{ $workflow->committedBy->employee->full_name ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Committed Date:</span>
                                    <span class="text-sm font-medium ml-2">{{ $workflow->committed_at ? $workflow->committed_at->format('M d, Y') : 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Performance Period:</span>
                                    <span class="text-sm font-medium ml-2">{{ $workflow->period->year }} - {{ $workflow->period->semester }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Evaluation Instructions -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-blue-900 mb-2">Evaluation Instructions</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Review each target's accomplishments against the set targets</li>
                                <li>• Rate each QET component (Quantity, Efficiency, Timeliness) on a scale of 1-5</li>
                                <li>• Provide constructive remarks for each rating</li>
                                <li>• The system will automatically calculate the average and adjectival rating</li>
                                <li>• Rating Scale: 5 = Outstanding, 4 = Very Satisfactory, 3 = Satisfactory, 2 = Unsatisfactory, 1 = Poor</li>
                            </ul>
                        </div>

                        <!-- Performance Targets Evaluation -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">Performance Targets Evaluation</h3>

                            @foreach($workflow->targets as $index => $target)
                                @php
                                    $existingRating = $target->ratings->first();
                                    $oldEvaluation = old('evaluations.'.$target->id, []);

                                    $resolvedAccomplishedQuantity = $oldEvaluation['accomplished_quantity']
                                        ?? $target->accomplished_quantity
                                        ?? $target->successIndicator->accomplished_quantity;

                                    $resolvedAccomplishedEfficiency = $oldEvaluation['accomplished_efficiency']
                                        ?? $target->accomplished_efficiency
                                        ?? $target->successIndicator->accomplished_efficiency;

                                    $resolvedAccomplishedTimeliness = $oldEvaluation['accomplished_timeliness']
                                        ?? $target->accomplished_timeliness
                                        ?? $target->successIndicator->accomplished_timeliness;

                                    $quantityRatingValue = $oldEvaluation['quantity_rating']
                                        ?? $existingRating?->rating_quantity;

                                    $efficiencyRatingValue = $oldEvaluation['efficiency_rating']
                                        ?? $existingRating?->rating_efficiency;

                                    $timelinessRatingValue = $oldEvaluation['timeliness_rating']
                                        ?? $existingRating?->rating_timeliness;

                                    $remarksValue = $oldEvaluation['remarks']
                                        ?? $existingRating?->remarks;

                                    $resolvedTargetMet = $target->is_target_met ?? $target->successIndicator->is_target_met;
                                    $resolvedPerformancePercentage = $target->performance_percentage ?? $target->successIndicator->performance_percentage;
                                @endphp
                                <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                                    <!-- Target Header -->
                                    <div class="mb-4">
                                        <h4 class="text-lg font-medium text-gray-900">Target {{ $index + 1 }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            MFO: {{ $target->mfo->full_code_path }} - {{ $target->mfo->title }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Success Indicator: {{ $target->successIndicator->code }} - {{ $target->successIndicator->title }}
                                        </p>
                                    </div>

                                    <!-- Target vs Accomplishment Comparison -->
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                                        <!-- Quantity Comparison -->
                                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                                            <h5 class="text-sm font-medium text-gray-900 mb-3">Quantity</h5>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Target:</span>
                                                    <span class="text-sm font-medium">{{ $target->target_quantity ?? 'Not Set' }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Accomplished:</span>
                                                    <span class="text-sm font-medium {{ $resolvedTargetMet ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $resolvedAccomplishedQuantity ?? 'Not Reported' }}
                                                    </span>
                                                </div>
                                                @if(!is_null($resolvedPerformancePercentage))
                                                    <div class="flex justify-between">
                                                        <span class="text-sm text-gray-600">Performance:</span>
                                                        <span class="text-sm font-medium {{ $resolvedTargetMet ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $resolvedPerformancePercentage }}%
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Efficiency Comparison -->
                                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                                            <h5 class="text-sm font-medium text-gray-900 mb-3">Efficiency</h5>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Target:</span>
                                                    <span class="text-sm font-medium">{{ $target->target_efficiency ?? 'Not Set' }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Accomplished:</span>
                                                    <span class="text-sm font-medium">{{ $resolvedAccomplishedEfficiency ?? 'Not Reported' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Timeliness Comparison -->
                                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                                            <h5 class="text-sm font-medium text-gray-900 mb-3">Timeliness</h5>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Target:</span>
                                                    <span class="text-sm font-medium">{{ $target->target_timeliness ?? 'Not Set' }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Accomplished:</span>
                                                    <span class="text-sm font-medium">{{ $resolvedAccomplishedTimeliness ?? 'Not Reported' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Accomplished Values Input -->
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                                        <!-- Accomplished Quantity -->
                                        <div>
                                            <x-input-label for="accomplished_quantity_{{ $index }}" value="Accomplished Quantity" />
                                            <input
                                                type="number"
                                                id="accomplished_quantity_{{ $index }}"
                                                name="evaluations[{{ $target->id }}][accomplished_quantity]"
                                                step="0.01"
                                                min="0"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                placeholder="{{ $target->target_quantity }}"
                                                value="{{ is_null($resolvedAccomplishedQuantity) ? '' : $resolvedAccomplishedQuantity }}"
                                                required
                                            >
                                            <x-input-error :messages="$errors->get('evaluations.'.$target->id.'.accomplished_quantity')" class="mt-2" />
                                        </div>

                                        <!-- Accomplished Efficiency -->
                                        <div>
                                            <x-input-label for="accomplished_efficiency_{{ $index }}" value="Accomplished Efficiency" />
                                            <input
                                                type="text"
                                                id="accomplished_efficiency_{{ $index }}"
                                                name="evaluations[{{ $target->id }}][accomplished_efficiency]"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                placeholder="{{ $target->target_efficiency }}"
                                                value="{{ $resolvedAccomplishedEfficiency }}"
                                                required
                                            >
                                            <x-input-error :messages="$errors->get('evaluations.'.$target->id.'.accomplished_efficiency')" class="mt-2" />
                                        </div>

                                        <!-- Accomplished Timeliness -->
                                        <div>
                                            <x-input-label for="accomplished_timeliness_{{ $index }}" value="Accomplished Timeliness" />
                                            <input
                                                type="text"
                                                id="accomplished_timeliness_{{ $index }}"
                                                name="evaluations[{{ $target->id }}][accomplished_timeliness]"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                placeholder="{{ $target->target_timeliness }}"
                                                value="{{ $resolvedAccomplishedTimeliness }}"
                                                required
                                            >
                                            <x-input-error :messages="$errors->get('evaluations.'.$target->id.'.accomplished_timeliness')" class="mt-2" />
                                        </div>
                                    </div>

                                    <!-- QET Rating Inputs -->
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                                        <!-- Quantity Rating -->
                                        <div>
                                            <x-input-label for="rating_quantity_{{ $index }}" value="Quantity Rating (1-5)" />
                                            <select id="rating_quantity_{{ $index }}" name="evaluations[{{ $target->id }}][quantity_rating]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                <option value="">Select Rating</option>
                                                <option value="5" {{ (string)$quantityRatingValue === '5' ? 'selected' : '' }}>5 - Outstanding</option>
                                                <option value="4" {{ (string)$quantityRatingValue === '4' ? 'selected' : '' }}>4 - Very Satisfactory</option>
                                                <option value="3" {{ (string)$quantityRatingValue === '3' ? 'selected' : '' }}>3 - Satisfactory</option>
                                                <option value="2" {{ (string)$quantityRatingValue === '2' ? 'selected' : '' }}>2 - Unsatisfactory</option>
                                                <option value="1" {{ (string)$quantityRatingValue === '1' ? 'selected' : '' }}>1 - Poor</option>
                                            </select>
                                            <x-input-error :messages="$errors->get('evaluations.'.$target->id.'.quantity_rating')" class="mt-2" />
                                        </div>

                                        <!-- Efficiency Rating -->
                                        <div>
                                            <x-input-label for="rating_efficiency_{{ $index }}" value="Efficiency Rating (1-5)" />
                                            <select id="rating_efficiency_{{ $index }}" name="evaluations[{{ $target->id }}][efficiency_rating]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                <option value="">Select Rating</option>
                                                <option value="5" {{ (string)$efficiencyRatingValue === '5' ? 'selected' : '' }}>5 - Outstanding</option>
                                                <option value="4" {{ (string)$efficiencyRatingValue === '4' ? 'selected' : '' }}>4 - Very Satisfactory</option>
                                                <option value="3" {{ (string)$efficiencyRatingValue === '3' ? 'selected' : '' }}>3 - Satisfactory</option>
                                                <option value="2" {{ (string)$efficiencyRatingValue === '2' ? 'selected' : '' }}>2 - Unsatisfactory</option>
                                                <option value="1" {{ (string)$efficiencyRatingValue === '1' ? 'selected' : '' }}>1 - Poor</option>
                                            </select>
                                            <x-input-error :messages="$errors->get('evaluations.'.$target->id.'.efficiency_rating')" class="mt-2" />
                                        </div>

                                        <!-- Timeliness Rating -->
                                        <div>
                                            <x-input-label for="rating_timeliness_{{ $index }}" value="Timeliness Rating (1-5)" />
                                            <select id="rating_timeliness_{{ $index }}" name="evaluations[{{ $target->id }}][timeliness_rating]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                <option value="">Select Rating</option>
                                                <option value="5" {{ (string)$timelinessRatingValue === '5' ? 'selected' : '' }}>5 - Outstanding</option>
                                                <option value="4" {{ (string)$timelinessRatingValue === '4' ? 'selected' : '' }}>4 - Very Satisfactory</option>
                                                <option value="3" {{ (string)$timelinessRatingValue === '3' ? 'selected' : '' }}>3 - Satisfactory</option>
                                                <option value="2" {{ (string)$timelinessRatingValue === '2' ? 'selected' : '' }}>2 - Unsatisfactory</option>
                                                <option value="1" {{ (string)$timelinessRatingValue === '1' ? 'selected' : '' }}>1 - Poor</option>
                                            </select>
                                            <x-input-error :messages="$errors->get('evaluations.'.$target->id.'.timeliness_rating')" class="mt-2" />
                                        </div>
                                    </div>

                                    <!-- Hidden target_id input -->
                                    <input type="hidden" name="evaluations[{{ $target->id }}][target_id]" value="{{ $target->id }}">

                                    <!-- Rating Calculation Display -->
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6" id="rating-display-{{ $index }}">
                                        <h5 class="text-sm font-medium text-gray-900 mb-3">Rating Summary</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div class="text-center">
                                                <div class="text-2xl font-bold text-gray-400" id="average-rating-{{ $index }}">--</div>
                                                <div class="text-xs text-gray-600">Average Rating</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-lg font-medium text-gray-400" id="adjectival-rating-{{ $index }}">--</div>
                                                <div class="text-xs text-gray-600">Adjectival Rating</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-lg font-medium text-indigo-600" id="quantity-rating-display-{{ $index }}">--</div>
                                                <div class="text-xs text-gray-600">Quantity</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-lg font-medium text-indigo-600" id="qet-summary-{{ $index }}">--</div>
                                                <div class="text-xs text-gray-600">QET Scores</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div>
                                        <x-input-label for="remarks_{{ $index }}" value="Evaluation Remarks" />
                                        <textarea
                                            id="remarks_{{ $index }}"
                                            name="evaluations[{{ $target->id }}][remarks]"
                                            rows="3"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            placeholder="Provide detailed feedback on the performance..."
                                        >{{ $remarksValue }}</textarea>
                                        <x-input-error :messages="$errors->get('evaluations.'.$target->id.'.remarks')" class="mt-2" />
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Overall Evaluation Summary -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Overall Evaluation Summary</h3>
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <x-input-label for="overall_remarks" value="Overall Remarks" />
                                        <textarea id="overall_remarks" name="overall_remarks" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Provide overall assessment and recommendations..." required oninput="updateCharCounter()">{{ old('overall_remarks', $workflow->assessor_remarks) }}</textarea>
                                        <div class="mt-1 text-sm text-gray-500">
                                            <span id="char-counter">0</span> / 10 characters minimum (2000 max)
                                        </div>
                                        <x-input-error :messages="$errors->get('overall_remarks')" class="mt-2" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <div class="grid grid-cols-2 gap-6">
                                            <div>
                                                <x-input-label for="recommendations" value="Recommendations" />
                                                <textarea id="recommendations" name="recommendations" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Specific recommendations for improvement...">{{ old('recommendations', $workflow->recommendations) }}</textarea>
                                                <x-input-error :messages="$errors->get('recommendations')" class="mt-2" />
                                            </div>

                                            <div>
                                                <x-input-label for="action" value="Action" />
                                                <select id="action" name="action" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                    <option value="">Select Action</option>
                                                    <option value="submit" {{ old('action') === 'submit' ? 'selected' : '' }}>Submit Evaluation</option>
                                                    <option value="return" {{ old('action') === 'return' ? 'selected' : '' }}>Return for Revision</option>
                                                </select>
                                                <x-input-error :messages="$errors->get('action')" class="mt-2" />
                                            </div>
                                        </div>

                                        <!-- Return Reason (shown when action is 'return') -->
                                        <div id="return_reason_section" class="mt-4 {{ old('action') === 'return' ? '' : 'hidden' }}">
                                            <x-input-label for="return_reason" value="Return Reason" />
                                            <textarea id="return_reason" name="return_reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Specify the reason for returning this OPCR for revision...">{{ old('return_reason') }}</textarea>
                                            <x-input-error :messages="$errors->get('return_reason')" class="mt-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="border-t pt-6">
                            <div class="flex items-center justify-end space-x-4">
                                <a href="{{ route('opcr.workflows.show', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancel
                                </a>
                                <button type="button" id="save-draft-btn" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V2" />
                                    </svg>
                                    Save Draft
                                </button>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Submit Evaluation
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Character counter for overall remarks - global function
        function updateCharCounter() {
            const textarea = document.getElementById('overall_remarks');
            const counter = document.getElementById('char-counter');
            if (textarea && counter) {
                const length = textarea.value.length;
                counter.textContent = length;

                // Update color based on character count
                if (length < 10) {
                    counter.className = 'text-red-600 font-medium';
                } else if (length > 2000) {
                    counter.className = 'text-red-600 font-medium';
                } else {
                    counter.className = 'text-green-600 font-medium';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize character counter
            updateCharCounter();
            const targets = {{ $workflow->targets->count() }};

            // Rating calculation function
            function calculateRating(targetIndex) {
                const quantityRating = document.getElementById(`rating_quantity_${targetIndex}`);
                const efficiencyRating = document.getElementById(`rating_efficiency_${targetIndex}`);
                const timelinessRating = document.getElementById(`rating_timeliness_${targetIndex}`);

                if (quantityRating.value && efficiencyRating.value && timelinessRating.value) {
                    const q = parseFloat(quantityRating.value);
                    const e = parseFloat(efficiencyRating.value);
                    const t = parseFloat(timelinessRating.value);

                    const average = ((q + e + t) / 3).toFixed(2);
                    let adjectival = '';

                    if (average >= 4.51) adjectival = 'Outstanding';
                    else if (average >= 3.76) adjectival = 'Very Satisfactory';
                    else if (average >= 3.01) adjectival = 'Satisfactory';
                    else if (average >= 2.51) adjectival = 'Fairly Satisfactory';
                    else if (average >= 1.51) adjectival = 'Poor';
                    else adjectival = 'Very Poor';

                    // Update display
                    document.getElementById(`average-rating-${targetIndex}`).textContent = average;
                    document.getElementById(`adjectival-rating-${targetIndex}`).textContent = adjectival;
                    document.getElementById(`quantity-rating-display-${targetIndex}`).textContent = `${q}/5`;
                    document.getElementById(`qet-summary-${targetIndex}`).textContent = `Q:${q} E:${e} T:${t}`;

                    // Update color based on rating
                    const ratingDisplay = document.getElementById(`rating-display-${targetIndex}`);
                    const colorClass = average >= 4.5 ? 'text-green-600' :
                                     average >= 3.5 ? 'text-blue-600' :
                                     average >= 2.5 ? 'text-yellow-600' :
                                     average >= 1.5 ? 'text-orange-600' : 'text-red-600';

                    ratingDisplay.classList.remove('text-gray-400', 'text-green-600', 'text-blue-600', 'text-yellow-600', 'text-orange-600', 'text-red-600');
                    ratingDisplay.classList.add(colorClass);
                } else {
                    // Reset display
                    document.getElementById(`average-rating-${targetIndex}`).textContent = '--';
                    document.getElementById(`adjectival-rating-${targetIndex}`).textContent = '--';
                    document.getElementById(`quantity-rating-display-${targetIndex}`).textContent = '--';
                    document.getElementById(`qet-summary-${targetIndex}`).textContent = '--';

                    const ratingDisplay = document.getElementById(`rating-display-${targetIndex}`);
                    ratingDisplay.classList.remove('text-green-600', 'text-blue-600', 'text-yellow-600', 'text-orange-600', 'text-red-600');
                    ratingDisplay.classList.add('text-gray-400');
                }
            }

            // Setup event listeners for all rating inputs
            for (let i = 0; i < targets; i++) {
                const quantityRating = document.getElementById(`rating_quantity_${i}`);
                const efficiencyRating = document.getElementById(`rating_efficiency_${i}`);
                const timelinessRating = document.getElementById(`rating_timeliness_${i}`);

                if (quantityRating) quantityRating.addEventListener('change', () => calculateRating(i));
                if (efficiencyRating) efficiencyRating.addEventListener('change', () => calculateRating(i));
                if (timelinessRating) timelinessRating.addEventListener('change', () => calculateRating(i));

                calculateRating(i);
            }

            // Save draft functionality
            document.getElementById('save-draft-btn').addEventListener('click', function() {
                const form = document.getElementById('evaluation-form');
                const formData = new FormData(form);

                // Create a temporary form to submit as draft
                const tempForm = document.createElement('form');
                tempForm.method = 'POST';
                tempForm.action = '{{ route("opcr.workflows.submit.evaluation", $workflow) }}';

                // Add CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                tempForm.appendChild(csrfInput);

                // Add draft flag
                const draftInput = document.createElement('input');
                draftInput.type = 'hidden';
                draftInput.name = 'save_as_draft';
                draftInput.value = '1';
                tempForm.appendChild(draftInput);

                // Copy all form data
                for (let [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    tempForm.appendChild(input);
                }

                document.body.appendChild(tempForm);
                tempForm.submit();
            });

            // Show/hide return reason based on action selection
            const actionSelect = document.getElementById('action');
            const returnReasonSection = document.getElementById('return_reason_section');
            const returnReason = document.getElementById('return_reason');

            if (actionSelect && actionSelect.value === 'return') {
                returnReasonSection.classList.remove('hidden');
                returnReason.setAttribute('required', 'required');
            }

            if (actionSelect) {
                actionSelect.addEventListener('change', function() {
                    if (this.value === 'return') {
                        returnReasonSection.classList.remove('hidden');
                        returnReason.setAttribute('required', 'required');
                    } else {
                        returnReasonSection.classList.add('hidden');
                        returnReason.removeAttribute('required');
                    }
                });
            }

            // Form validation
            document.getElementById('evaluation-form').addEventListener('submit', function(e) {
                console.log('Form submission triggered, validating...');
                let allRated = true;
                let missingFields = [];

                for (let i = 0; i < targets; i++) {
                    const quantityRating = document.getElementById(`rating_quantity_${i}`);
                    const efficiencyRating = document.getElementById(`rating_efficiency_${i}`);
                    const timelinessRating = document.getElementById(`rating_timeliness_${i}`);
                    const accomplishedQuantity = document.getElementById(`accomplished_quantity_${i}`);
                    const accomplishedEfficiency = document.getElementById(`accomplished_efficiency_${i}`);
                    const accomplishedTimeliness = document.getElementById(`accomplished_timeliness_${i}`);

                    console.log(`Target ${i} field check:`, {
                        quantityRating: quantityRating?.value,
                        efficiencyRating: efficiencyRating?.value,
                        timelinessRating: timelinessRating?.value,
                        accomplishedQuantity: accomplishedQuantity?.value,
                        accomplishedEfficiency: accomplishedEfficiency?.value,
                        accomplishedTimeliness: accomplishedTimeliness?.value,
                    });

                    if (!quantityRating || !quantityRating.value ||
                        !efficiencyRating || !efficiencyRating.value ||
                        !timelinessRating || !timelinessRating.value ||
                        !accomplishedQuantity || !accomplishedQuantity.value ||
                        !accomplishedEfficiency || !accomplishedEfficiency.value ||
                        !accomplishedTimeliness || !accomplishedTimeliness.value) {
                        allRated = false;
                        missingFields.push(`Target ${i + 1}`);
                        console.log(`Target ${i} validation failed`);
                    }
                }

                console.log('All fields valid:', allRated, 'Missing fields:', missingFields);

                if (!allRated) {
                    e.preventDefault();
                    alert('Please provide ratings and accomplished values for all QET components (Quantity, Efficiency, Timeliness) for each target. Missing: ' + missingFields.join(', '));
                    return false;
                }

                const action = document.getElementById('action');
                if (!action.value) {
                    e.preventDefault();
                    alert('Please select an action for this evaluation.');
                    return false;
                }

                const overallRemarks = document.getElementById('overall_remarks');
                if (!overallRemarks.value.trim()) {
                    e.preventDefault();
                    alert('Please provide overall remarks for this evaluation.');
                    return false;
                }

                if (action.value === 'return') {
                    const returnReason = document.getElementById('return_reason');
                    if (!returnReason.value.trim()) {
                        e.preventDefault();
                        alert('Please provide a reason for returning this OPCR for revision.');
                        return false;
                    }
                }

                return confirm('Are you sure you want to submit this evaluation? This action cannot be undone.');
            });
        });
    </script>
</x-app-layout>
