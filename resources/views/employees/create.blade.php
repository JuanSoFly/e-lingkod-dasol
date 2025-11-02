<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Employee') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Employee Information</h3>
                <p class="text-sm text-gray-500 mt-1">Add a new employee to the system</p>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('employees.store') }}" id="employeeForm" class="space-y-6">
                    @csrf

                    <!-- Loading Overlay -->
                    <div id="loadingOverlay" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center">
                        <div class="bg-white p-6 rounded-lg shadow-xl">
                            <div class="flex items-center space-x-3">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                                <span class="text-gray-700">Creating employee...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-base font-medium text-gray-900">Personal Information</h4>
                            <p class="text-sm text-gray-500 mt-1">Basic personal details of the employee</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Employee Number (Auto-generated) -->
                            <input type="hidden" name="employee_number" value="" />
                            <div>
                                <x-input-label for="employee_number_display" :value="__('Employee Number')" />
                                <x-text-input
                                    id="employee_number_display"
                                    class="block mt-1 w-full bg-gray-50"
                                    type="text"
                                    value="Auto-generated on save"
                                    readonly
                                />
                                <p class="text-xs text-gray-500 mt-1">Unique employee ID will be generated automatically</p>
                            </div>

                            <!-- First Name -->
                            <div>
                                <x-input-label for="first_name" :value="__('First Name')" />
                                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>

                            <!-- Middle Name -->
                            <div>
                                <x-input-label for="middle_name" :value="__('Middle Name')" />
                                <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name" :value="old('middle_name')" />
                                <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <x-input-label for="last_name" :value="__('Last Name')" />
                                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>

                             <!-- Email -->
                             <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <!-- Contact Number -->
                            <div>
                                <x-input-label for="contact_number" :value="__('Contact Number')" />
                                <x-text-input id="contact_number" class="block mt-1 w-full" type="text" name="contact_number" :value="old('contact_number')" required />
                                <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                            </div>

                        </div>

                        <!-- Address -->
                        <div>
                            <x-input-label for="address" :value="__('Address')" />
                            <x-text-input id="address" class="block mt-1 w-full" type="text" name="address" :value="old('address')" required />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Employment Information Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-base font-medium text-gray-900">Employment Information</h4>
                            <p class="text-sm text-gray-500">Job position and employment details</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Position -->
                            <div>
                                <x-input-label for="position" :value="__('Position')" />
                                <x-text-input id="position" class="block mt-1 w-full" type="text" name="position" :value="old('position')" required />
                                <x-input-error :messages="$errors->get('position')" class="mt-2" />
                            </div>

                            <!-- Office -->
                            <div>
                                <x-input-label for="office_id" :value="__('Office')" />
                                <select id="office_id" name="office_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Office</option>
                                    @foreach($offices as $office)
                                        <option value="{{ $office->id }}" {{ old('office_id') == $office->id ? 'selected' : '' }}>
                                            {{ $office->name }} ({{ $office->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('office_id')" class="mt-2" />
                            </div>

                            <!-- Employment Status -->
                            <div>
                                <x-input-label for="employment_status" :value="__('Employment Status')" />
                                <select id="employment_status" name="employment_status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Employment Status</option>
                                    <option value="regular" {{ old('employment_status') == 'regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="probationary" {{ old('employment_status') == 'probationary' ? 'selected' : '' }}>Probationary</option>
                                    <option value="contractual" {{ old('employment_status') == 'contractual' ? 'selected' : '' }}>Contractual</option>
                                    <option value="casual" {{ old('employment_status') == 'casual' ? 'selected' : '' }}>Casual</option>
                                    <option value="job-order" {{ old('employment_status') == 'job-order' ? 'selected' : '' }}>Job Order</option>
                                </select>
                                <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
                            </div>

                            <!-- Date Hired -->
                            <div>
                                <x-input-label for="date_hired" :value="__('Date Hired')" />
                                <x-text-input id="date_hired" class="block mt-1 w-full" type="date" name="date_hired" :value="old('date_hired')" required />
                                <x-input-error :messages="$errors->get('date_hired')" class="mt-2" />
                            </div>

                            <!-- Salary Grade -->
                            <div>
                                <x-input-label for="salary_grade" :value="__('Salary Grade')" />
                                <x-text-input id="salary_grade" class="block mt-1 w-full" type="number" name="salary_grade" :value="old('salary_grade')" min="1" max="33" />
                                <x-input-error :messages="$errors->get('salary_grade')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Government salary grade (1-33)</p>
                            </div>

                            <!-- Step Increment -->
                            <div>
                                <x-input-label for="step_increment" :value="__('Step Increment')" />
                                <x-text-input id="step_increment" class="block mt-1 w-full" type="number" name="step_increment" :value="old('step_increment')" min="1" max="8" />
                                <x-input-error :messages="$errors->get('step_increment')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Step increment (1-8)</p>
                            </div>

                            <!-- Basic Salary (NEW FIELD) -->
                            <div>
                                <x-input-label for="basic_salary" :value="__('Basic Salary')" />
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">₱</span>
                                    </div>
                                    <x-text-input
                                        id="basic_salary"
                                        class="block mt-1 w-full pl-8"
                                        type="number"
                                        name="basic_salary"
                                        :value="old('basic_salary', '0.00')"
                                        step="0.01"
                                        min="0"
                                        required
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('basic_salary')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Monthly basic salary in Philippine Peso</p>
                            </div>

                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-base font-medium text-gray-900">Additional Information</h4>
                            <p class="text-sm text-gray-500">Personal details and demographics</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Birth Date -->
                            <div>
                                <x-input-label for="birth_date" :value="__('Birth Date')" />
                                <x-text-input id="birth_date" class="block mt-1 w-full" type="date" name="birth_date" :value="old('birth_date')" required />
                                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
                            </div>

                            <!-- Gender -->
                            <div>
                                <x-input-label for="gender" :value="__('Gender')" />
                                <select id="gender" name="gender" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                            </div>

                            <!-- Civil Status -->
                            <div>
                                <x-input-label for="civil_status" :value="__('Civil Status')" />
                                <select id="civil_status" name="civil_status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Civil Status</option>
                                    <option value="Single" {{ old('civil_status') == 'Single' ? 'selected' : '' }}>Single</option>
                                    <option value="Married" {{ old('civil_status') == 'Married' ? 'selected' : '' }}>Married</option>
                                    <option value="Divorced" {{ old('civil_status') == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                    <option value="Widowed" {{ old('civil_status') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                                    <option value="Separated" {{ old('civil_status') == 'Separated' ? 'selected' : '' }}>Separated</option>
                                </select>
                                <x-input-error :messages="$errors->get('civil_status')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <div class="text-sm text-gray-500">
                            All fields marked with an asterisk (*) are required.
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('employees.index') }}">
                                <x-secondary-button>
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </a>
                            <x-primary-button type="submit" id="submitBtn">
                                {{ __('Save Employee') }}
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages Modal -->
    <div id="messageModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="flex items-center space-x-3">
                <div id="messageIcon"></div>
                <div>
                    <h3 id="messageTitle" class="text-lg font-medium text-gray-900"></h3>
                    <p id="messageText" class="text-sm text-gray-500 mt-1"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="closeMessageModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Form submission handling
        document.getElementById('employeeForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const loadingOverlay = document.getElementById('loadingOverlay');

            // Show loading state
            submitBtn.disabled = true;
            loadingOverlay.classList.remove('hidden');

            try {
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('Success!', data.message || 'Employee created successfully.', 'success');
                    setTimeout(() => {
                        window.location.href = '/employees';
                    }, 2000);
                } else {
                    showMessage('Error', data.message || 'Failed to create employee.', 'error');

                    // Handle validation errors
                    if (data.errors) {
                        // Display validation errors next to fields
                        Object.keys(data.errors).forEach(field => {
                            const input = document.querySelector(`[name="${field}"]`);
                            if (input) {
                                const errorDiv = input.closest('div').querySelector('.text-red-600');
                                if (errorDiv) {
                                    errorDiv.textContent = data.errors[field][0];
                                }
                            }
                        });
                    }
                }
            } catch (error) {
                console.error('Form submission error:', error);
                showMessage('Error', 'An unexpected error occurred. Please try again.', 'error');
            } finally {
                // Hide loading state
                submitBtn.disabled = false;
                loadingOverlay.classList.add('hidden');
            }
        });

        // Message modal functions
        function showMessage(title, text, type) {
            const modal = document.getElementById('messageModal');
            const titleElement = document.getElementById('messageTitle');
            const textElement = document.getElementById('messageText');
            const iconElement = document.getElementById('messageIcon');

            titleElement.textContent = title;
            textElement.textContent = text;

            // Set icon based on type
            if (type === 'success') {
                iconElement.innerHTML = '<div class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></div>';
            } else {
                iconElement.innerHTML = '<div class="flex-shrink-0 w-6 h-6 bg-red-100 rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></div>';
            }

            modal.classList.remove('hidden');
        }

        function closeMessageModal() {
            document.getElementById('messageModal').classList.add('hidden');
        }

        // Employee number is now auto-generated in the backend, no need to pre-fetch

        // Real-time validation
        document.querySelectorAll('input[required], select[required]').forEach(field => {
            field.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('border-red-300');
                } else {
                    this.classList.remove('border-red-300');
                }
            });
        });

        // Format currency input for basic salary
        document.getElementById('basic_salary').addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value) && value >= 0) {
                this.value = value.toFixed(2);
            }
        });
    </script>
    @endpush
</x-app-layout>
