<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Government Benefit Enrollment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('benefits.store') }}" class="space-y-6">
                        @csrf

                        <!-- Display General Errors -->
                        @if ($errors->any())
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700">
                                            {{ $errors->first('error') ?: 'Please check the form for errors.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Employee Selection -->
                            <div>
                                <x-input-label for="employee_id" :value="__('Employee')" />
                                <select name="employee_id" id="employee_id" required 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->last_name }}, {{ $employee->first_name }} {{ $employee->middle_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
                            </div>

                            <!-- Benefit Type -->
                            <div>
                                <x-input-label for="benefit_type" :value="__('Benefit Type')" />
                                <select name="benefit_type" id="benefit_type" required 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Benefit Type</option>
                                    @foreach($benefitTypes as $type)
                                        <option value="{{ $type }}" {{ old('benefit_type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                            @if($type == 'GSIS') - Government Service Insurance System
                                            @elseif($type == 'PhilHealth') - Universal Health Coverage
                                            @elseif($type == 'Pag-IBIG') - Home Development Mutual Fund
                                            @elseif($type == 'SSS') - Social Security System
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('benefit_type')" class="mt-2" />
                            </div>

                            <!-- Member Number -->
                            <div>
                                <x-input-label for="member_number" :value="__('Member Number')" />
                                <x-text-input id="member_number" class="block mt-1 w-full" type="text" name="member_number" :value="old('member_number')" required placeholder="Enter member number (e.g., GSIS-1234567)" />
                                <x-input-error :messages="$errors->get('member_number')" class="mt-2" />
                            </div>

                            <!-- Enrollment Date -->
                            <div>
                                <x-input-label for="enrollment_date" :value="__('Enrollment Date')" />
                                <x-text-input id="enrollment_date" class="block mt-1 w-full" type="date" name="enrollment_date" :value="old('enrollment_date')" required />
                                <x-input-error :messages="$errors->get('enrollment_date')" class="mt-2" />
                            </div>

                            <!-- Enrollment Status -->
                            <div>
                                <x-input-label for="enrollment_status" :value="__('Enrollment Status')" />
                                <select name="enrollment_status" id="enrollment_status" required 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="active" {{ old('enrollment_status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('enrollment_status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="pending" {{ old('enrollment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="suspended" {{ old('enrollment_status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                                <x-input-error :messages="$errors->get('enrollment_status')" class="mt-2" />
                            </div>

                            <!-- Coverage Type -->
                            <div>
                                <x-input-label for="coverage_type" :value="__('Coverage Type')" />
                                <select name="coverage_type" id="coverage_type" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="basic" {{ old('coverage_type') == 'basic' ? 'selected' : '' }}>Basic</option>
                                    <option value="premium" {{ old('coverage_type') == 'premium' ? 'selected' : '' }}>Premium</option>
                                    <option value="dependent" {{ old('coverage_type') == 'dependent' ? 'selected' : '' }}>Dependent</option>
                                    <option value="family" {{ old('coverage_type') == 'family' ? 'selected' : '' }}>Family</option>
                                </select>
                                <x-input-error :messages="$errors->get('coverage_type')" class="mt-2" />
                            </div>

                            <!-- Coverage Amount -->
                            <div>
                                <x-input-label for="coverage_amount" :value="__('Coverage Amount (PHP)')" />
                                <x-text-input id="coverage_amount" class="block mt-1 w-full" type="number" name="coverage_amount" :value="old('coverage_amount')" step="0.01" min="0" placeholder="0.00" />
                                <x-input-error :messages="$errors->get('coverage_amount')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Information Panel -->
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mt-6">
                            <h4 class="text-sm font-medium text-blue-800 mb-2">Government Benefits Information</h4>
                            <div class="text-sm text-blue-700 space-y-1">
                                <p><strong>GSIS:</strong> For government employees - retirement, life insurance, and other benefits</p>
                                <p><strong>PhilHealth:</strong> Universal health coverage - medical and hospitalization benefits</p>
                                <p><strong>Pag-IBIG:</strong> Housing fund - savings, loans, and housing assistance</p>
                                <p><strong>SSS:</strong> For private sector employees - retirement, disability, and death benefits</p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-100">
                            <a href="{{ route('benefits.index') }}">
                                <x-secondary-button>
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </a>
                            <x-primary-button>
                                {{ __('Create Benefit Enrollment') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>