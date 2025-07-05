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
                <form method="POST" action="{{ route('employees.store') }}" class="space-y-6">
                        @csrf

                    <!-- Personal Information Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-base font-medium text-gray-900">Personal Information</h4>
                            <p class="text-sm text-gray-500">Basic personal details of the employee</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Employee Number -->
                            <div>
                                <x-input-label for="employee_number" :value="__('Employee Number')" />
                                <x-text-input id="employee_number" class="block mt-1 w-full" type="text" name="employee_number" :value="old('employee_number')" required autofocus />
                                <x-input-error :messages="$errors->get('employee_number')" class="mt-2" />
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

                            <!-- Department -->
                            <div>
                                <x-input-label for="department" :value="__('Department')" />
                                <x-text-input id="department" class="block mt-1 w-full" type="text" name="department" :value="old('department')" required />
                                <x-input-error :messages="$errors->get('department')" class="mt-2" />
                            </div>

                            <!-- Employment Status -->
                            <div>
                                <x-input-label for="employment_status" :value="__('Employment Status')" />
                                <select id="employment_status" name="employment_status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Employment Status</option>
                                    <option value="Regular" {{ old('employment_status') == 'Regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="Contractual" {{ old('employment_status') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                                    <option value="Casual" {{ old('employment_status') == 'Casual' ? 'selected' : '' }}>Casual</option>
                                    <option value="Job Order" {{ old('employment_status') == 'Job Order' ? 'selected' : '' }}>Job Order</option>
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
                            <x-primary-button>
                                {{ __('Save Employee') }}
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>