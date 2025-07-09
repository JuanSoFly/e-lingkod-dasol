<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Questionnaire') }} - {{ $employee->full_name }}
            </h2>
            <a href="{{ route('pds.dashboard', $employee) }}">
                <x-secondary-button>
                    {{ __('Back to PDS Dashboard') }}
                </x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Introduction -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Important Legal Declaration</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>This questionnaire contains legally binding declarations. Answer all questions truthfully and completely. If you answer "YES" to any question, provide detailed explanations in the corresponding detail fields. Providing false information may result in disqualification or termination.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questionnaire Form -->
            <form method="POST" action="{{ route('pds.update-questionnaire', $employee) }}">
                @csrf
                @method('PATCH')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Legal and Ethical Declarations</h3>
                        
                        <div class="space-y-8">
                            @foreach($questionLabels as $questionCode => $questionText)
                                @php
                                    $currentAnswer = $questionnaire?->getQuestionAnswer($questionCode) ?? false;
                                    $currentDetail = $questionnaire?->getQuestionDetail($questionCode) ?? '';
                                @endphp
                                
                                <div class="border border-gray-200 rounded-lg p-6">
                                    <!-- Question Number and Text -->
                                    <div class="mb-4">
                                        <h4 class="text-md font-medium text-gray-900 mb-2">
                                            {{ strtoupper($questionCode) }}. {{ $questionText }}
                                        </h4>
                                    </div>

                                    <!-- Yes/No Radio Buttons -->
                                    <div class="mb-4">
                                        <fieldset>
                                            <legend class="sr-only">Answer for {{ $questionCode }}</legend>
                                            <div class="flex space-x-6">
                                                <div class="flex items-center">
                                                    <input type="radio" 
                                                           id="{{ $questionCode }}_yes" 
                                                           name="questions[{{ $questionCode }}]" 
                                                           value="1"
                                                           {{ $currentAnswer ? 'checked' : '' }}
                                                           onchange="toggleDetailField('{{ $questionCode }}', true)"
                                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                    <label for="{{ $questionCode }}_yes" class="ml-2 text-sm font-medium text-gray-700">
                                                        YES
                                                    </label>
                                                </div>
                                                <div class="flex items-center">
                                                    <input type="radio" 
                                                           id="{{ $questionCode }}_no" 
                                                           name="questions[{{ $questionCode }}]" 
                                                           value="0"
                                                           {{ !$currentAnswer ? 'checked' : '' }}
                                                           onchange="toggleDetailField('{{ $questionCode }}', false)"
                                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                    <label for="{{ $questionCode }}_no" class="ml-2 text-sm font-medium text-gray-700">
                                                        NO
                                                    </label>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <!-- Detail Field (shown when YES is selected) -->
                                    <div id="detail_{{ $questionCode }}" class="{{ $currentAnswer ? '' : 'hidden' }}">
                                        <label for="details_{{ $questionCode }}" class="block text-sm font-medium text-gray-700 mb-2">
                                            Please provide details:
                                        </label>
                                        <textarea name="details[{{ $questionCode }}]" 
                                                  id="details_{{ $questionCode }}"
                                                  rows="4"
                                                  placeholder="Provide complete details including dates, circumstances, and resolution if applicable..."
                                                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('details.'.$questionCode, $currentDetail) }}</textarea>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Be specific and include all relevant information. This may be subject to verification.
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Declaration -->
                        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Declaration</h4>
                            <div class="text-sm text-gray-700 space-y-2">
                                <p>I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines.</p>
                                <p>I authorize the agency head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this document and its supporting papers shall cause the filing of administrative/criminal case/s against me.</p>
                                <p class="font-medium text-gray-900 mt-4">
                                    By submitting this questionnaire, you acknowledge that you have read, understood, and agree to the above declaration.
                                </p>
                            </div>
                        </div>

                        <!-- Completion Status -->
                        @php
                            $totalQuestions = count($questionLabels);
                            $answeredQuestions = 0;
                            $hasYesAnswers = false;
                            
                            if ($questionnaire && $questionnaire->questions_answers) {
                                $answeredQuestions = count(array_filter($questionnaire->questions_answers, function($answer) {
                                    return $answer !== null;
                                }));
                                $hasYesAnswers = in_array(true, $questionnaire->questions_answers);
                            }
                            
                            $completionPercentage = $totalQuestions > 0 ? ($answeredQuestions / $totalQuestions) * 100 : 0;
                        @endphp

                        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-blue-800">Completion Status</span>
                                <span class="text-sm text-blue-600">{{ $answeredQuestions }} of {{ $totalQuestions }} questions answered</span>
                            </div>
                            <div class="w-full bg-blue-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $completionPercentage }}%"></div>
                            </div>
                            @if($hasYesAnswers)
                                <p class="text-sm text-orange-600 mt-2">
                                    <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    You have answered "YES" to one or more questions. Please ensure all required details are provided.
                                </p>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-8 flex justify-end space-x-4">
                            <a href="{{ route('pds.dashboard', $employee) }}">
                                <x-secondary-button>
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </a>
                            <x-primary-button>
                                {{ __('Update Questionnaire') }}
                            </x-primary-button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Help Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Guidelines for Answering</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Be honest and accurate</strong> - All answers may be verified through background checks</li>
                                <li><strong>Provide complete details for "YES" answers</strong> - Include dates, circumstances, and outcomes</li>
                                <li><strong>Don't leave questions unanswered</strong> - Every question must have either "YES" or "NO" selected</li>
                                <li><strong>Double-check your answers</strong> - Review all responses before submitting</li>
                                <li><strong>Keep supporting documents</strong> - You may be asked to provide documentation for any "YES" answers</li>
                            </ul>
                            <div class="mt-3 p-3 bg-blue-100 rounded-md">
                                <p class="text-xs font-medium text-blue-800">Remember:</p>
                                <p class="text-xs text-blue-700 mt-1">
                                    A "YES" answer does not automatically disqualify you from employment. What matters is your honesty and the circumstances surrounding the incident. Failure to disclose information that is later discovered may result in disciplinary action.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Dynamic Detail Fields -->
    <script>
        function toggleDetailField(questionCode, showDetail) {
            const detailDiv = document.getElementById('detail_' + questionCode);
            const textarea = document.getElementById('details_' + questionCode);
            
            if (showDetail) {
                detailDiv.classList.remove('hidden');
                textarea.focus();
                // Make the textarea required when YES is selected
                textarea.setAttribute('required', 'required');
            } else {
                detailDiv.classList.add('hidden');
                textarea.value = ''; // Clear the content when hidden
                // Remove required attribute when NO is selected
                textarea.removeAttribute('required');
            }
        }

        // Initialize detail fields based on current values on page load
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($questionLabels as $questionCode => $questionText)
                @php
                    $currentAnswer = $questionnaire?->getQuestionAnswer($questionCode) ?? false;
                @endphp
                
                @if($currentAnswer)
                    // Ensure required attribute is set for fields that should be shown
                    document.getElementById('details_{{ $questionCode }}').setAttribute('required', 'required');
                @endif
            @endforeach
        });

        // Form validation before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const yesAnswers = document.querySelectorAll('input[type="radio"][value="1"]:checked');
            let hasEmptyDetails = false;
            
            yesAnswers.forEach(function(radio) {
                const questionCode = radio.name.match(/questions\[([^\]]+)\]/)[1];
                const textarea = document.getElementById('details_' + questionCode);
                
                if (textarea && textarea.value.trim() === '') {
                    hasEmptyDetails = true;
                    textarea.classList.add('border-red-500');
                } else if (textarea) {
                    textarea.classList.remove('border-red-500');
                }
            });
            
            if (hasEmptyDetails) {
                e.preventDefault();
                alert('Please provide details for all questions you answered "YES" to.');
                return false;
            }
        });
    </script>
</x-app-layout>