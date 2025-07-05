<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Government Benefit Enrollment') }}
            </h2>
            <a href="{{ route('benefits.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Benefits
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('benefits.store') }}" class="space-y-6">
                        @csrf

                        <!-- Employee Selection -->
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee <span class="text-red-500">*</span></label>
                            <select name="employee_id" id="employee_id" required 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('employee_id') border-red-500 @enderror">
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->last_name }}, {{ $employee->first_name }} {{ $employee->middle_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Benefit Type -->
                        <div>
                            <label for="benefit_type" class="block text-sm font-medium text-gray-700">Benefit Type <span class="text-red-500">*</span></label>
                            <select name="benefit_type" id="benefit_type" required 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('benefit_type') border-red-500 @enderror">
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
                            @error('benefit_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Member Number -->
                        <div>
                            <label for="member_number" class="block text-sm font-medium text-gray-700">Member Number <span class="text-red-500">*</span></label>
                            <input type="text" name="member_number" id="member_number" value="{{ old('member_number') }}" required
                                   placeholder="Enter member number (e.g., GSIS-1234567)"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('member_number') border-red-500 @enderror">
                            @error('member_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Enrollment Date -->
                        <div>
                            <label for="enrollment_date" class="block text-sm font-medium text-gray-700">Enrollment Date <span class="text-red-500">*</span></label>
                            <input type="date" name="enrollment_date" id="enrollment_date" value="{{ old('enrollment_date') }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('enrollment_date') border-red-500 @enderror">
                            @error('enrollment_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Enrollment Status -->
                        <div>
                            <label for="enrollment_status" class="block text-sm font-medium text-gray-700">Enrollment Status <span class="text-red-500">*</span></label>
                            <select name="enrollment_status" id="enrollment_status" required 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('enrollment_status') border-red-500 @enderror">
                                <option value="active" {{ old('enrollment_status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('enrollment_status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="pending" {{ old('enrollment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="suspended" {{ old('enrollment_status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                            @error('enrollment_status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Coverage Type -->
                        <div>
                            <label for="coverage_type" class="block text-sm font-medium text-gray-700">Coverage Type</label>
                            <select name="coverage_type" id="coverage_type" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('coverage_type') border-red-500 @enderror">
                                <option value="basic" {{ old('coverage_type') == 'basic' ? 'selected' : '' }}>Basic</option>
                                <option value="premium" {{ old('coverage_type') == 'premium' ? 'selected' : '' }}>Premium</option>
                                <option value="dependent" {{ old('coverage_type') == 'dependent' ? 'selected' : '' }}>Dependent</option>
                                <option value="family" {{ old('coverage_type') == 'family' ? 'selected' : '' }}>Family</option>
                            </select>
                            @error('coverage_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Coverage Amount -->
                        <div>
                            <label for="coverage_amount" class="block text-sm font-medium text-gray-700">Coverage Amount (PHP)</label>
                            <input type="number" name="coverage_amount" id="coverage_amount" value="{{ old('coverage_amount') }}" 
                                   step="0.01" min="0" placeholder="0.00"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('coverage_amount') border-red-500 @enderror">
                            @error('coverage_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Information Panel -->
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <h4 class="text-sm font-medium text-blue-800 mb-2">Government Benefits Information</h4>
                            <div class="text-sm text-blue-700 space-y-1">
                                <p><strong>GSIS:</strong> For government employees - retirement, life insurance, and other benefits</p>
                                <p><strong>PhilHealth:</strong> Universal health coverage - medical and hospitalization benefits</p>
                                <p><strong>Pag-IBIG:</strong> Housing fund - savings, loans, and housing assistance</p>
                                <p><strong>SSS:</strong> For private sector employees - retirement, disability, and death benefits</p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end space-x-4 pt-4">
                            <a href="{{ route('benefits.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Benefit Enrollment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>