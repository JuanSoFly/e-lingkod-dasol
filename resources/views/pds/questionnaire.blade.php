<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('PDS - Page 4 Declarations') }} for {{ $employee->full_name }}
            </h2>
            <a href="{{ route('pds.dashboard', $employee) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <script>
    function toggleTextarea(questionPrefix) {
        const yesRadio = document.querySelector(`input[name="${questionPrefix}_yes_no"][value="1"]`);
        const textarea = document.getElementById(`${questionPrefix}_details`);

        if (yesRadio && textarea) {
            textarea.style.display = yesRadio.checked ? 'block' : 'none';
            if (textarea.tagName === 'TEXTAREA') {
                textarea.required = yesRadio.checked;
            } else if (textarea.tagName === 'INPUT') {
                // For text inputs in question 40
                const textInput = textarea.querySelector('input') || textarea.querySelector('textarea');
                if (textInput) {
                    textInput.required = yesRadio.checked;
                }
            }
        }
    }

    function updateProgress() {
        const questionPrefixes = [
            'field_34', 'field_34b', 'field_35a', 'field_35b', 'field_36', 'field_37',
            'field_38a', 'field_38b', 'field_39', 'field_40a', 'field_40b', 'field_40c'
        ];

        let answeredCount = 0;
        questionPrefixes.forEach(prefix => {
            const radioButtons = document.querySelectorAll(`input[name="${prefix}_yes_no"]`);
            radioButtons.forEach(radio => {
                if (radio.checked) {
                    answeredCount++;
                }
            });
        });

        const progress = Math.round((answeredCount / questionPrefixes.length) * 100);
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');

        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
        if (progressText) {
            progressText.textContent = progress + '% Complete';
        }

        // Update progress bar color based on completion
        if (progressBar) {
            progressBar.className = 'h-2 rounded-full transition-all duration-300';
            if (progress === 100) {
                progressBar.classList.add('bg-green-600');
            } else if (progress >= 75) {
                progressBar.classList.add('bg-blue-600');
            } else if (progress >= 50) {
                progressBar.classList.add('bg-yellow-600');
            } else {
                progressBar.classList.add('bg-red-600');
            }
        }
    }

    function highlightUnansweredQuestions(unansweredQuestions) {
        // Clear previous highlights
        document.querySelectorAll('.question-group').forEach(group => {
            group.classList.remove('border-red-500', 'bg-red-50');
        });

        // Highlight unanswered questions
        unansweredQuestions.forEach(prefix => {
            const radioGroup = document.querySelector(`input[name="${prefix}_yes_no"]`)?.closest('.question-group');
            if (radioGroup) {
                radioGroup.classList.add('border-red-500', 'bg-red-50');
                const radioButtons = radioGroup.querySelectorAll('input[type="radio"]');
                radioButtons.forEach(radio => {
                    radio.closest('label').classList.add('border-red-500', 'border-2');
                });
            }
        });
    }

    function clearHighlights() {
        document.querySelectorAll('.question-group').forEach(group => {
            group.classList.remove('border-red-500', 'bg-red-50');
        });
        document.querySelectorAll('label').forEach(label => {
            label.classList.remove('border-red-500', 'border-2');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all textarea toggles
        const questionPrefixes = [
            'field_34', 'field_34b', 'field_35a', 'field_35b', 'field_36', 'field_37',
            'field_38a', 'field_38b', 'field_39', 'field_40a', 'field_40b', 'field_40c'
        ];

        questionPrefixes.forEach(prefix => {
            // Set initial state (hidden by default since radio buttons start unselected)
            toggleTextarea(prefix);

            // Add event listeners to radio buttons
            const radioButtons = document.querySelectorAll(`input[name="${prefix}_yes_no"]`);
            radioButtons.forEach(radio => {
                radio.addEventListener('change', () => {
                    toggleTextarea(prefix);
                    updateProgress();
                    clearHighlights(); // Clear error highlights when user makes a selection
                });
            });
        });

        // Initialize progress on page load
        updateProgress();

        // Add form submission validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Check for Laravel validation errors first
                const errorElements = document.querySelectorAll('.text-red-600');
                if (errorElements.length > 0) {
                    e.preventDefault();
                    showNotification('Please fix the validation errors before submitting.', 'error');

                    // Scroll to first error
                    const firstError = errorElements[0];
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        const relatedInput = firstError.closest('.question-group')?.querySelector('input[type="radio"]');
                        if (relatedInput) relatedInput.focus();
                    }
                    return false;
                }

                const unansweredQuestions = [];
                const missingDetails = [];

                questionPrefixes.forEach(prefix => {
                    const radioButtons = document.querySelectorAll(`input[name="${prefix}_yes_no"]`);
                    let isAnswered = false;
                    let selectedValue = null;

                    radioButtons.forEach(radio => {
                        if (radio.checked) {
                            isAnswered = true;
                            selectedValue = radio.value;
                        }
                    });

                    if (!isAnswered) {
                        unansweredQuestions.push(prefix);
                    } else if (selectedValue === '1') {
                        // Check if details are provided when YES is selected
                        const detailsElement = document.getElementById(`${prefix}_details`);
                        if (detailsElement) {
                            const textarea = detailsElement.querySelector('textarea');
                            const textInput = detailsElement.querySelector('input[type="text"]');
                            const hasDetails = (textarea && textarea.value.trim()) || (textInput && textInput.value.trim());

                            if (!hasDetails) {
                                missingDetails.push(prefix);
                            }
                        }
                    }
                });

                if (unansweredQuestions.length > 0 || missingDetails.length > 0) {
                    e.preventDefault();

                    // Highlight missing questions and details
                    const allMissing = [...unansweredQuestions, ...missingDetails];
                    highlightUnansweredQuestions(allMissing);

                    // Create comprehensive error message
                    let message = '';
                    if (unansweredQuestions.length > 0 && missingDetails.length > 0) {
                        message = `Please answer ${unansweredQuestions.length} unanswered question(s) and provide details for ${missingDetails.length} "YES" answer(s).`;
                    } else if (unansweredQuestions.length > 0) {
                        const unansweredCount = unansweredQuestions.length;
                        message = unansweredCount === 1
                            ? 'Please answer the remaining required question before submitting the form.'
                            : `Please answer the ${unansweredCount} remaining required questions before submitting the form.`;
                    } else if (missingDetails.length > 0) {
                        message = `Please provide details for ${missingDetails.length} question(s) where you answered "YES".`;
                    }

                    // Show notification instead of alert
                    showNotification(message, 'error');

                    // Scroll to first missing item
                    const firstMissing = document.querySelector(`input[name="${allMissing[0]}_yes_no"]`);
                    if (firstMissing) {
                        firstMissing.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstMissing.focus();
                    }

                    return false;
                }
            });
        }

        // Initialize progress based on saved data
        function initializeProgressFromSaved() {
            let answeredCount = 0;

            questionPrefixes.forEach(prefix => {
                const radioButtons = document.querySelectorAll(`input[name="${prefix}_yes_no"]`);
                radioButtons.forEach(radio => {
                    if (radio.checked) {
                        answeredCount++;
                    }
                });
            });

            const progress = Math.round((answeredCount / questionPrefixes.length) * 100);
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');

            if (progressBar) {
                progressBar.style.width = progress + '%';
                // Set initial color based on completion
                progressBar.className = 'h-2 rounded-full transition-all duration-300';
                if (progress === 100) {
                    progressBar.classList.add('bg-green-600');
                } else if (progress >= 75) {
                    progressBar.classList.add('bg-blue-600');
                } else if (progress >= 50) {
                    progressBar.classList.add('bg-yellow-600');
                } else {
                    progressBar.classList.add('bg-red-600');
                }
            }
            if (progressText) {
                progressText.textContent = progress + '% Complete';
            }
        }

        // Initialize detail fields visibility based on saved data
        function initializeDetailFieldsVisibility() {
            const questionPrefixes = [
                'field_34', 'field_34b', 'field_35a', 'field_35b', 'field_36', 'field_37',
                'field_38a', 'field_38b', 'field_39', 'field_40a', 'field_40b', 'field_40c'
            ];

            questionPrefixes.forEach(prefix => {
                const yesRadio = document.querySelector(`input[name="${prefix}_yes_no"][value="1"]`);
                const detailsElement = document.getElementById(`${prefix}_details`);

                if (yesRadio && detailsElement) {
                    // Show details if YES radio is checked
                    if (yesRadio.checked) {
                        detailsElement.style.display = 'block';
                        // Set required attribute for detail inputs
                        const textarea = detailsElement.querySelector('textarea');
                        const textInput = detailsElement.querySelector('input[type="text"]');
                        if (textarea) textarea.required = true;
                        if (textInput) textInput.required = true;
                    } else {
                        detailsElement.style.display = 'none';
                        // Remove required attribute for detail inputs
                        const textarea = detailsElement.querySelector('textarea');
                        const textInput = detailsElement.querySelector('input[type="text"]');
                        if (textarea) textarea.required = false;
                        if (textInput) textInput.required = false;
                    }
                }
            });
        }

        // Call this after setting up event listeners
        initializeProgressFromSaved();
        initializeDetailFieldsVisibility();

        // Add keyboard navigation support
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    // Move to next radio in group
                    const group = document.querySelectorAll(`input[name="${radio.name}"]`);
                    const currentIndex = Array.from(group).indexOf(radio);
                    if (currentIndex < group.length - 1) {
                        group[currentIndex + 1].focus();
                    }
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    // Move to previous radio in group
                    const group = document.querySelectorAll(`input[name="${radio.name}"]`);
                    const currentIndex = Array.from(group).indexOf(radio);
                    if (currentIndex > 0) {
                        group[currentIndex - 1].focus();
                    }
                }
            });
        });
    });

    function showNotification(message, type = 'info') {
        // Remove any existing notifications
        const existingNotification = document.querySelector('.notification-toast');
        if (existingNotification) {
            existingNotification.remove();
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification-toast fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md transform transition-all duration-300 ${
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-black' :
            type === 'success' ? 'bg-green-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    </script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-800">{{ session('warning') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Progress Indicator -->
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-medium text-blue-900">Questionnaire Completion Status</h3>
                    <span id="progress-text" class="text-sm text-blue-700 font-medium">{{ $completionPercentage }}% Complete</span>
                </div>
                <div class="w-full bg-blue-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $completionPercentage }}%"></div>
                </div>
                <p class="text-xs text-blue-700 mt-1">All questions marked with <span class="text-red-500">*</span> are required</p>
            </div>

            <form method="POST" action="{{ route('pds.update-questionnaire', $employee) }}" class="space-y-8">
                @csrf
                @method('POST')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8 bg-white border-b border-gray-200">
                        <div class="space-y-6">
                            <!-- Question 34: Relationship to appointing authority -->
                            <div class="border-t border-gray-200 pt-4 question-group" data-question="field_34_yes_no">
                                <h3 class="text-sm font-medium text-gray-900 mb-3">
                                    34. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be appointed,
                                    <span class="text-red-500">*</span>
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">a. within the third degree?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_34_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_34_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_34_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_34_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_34_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div id="field_34_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_34_relationship_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_34_relationship_details', $questionnaire->field_34_relationship_details) }}</textarea>
                                        @error('field_34_relationship_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">b. within the fourth degree (for Local Government Unit - Career Employees)?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_34b_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_34b_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_34b_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_34b_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_34b_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div id="field_34b_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_34b_relationship_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_34b_relationship_details', $questionnaire->field_34b_relationship_details) }}</textarea>
                                        @error('field_34b_relationship_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Question 35: Administrative/criminal charges -->
                            <div class="border-t border-gray-200 pt-4 question-group" data-question="field_35a_yes_no">
                                <h3 class="text-sm font-medium text-gray-900 mb-3">
                                    35. Please answer the following questions:
                                    <span class="text-red-500">*</span>
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">a. Have you ever been found guilty of any administrative offense?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_35a_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_35a_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_35a_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_35a_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_35a_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div id="field_35a_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_35_administrative_offense_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_35_administrative_offense_details', $questionnaire->field_35_administrative_offense_details) }}</textarea>
                                        @error('field_35_administrative_offense_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">b. Have you been criminally charged before any court?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_35b_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_35b_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_35b_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_35b_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_35b_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div id="field_35b_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_36_criminal_charge_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_36_criminal_charge_details', $questionnaire->field_36_criminal_charge_details) }}</textarea>
                                        @error('field_36_criminal_charge_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Question 36: Conviction of any crime -->
                            <div class="border-t border-gray-200 pt-4 question-group" data-question="field_36_yes_no">
                                <h3 class="text-sm font-medium text-gray-900 mb-3">
                                    36. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?
                                    <span class="text-red-500">*</span>
                                </h3>
                                <div class="space-y-4">
                                    <div class="flex space-x-6 radio-group" data-required="true">
                                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                            <input type="radio" name="field_36_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_36_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                            <span class="text-sm text-gray-700">YES</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                            <input type="radio" name="field_36_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_36_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                            <span class="text-sm text-gray-700">NO</span>
                                        </label>
                                    </div>
                                    @error('field_36_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    <div id="field_36_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_36_conviction_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_36_conviction_details', $questionnaire->field_36_conviction_details) }}</textarea>
                                        @error('field_36_conviction_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Question 37: Separation from service -->
                            <div class="border-t border-gray-200 pt-4 question-group" data-question="field_37_yes_no">
                                <h3 class="text-sm font-medium text-gray-900 mb-3">
                                    37. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?
                                    <span class="text-red-500">*</span>
                                </h3>
                                <div class="space-y-4">
                                    <div class="flex space-x-6 radio-group" data-required="true">
                                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                            <input type="radio" name="field_37_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_37_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                            <span class="text-sm text-gray-700">YES</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                            <input type="radio" name="field_37_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_37_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                            <span class="text-sm text-gray-700">NO</span>
                                        </label>
                                    </div>
                                    @error('field_37_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    <div id="field_37_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_37_separation_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_37_separation_details', $questionnaire->field_37_separation_details) }}</textarea>
                                        @error('field_37_separation_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Question 38: Election Candidacy and Resignation -->
                            <div class="border-t border-gray-200 pt-4 question-group" data-question="field_38a_yes_no">
                                <h3 class="text-sm font-medium text-gray-900 mb-3">
                                    38. Please answer the following questions:
                                    <span class="text-red-500">*</span>
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_38a_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_38a_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_38a_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_38a_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_38a_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div id="field_38a_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_36_candidate_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_36_candidate_details', $questionnaire->field_36_candidate_details) }}</textarea>
                                        @error('field_36_candidate_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">b. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_38b_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_38b_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_38b_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_38b_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_38b_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div id="field_38b_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_37_resignation_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_37_resignation_details', $questionnaire->field_37_resignation_details) }}</textarea>
                                        @error('field_37_resignation_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Question 39: Immigrant Status -->
                            <div class="border-t border-gray-200 pt-4 question-group" data-question="field_39_yes_no">
                                <h3 class="text-sm font-medium text-gray-900 mb-3">
                                    39. Have you acquired the status of an immigrant or permanent resident of another country?
                                    <span class="text-red-500">*</span>
                                </h3>
                                <div class="space-y-4">
                                    <div class="flex space-x-6 radio-group" data-required="true">
                                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                            <input type="radio" name="field_39_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_39_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                            <span class="text-sm text-gray-700">YES</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                            <input type="radio" name="field_39_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_39_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                            <span class="text-sm text-gray-700">NO</span>
                                        </label>
                                    </div>
                                    @error('field_39_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    <div id="field_39_details" style="display: none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">If YES, give details:   </label>
                                        <textarea name="field_39_immigrant_details" placeholder="If YES, give details:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" rows="2">{{ old('field_39_immigrant_details', $questionnaire->field_39_immigrant_details) }}</textarea>
                                        @error('field_39_immigrant_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Question 40: Indigenous/PWD/Solo Parent status -->
                            <div class="border-t border-gray-200 pt-4 question-group" data-question="field_40a_yes_no">
                                <h3 class="text-sm font-medium text-gray-900 mb-3">
                                    40. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:
                                    <span class="text-red-500">*</span>
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">a. Are you a member of any indigenous group?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_40a_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_40a_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_40a_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_40a_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_40a_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        <div id="field_40a_details" style="display: none;">
                                            <label class="block text-sm font-medium text-gray-700 mb-1 mt-2">If YES, please specify:   </label>
                                            <input type="text" name="field_40_indigenous_details" placeholder="If YES, please specify:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('field_40_indigenous_details', $questionnaire->field_40_indigenous_details) }}">
                                            @error('field_40_indigenous_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">b. Are you a person with disability?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_40b_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_40b_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_40b_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_40b_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_40b_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        <div id="field_40b_details" style="display: none;">
                                            <label class="block text-sm font-medium text-gray-700 mb-1 mt-2">If YES, please specify ID No:   </label>
                                            <input type="text" name="field_40_pwd_details" placeholder="If YES, please specify ID No:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('field_40_pwd_details', $questionnaire->field_40_pwd_details) }}">
                                            @error('field_40_pwd_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-600 mb-2">c. Are you a solo parent?</p>
                                        <div class="flex space-x-6 radio-group" data-required="true">
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_40c_yes_no" value="1" {{ $questionnaire->exists && $questionnaire->field_40c_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">YES</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                                                <input type="radio" name="field_40c_yes_no" value="0" {{ $questionnaire->exists && !$questionnaire->field_40c_yes_no ? 'checked' : '' }} required class="mr-2" aria-required="true">
                                                <span class="text-sm text-gray-700">NO</span>
                                            </label>
                                        </div>
                                        @error('field_40c_yes_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        <div id="field_40c_details" style="display: none;">
                                            <label class="block text-sm font-medium text-gray-700 mb-1 mt-2">If YES, please specify ID No:   </label>
                                            <input type="text" name="field_40_solo_parent_details" placeholder="If YES, please specify ID No:   " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('field_40_solo_parent_details', $questionnaire->field_40_solo_parent_details) }}">
                                            @error('field_40_solo_parent_details') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end p-6 bg-gray-50">
                        <x-primary-button>
                            {{ __('Save and Update Declarations') }}
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
