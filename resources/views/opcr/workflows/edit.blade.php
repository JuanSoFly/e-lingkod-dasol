<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit OPCR: {{ $workflow->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Edit Office Performance Commitment</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $workflow->office->name }} • {{ $workflow->period->year }} - {{ $workflow->period->semester }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 md:justify-end">
                            <a href="{{ route('opcr.workflows.show', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                {{ ucwords(str_replace('_', ' ', $workflow->workflow_state)) }}
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('opcr.workflows.update', $workflow) }}" id="opcr-edit-form">
                    @csrf
                    @method('PATCH')
                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <x-input-label for="title" value="OPCR Title" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ old('title', $workflow->title) }}" required />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div class="col-span-2">
                                <x-input-label for="description" value="Description (Optional)" />
                                <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $workflow->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Performance Targets Section -->
                        <div class="border-t pt-6">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Performance Targets</h3>
                                <div class="flex flex-wrap gap-3 md:justify-end">
                                    <button type="button" id="add-target-btn" class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Add Target
                                    </button>
                                </div>
                            </div>

                            <div id="targets-container" class="space-y-4">
                                @foreach($workflow->targets as $index => $target)
                                    <div class="target-row bg-gray-50 p-4 rounded-lg border border-gray-200" data-target-index="{{ $index }}">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium text-gray-900">Target {{ $index + 1 }}</h4>
                                            <button type="button" class="remove-target-btn text-red-600 hover:text-red-800 transition-colors" {{ $loop->first ? 'style="display: none;"' : '' }}>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <input type="hidden" name="targets[{{ $index }}][id]" value="{{ $target->id }}">

                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            <!-- MFO Selection -->
                                            <div class="lg:col-span-2">
                                                <x-input-label for="mfo_id_{{ $index }}" value="Major Final Output (MFO)" />
                                                <select id="mfo_id_{{ $index }}" name="targets[{{ $index }}][mfo_id]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mfo-select" data-office-id="{{ $workflow->office_id }}" required>
                                                    <option value="">Select MFO</option>
                                                    <!-- MFOs will be loaded via JavaScript -->
                                                </select>
                                                <x-input-error :messages="$errors->get('targets.'.$index.'.mfo_id')" class="mt-2" />
                                            </div>

                                            <!-- Success Indicator Selection -->
                                            <div class="lg:col-span-2">
                                                <x-input-label for="success_indicator_id_{{ $index }}" value="Success Indicator" />
                                                <select id="success_indicator_id_{{ $index }}" name="targets[{{ $index }}][success_indicator_id]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm success-indicator-select" data-mfo-id="{{ $target->mfo_id }}" required>
                                                    <option value="">Select MFO First</option>
                                                    <!-- Success indicators will be loaded via JavaScript -->
                                                </select>
                                                <x-input-error :messages="$errors->get('targets.'.$index.'.success_indicator_id')" class="mt-2" />
                                            </div>

                                            <!-- QET Targets -->
                                            <div>
                                                <x-input-label for="target_quantity_{{ $index }}" value="Target Quantity" />
                                                <input type="number" id="target_quantity_{{ $index }}" name="targets[{{ $index }}][target_quantity]" step="0.01" min="0" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value="{{ $target->target_quantity }}" placeholder="e.g., 100">
                                                <x-input-error :messages="$errors->get('targets.'.$index.'.target_quantity')" class="mt-2" />
                                            </div>

                                            <div>
                                                <x-input-label for="target_efficiency_{{ $index }}" value="Target Efficiency" />
                                                <input type="text" id="target_efficiency_{{ $index }}" name="targets[{{ $index }}][target_efficiency]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value="{{ $target->target_efficiency }}" placeholder="e.g., 95% accuracy">
                                                <x-input-error :messages="$errors->get('targets.'.$index.'.target_efficiency')" class="mt-2" />
                                            </div>

                                            <div class="lg:col-span-2">
                                                <x-input-label for="target_timeliness_{{ $index }}" value="Target Timeliness" />
                                                <input type="text" id="target_timeliness_{{ $index }}" name="targets[{{ $index }}][target_timeliness]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value="{{ $target->target_timeliness }}" placeholder="e.g., Within 5 working days">
                                                <x-input-error :messages="$errors->get('targets.'.$index.'.target_timeliness')" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($errors->has('targets'))
                                <div class="rounded-md bg-red-50 p-4 mt-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-red-800">Please fix the target validation errors</h3>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Form Actions -->
                        <div class="border-t pt-6">
                            <div class="flex items-center justify-end space-x-4">
                                <a href="{{ route('opcr.workflows.show', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancel
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Update OPCR Commitment
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden template for new target rows -->
    <template id="target-row-template">
        <div class="target-row bg-gray-50 p-4 rounded-lg border border-gray-200" data-target-index="__INDEX__">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">Target __NUMBER__</h4>
                <button type="button" class="remove-target-btn text-red-600 hover:text-red-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- MFO Selection -->
                <div class="lg:col-span-2">
                    <x-input-label for="mfo_id___INDEX__" value="Major Final Output (MFO)" />
                    <select id="mfo_id___INDEX__" name="targets[__INDEX__][mfo_id]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mfo-select" data-office-id="{{ $workflow->office_id }}" required>
                        <option value="">Select MFO</option>
                    </select>
                    <x-input-error :messages="$errors->get('targets.__INDEX__.mfo_id')" class="mt-2" />
                </div>

                <!-- Success Indicator Selection -->
                <div class="lg:col-span-2">
                    <x-input-label for="success_indicator_id___INDEX__" value="Success Indicator" />
                    <select id="success_indicator_id___INDEX__" name="targets[__INDEX__][success_indicator_id]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm success-indicator-select" data-mfo-id="" required disabled>
                        <option value="">First select an MFO</option>
                    </select>
                    <x-input-error :messages="$errors->get('targets.__INDEX__.success_indicator_id')" class="mt-2" />
                </div>

                <!-- QET Targets -->
                <div>
                    <x-input-label for="target_quantity___INDEX__" value="Target Quantity" />
                    <input type="number" id="target_quantity___INDEX__" name="targets[__INDEX__][target_quantity]" step="0.01" min="0" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="e.g., 100">
                    <x-input-error :messages="$errors->get('targets.__INDEX__.target_quantity')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="target_efficiency___INDEX__" value="Target Efficiency" />
                    <input type="text" id="target_efficiency___INDEX__" name="targets[__INDEX__][target_efficiency]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="e.g., 95% accuracy">
                    <x-input-error :messages="$errors->get('targets.__INDEX__.target_efficiency')" class="mt-2" />
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="target_timeliness___INDEX__" value="Target Timeliness" />
                    <input type="text" id="target_timeliness___INDEX__" name="targets[__INDEX__][target_timeliness]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="e.g., Within 5 working days">
                    <x-input-error :messages="$errors->get('targets.__INDEX__.target_timeliness')" class="mt-2" />
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const officeId = '{{ $workflow->office_id }}';
            let targetIndex = {{ $workflow->targets->count() }};
            const targetsContainer = document.getElementById('targets-container');
            const addTargetBtn = document.getElementById('add-target-btn');
            const targetRowTemplate = document.getElementById('target-row-template');
            let mfoData = {};

            // Load MFOs for the office
            loadMFOs(officeId);

            function loadMFOs(officeId) {
                fetch(`/api/offices/${officeId}/mfos`)
                    .then(response => response.json())
                    .then(data => {
                        mfoData = data;
                        updateMFOSelects();
                        // Load existing success indicators
                        loadExistingSuccessIndicators();
                    })
                    .catch(error => {
                        console.error('Error loading MFOs:', error);
                    });
            }

            // Update all MFO select elements
            function updateMFOSelects() {
                const mfoSelects = document.querySelectorAll('.mfo-select');
                mfoSelects.forEach(select => {
                    const currentMfoId = select.getAttribute('data-current-mfo-id');
                    select.innerHTML = '<option value="">Select MFO</option>';

                    Object.keys(mfoData).forEach(mfoId => {
                        const mfo = mfoData[mfoId];
                        const option = document.createElement('option');
                        option.value = mfoId;
                        option.textContent = mfo.full_code_path + ' - ' + mfo.title;
                        option.selected = currentMfoId == mfoId || select.value == mfoId;
                        select.appendChild(option);
                    });
                });
            }

            // Load existing success indicators
            function loadExistingSuccessIndicators() {
                const successIndicatorSelects = document.querySelectorAll('.success-indicator-select');
                successIndicatorSelects.forEach(select => {
                    const mfoId = select.getAttribute('data-mfo-id');
                    if (mfoId && mfoData[mfoId]) {
                        loadSuccessIndicators(select, mfoId);

                        // Set current selection if available
                        const currentIndicatorId = select.getAttribute('data-current-indicator-id');
                        if (currentIndicatorId) {
                            select.value = currentIndicatorId;
                        }
                    }
                });
            }

            // Add new target row
            addTargetBtn.addEventListener('click', function() {
                const template = targetRowTemplate.innerHTML;
                const newRow = template.replace(/__INDEX__/g, targetIndex);
                const newNumber = targetIndex + 1;
                const newContent = newRow.replace(/__NUMBER__/g, newNumber);

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = newContent;
                const newTargetRow = tempDiv.firstElementChild;

                targetsContainer.appendChild(newTargetRow);

                // Update MFO select for new row
                const newMfoSelect = newTargetRow.querySelector('.mfo-select');
                updateMFOSelects();

                // Setup event listeners for new row
                setupTargetRowEventListeners(newTargetRow);

                targetIndex++;
                updateRemoveButtons();
            });

            // Setup event listeners for target rows
            function setupTargetRowEventListeners(targetRow) {
                const mfoSelect = targetRow.querySelector('.mfo-select');
                const successIndicatorSelect = targetRow.querySelector('.success-indicator-select');
                const removeBtn = targetRow.querySelector('.remove-target-btn');

                if (mfoSelect) {
                    mfoSelect.addEventListener('change', function() {
                        const mfoId = this.value;
                        if (mfoId && mfoData[mfoId]) {
                            loadSuccessIndicators(successIndicatorSelect, mfoId);
                        } else {
                            successIndicatorSelect.innerHTML = '<option value="">Select MFO First</option>';
                            successIndicatorSelect.disabled = true;
                        }
                    });
                }

                if (removeBtn) {
                    removeBtn.addEventListener('click', function() {
                        targetRow.remove();
                        updateTargetNumbers();
                        updateRemoveButtons();
                    });
                }
            }

            // Load success indicators for selected MFO
            function loadSuccessIndicators(selectElement, mfoId) {
                selectElement.innerHTML = '<option value="">Loading...</option>';
                selectElement.disabled = true;

                if (mfoData[mfoId] && mfoData[mfoId].success_indicators) {
                    selectElement.innerHTML = '<option value="">Select Success Indicator</option>';
                    selectElement.disabled = false;

                    mfoData[mfoId].success_indicators.forEach(indicator => {
                        const option = document.createElement('option');
                        option.value = indicator.id;
                        option.textContent = indicator.code + ' - ' + indicator.title;
                        selectElement.appendChild(option);
                    });
                } else {
                    selectElement.innerHTML = '<option value="">No success indicators available</option>';
                    selectElement.disabled = true;
                }
            }

            // Update target numbers
            function updateTargetNumbers() {
                const targetRows = document.querySelectorAll('.target-row');
                targetRows.forEach((row, index) => {
                    const titleElement = row.querySelector('h4');
                    if (titleElement) {
                        titleElement.textContent = `Target ${index + 1}`;
                    }
                });
            }

            // Update remove buttons visibility
            function updateRemoveButtons() {
                const targetRows = document.querySelectorAll('.target-row');
                const removeButtons = document.querySelectorAll('.remove-target-btn');

                removeButtons.forEach(btn => {
                    btn.style.display = targetRows.length > 1 ? 'block' : 'none';
                });
            }

            // Setup existing rows
            const existingRows = document.querySelectorAll('.target-row');
            existingRows.forEach(row => {
                setupTargetRowEventListeners(row);
            });

            // Form validation
            document.getElementById('opcr-edit-form').addEventListener('submit', function(e) {
                const targetRows = document.querySelectorAll('.target-row');
                let hasValidTarget = false;

                targetRows.forEach(row => {
                    const mfoSelect = row.querySelector('.mfo-select');
                    const successIndicatorSelect = row.querySelector('.success-indicator-select');

                    if (mfoSelect.value && successIndicatorSelect.value) {
                        hasValidTarget = true;
                    }
                });

                if (!hasValidTarget) {
                    e.preventDefault();
                    alert('Please ensure at least one performance target has both MFO and Success Indicator selected.');
                }
            });
        });
    </script>
</x-app-layout>
