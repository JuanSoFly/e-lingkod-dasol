<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Employee') }}: {{ $employee->first_name }} {{ $employee->last_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('employees.update', $employee->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Employee Number -->
                            <div>
                                <x-input-label for="employee_number" :value="__('Employee Number')" />
                                <x-text-input id="employee_number" class="block mt-1 w-full" type="text" name="employee_number" :value="old('employee_number', $employee->employee_number)" required autofocus />
                                <x-input-error :messages="$errors->get('employee_number')" class="mt-2" />
                            </div>

                            <!-- First Name -->
                            <div>
                                <x-input-label for="first_name" :value="__('First Name')" />
                                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name', $employee->first_name)" required />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>

                            <!-- Middle Name -->
                            <div>
                                <x-input-label for="middle_name" :value="__('Middle Name')" />
                                <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name" :value="old('middle_name', $employee->middle_name)" />
                                <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <x-input-label for="last_name" :value="__('Last Name')" />
                                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name', $employee->last_name)" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>

                             <!-- Email -->
                             <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $employee->email)" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <!-- Contact Number -->
                            <div>
                                <x-input-label for="contact_number" :value="__('Contact Number')" />
                                <x-text-input id="contact_number" class="block mt-1 w-full" type="text" name="contact_number" :value="old('contact_number', $employee->contact_number)" required />
                                <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <x-input-label for="address" :value="__('Address')" />
                                <x-text-input id="address" class="block mt-1 w-full" type="text" name="address" :value="old('address', $employee->address)" required />
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>

                            <!-- Birth Date -->
                            <div>
                                <x-input-label for="birth_date" :value="__('Birth Date')" />
                                <x-text-input id="birth_date" class="block mt-1 w-full" type="date" name="birth_date" :value="old('birth_date', $employee->birth_date?->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
                            </div>

                            <!-- Gender -->
                            <div>
                                <x-input-label for="gender" :value="__('Gender')" />
                                <select id="gender" name="gender" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="Male" @selected(old('gender', $employee->gender) == 'Male')>Male</option>
                                    <option value="Female" @selected(old('gender', $employee->gender) == 'Female')>Female</option>
                                </select>
                                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                            </div>

                            <!-- Civil Status -->
                            <div>
                                <x-input-label for="civil_status" :value="__('Civil Status')" />
                                <x-text-input id="civil_status" class="block mt-1 w-full" type="text" name="civil_status" :value="old('civil_status', $employee->civil_status)" required />
                                <x-input-error :messages="$errors->get('civil_status')" class="mt-2" />
                            </div>

                            <!-- Position -->
                            <div>
                                <x-input-label for="position" :value="__('Position')" />
                                <x-text-input id="position" class="block mt-1 w-full" type="text" name="position" :value="old('position', $employee->position)" required />
                                <x-input-error :messages="$errors->get('position')" class="mt-2" />
                            </div>

                            <!-- Office -->
                            <div>
                                <x-input-label for="office_id" :value="__('Office')" />
                                <select id="office_id" name="office_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Office</option>
                                    @foreach($offices as $office)
                                        <option value="{{ $office->id }}" {{ old('office_id', $employee->office_id ?? null) == $office->id ? 'selected' : '' }}>
                                            {{ $office->name }} ({{ $office->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('office_id')" class="mt-2" />
                            </div>

                            <!-- Work Calendar -->
                            <div>
                                <x-input-label for="work_calendar_id" :value="__('Work Calendar')" />
                                <select id="work_calendar_id" name="work_calendar_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Work Calendar</option>
                                    @foreach($workCalendars as $calendar)
                                        <option value="{{ $calendar->id }}" {{ old('work_calendar_id', $employee->work_calendar_id ?? null) == $calendar->id ? 'selected' : '' }}>
                                            {{ $calendar->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Determines how working days are counted for leave computations.</p>
                                <x-input-error :messages="$errors->get('work_calendar_id')" class="mt-2" />
                            </div>

                            <!-- Employment Status -->
                            <div>
                                <x-input-label for="employment_status" :value="__('Employment Status')" />
                                <x-text-input id="employment_status" class="block mt-1 w-full" type="text" name="employment_status" :value="old('employment_status', $employee->employment_status)" required />
                                <x-input-error :messages="$errors->get('employment_status')" class="mt-2" />
                            </div>

                            <!-- Date Hired -->
                            <div>
                                <x-input-label for="date_hired" :value="__('Date Hired')" />
                                <x-text-input id="date_hired" class="block mt-1 w-full" type="date" name="date_hired" :value="old('date_hired', $employee->date_hired?->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('date_hired')" class="mt-2" />
                            </div>

                            <!-- Salary Grade -->
                            <div>
                                <x-input-label for="salary_grade" :value="__('Salary Grade')" />
                                <x-text-input id="salary_grade" class="block mt-1 w-full" type="number" name="salary_grade" :value="old('salary_grade', $employee->salary_grade)" required />
                                <x-input-error :messages="$errors->get('salary_grade')" class="mt-2" />
                            </div>

                             <!-- Step Increment -->
                             <div>
                                <x-input-label for="step_increment" :value="__('Step Increment')" />
                                <x-text-input id="step_increment" class="block mt-1 w-full" type="number" name="step_increment" :value="old('step_increment', $employee->step_increment)" required />
                                <x-input-error :messages="$errors->get('step_increment')" class="mt-2" />
                            </div>

                            <!-- Basic Salary -->
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
                                        :value="old('basic_salary', $employee->basic_salary)"
                                        step="0.01"
                                        min="0"
                                        required
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('basic_salary')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Monthly basic salary in Philippine Peso</p>
                            </div>

                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('employees.index') }}">
                                <x-secondary-button class="me-3">
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </a>
                            <x-primary-button>
                                {{ __('Update Employee') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
