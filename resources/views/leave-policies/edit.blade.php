<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Leave Policy') }}
            </h2>
            <div class="space-x-2">
                <a href="{{ route('leave-policies.show', $leavePolicy) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    View Policy
                </a>
                <a href="{{ route('leave-policies.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Policies
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    
                    <!-- Information Header -->
                    <div class="mb-8 p-4 border rounded-lg bg-yellow-50">
                        <h3 class="font-medium text-yellow-700 mb-2">Editing Leave Policy: {{ $leavePolicy->name }}</h3>
                        <p class="text-sm text-yellow-600">
                            Changes to this policy will affect future leave calculations but will not retroactively modify existing leave records.
                            {{ $leavePolicy->is_government_policy ? 'This is a government-mandated policy - ensure compliance with regulations.' : '' }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('leave-policies.update', $leavePolicy) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Basic Information -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="name" :value="__('Policy Name')" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" 
                                                  :value="old('name', $leavePolicy->name)" required autofocus 
                                                  placeholder="e.g., Vacation Leave for Permanent Employees" />
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                </div>

                                <div>
                                    <x-input-label for="leave_type_id" :value="__('Leave Type')" />
                                    <select id="leave_type_id" name="leave_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        <option value="">Select Leave Type</option>
                                        @foreach($leaveTypes as $leaveType)
                                            <option value="{{ $leaveType->id }}" 
                                                    {{ old('leave_type_id', $leavePolicy->leave_type_id) == $leaveType->id ? 'selected' : '' }}>
                                                {{ $leaveType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('leave_type_id')" />
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description" rows="3" 
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                          placeholder="Describe the purpose and conditions of this leave policy">{{ old('description', $leavePolicy->description) }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>
                        </div>

                        <!-- Policy Rules -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Policy Rules</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="max_days_per_year" :value="__('Maximum Days Per Year')" />
                                    <x-text-input id="max_days_per_year" name="max_days_per_year" type="number" 
                                                  step="0.5" min="0" class="mt-1 block w-full" 
                                                  :value="old('max_days_per_year', $leavePolicy->max_days_per_year)" required 
                                                  placeholder="e.g., 15" />
                                    <p class="mt-1 text-sm text-gray-600">Total days allowed per calendar year</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('max_days_per_year')" />
                                </div>

                                <div>
                                    <x-input-label for="accrual_method" :value="__('Accrual Method')" />
                                    <select id="accrual_method" name="accrual_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                        <option value="">Select Accrual Method</option>
                                        @foreach($accrualMethods as $method)
                                            <option value="{{ $method }}" 
                                                    {{ old('accrual_method', $leavePolicy->accrual_method) == $method ? 'selected' : '' }}>
                                                {{ ucfirst($method) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-sm text-gray-600">How leave credits are earned</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('accrual_method')" />
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-input-label for="effective_start_date" :value="__('Effective Start Date')" />
                                <x-text-input id="effective_start_date" name="effective_start_date" type="date" 
                                              class="mt-1 block w-full" 
                                              :value="old('effective_start_date', $leavePolicy->effective_start_date ? \Carbon\Carbon::parse($leavePolicy->effective_start_date)->format('Y-m-d') : '')" required />
                                <p class="mt-1 text-sm text-gray-600">When this policy becomes active</p>
                                <x-input-error class="mt-2" :messages="$errors->get('effective_start_date')" />
                            </div>
                        </div>

                        <!-- Employment Status -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Applicable Employment Status</h3>
                            <p class="text-sm text-gray-600 mb-4">Select which employment statuses this policy applies to:</p>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                @foreach($employmentStatuses as $status)
                                    <div class="flex items-center">
                                        <input id="employment_status_{{ $status }}" name="employment_statuses[]" 
                                               type="checkbox" value="{{ $status }}" 
                                               class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                               {{ in_array($status, old('employment_statuses', $leavePolicy->employment_statuses ?? [])) ? 'checked' : '' }}>
                                        <label for="employment_status_{{ $status }}" class="ml-2 text-sm text-gray-700">
                                            {{ ucfirst($status) }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('employment_statuses')" />
                        </div>

                        <!-- Policy Options -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Policy Options</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input id="is_active" name="is_active" type="checkbox" 
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                           {{ old('is_active', $leavePolicy->is_active) ? 'checked' : '' }}>
                                    <label for="is_active" class="ml-2 text-sm text-gray-700">
                                        <span class="font-medium">Active Policy</span>
                                        <p class="text-gray-600">Enable this policy for leave applications</p>
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="is_government_policy" name="is_government_policy" type="checkbox" 
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                           {{ old('is_government_policy', $leavePolicy->is_government_policy) ? 'checked' : '' }}>
                                    <label for="is_government_policy" class="ml-2 text-sm text-gray-700">
                                        <span class="font-medium">Government Policy</span>
                                        <p class="text-gray-600">This policy is mandated by government regulations</p>
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="requires_approval" name="requires_approval" type="checkbox" 
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                           {{ old('requires_approval', $leavePolicy->requires_approval) ? 'checked' : '' }}>
                                    <label for="requires_approval" class="ml-2 text-sm text-gray-700">
                                        <span class="font-medium">Requires Approval</span>
                                        <p class="text-gray-600">Leave applications must be approved by supervisors</p>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Policy Impact Warning -->
                        @if($leavePolicy->is_active)
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                            <h4 class="font-medium text-yellow-900 mb-2">⚠️ Policy Update Impact</h4>
                            <p class="text-sm text-yellow-800">
                                This policy is currently active and may affect existing leave calculations. 
                                Changes will apply to future leave applications but will not modify historical records.
                            </p>
                        </div>
                        @endif

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-4 pt-6">
                            <a href="{{ route('leave-policies.show', $leavePolicy) }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Leave Policy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>