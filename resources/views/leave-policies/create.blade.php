<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Leave Policy') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Policy Configuration</h3>
                <p class="text-sm text-gray-500 mt-1">Configure leave policy rules and eligibility</p>
            </div>
            <div class="p-6">
                <!-- Information Alert -->
                <div class="mb-8 p-4 border rounded-lg bg-blue-50 border-blue-100">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-blue-800">About Leave Policies</h4>
                            <p class="mt-1 text-sm text-blue-700">
                                These policies will automatically calculate leave balances, handle pro-rated allocations for new employees, and enforce approval workflows. Ensure all settings comply with government regulations.
                            </p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('leave-policies.store') }}" class="space-y-6">
                    @csrf

                    <!-- Basic Information Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-base font-medium text-gray-900">Basic Information</h4>
                            <p class="text-sm text-gray-500 mt-1">Essential details about the leave policy</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Policy Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" 
                                              :value="old('name')" required autofocus 
                                              placeholder="e.g., Vacation Leave for Regulars" />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="leave_type_id" :value="__('Leave Type')" />
                                <select id="leave_type_id" name="leave_type_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Leave Type</option>
                                    @foreach($leaveTypes as $leaveType)
                                        <option value="{{ $leaveType->id }}" {{ old('leave_type_id') == $leaveType->id ? 'selected' : '' }}>
                                            {{ $leaveType->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('leave_type_id')" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description" rows="3" 
                                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                          placeholder="Describe the purpose, eligibility, and restrictions of this policy">{{ old('description') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>
                        </div>
                    </div>

                    <!-- Policy Rules Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-base font-medium text-gray-900">Credit & Accrual Rules</h4>
                            <p class="text-sm text-gray-500 mt-1">Define how leave credits are earned and limited</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="max_days_per_year" :value="__('Maximum Days Per Year')" />
                                <x-text-input id="max_days_per_year" name="max_days_per_year" type="number" 
                                              step="0.5" min="0" class="mt-1 block w-full" 
                                              :value="old('max_days_per_year')" required 
                                              placeholder="e.g., 15" />
                                <p class="mt-1 text-xs text-gray-500">Total days allowed per calendar year</p>
                                <x-input-error class="mt-2" :messages="$errors->get('max_days_per_year')" />
                            </div>

                            <div>
                                <x-input-label for="accrual_method" :value="__('Accrual Method')" />
                                <select id="accrual_method" name="accrual_method" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Accrual Method</option>
                                    @foreach($accrualMethods as $method)
                                        <option value="{{ $method }}" {{ old('accrual_method') == $method ? 'selected' : '' }}>
                                            {{ ucfirst($method) }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">How leave credits are earned over time</p>
                                <x-input-error class="mt-2" :messages="$errors->get('accrual_method')" />
                            </div>

                            <div>
                                <x-input-label for="effective_start_date" :value="__('Effective Start Date')" />
                                <x-text-input id="effective_start_date" name="effective_start_date" type="date" 
                                              class="mt-1 block w-full" :value="old('effective_start_date')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('effective_start_date')" />
                            </div>
                        </div>
                    </div>

                    <!-- Eligibility Section -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-base font-medium text-gray-900">Employment Eligibility</h4>
                            <p class="text-sm text-gray-500 mt-1">Who can avail this leave policy?</p>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($employmentStatuses as $status)
                                <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ in_array($status, old('employment_statuses', [])) ? 'bg-blue-50 border-blue-200' : 'border-gray-200' }}">
                                    <div class="min-w-0 flex-1 text-sm">
                                        <div class="font-medium text-gray-700 select-none">
                                            {{ ucfirst($status) }}
                                        </div>
                                    </div>
                                    <div class="ml-3 flex items-center h-5">
                                        <input id="employment_status_{{ $status }}" name="employment_statuses[]" 
                                               type="checkbox" value="{{ $status }}" 
                                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                               {{ in_array($status, old('employment_statuses', [])) ? 'checked' : '' }}>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('employment_statuses')" />
                    </div>

                    <!-- Configuration Options -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 pb-4">
                            <h4 class="text-base font-medium text-gray-900">Configuration Options</h4>
                            <p class="text-sm text-gray-500 mt-1">Additional settings for this policy</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="is_active" name="is_active" type="checkbox" 
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_active" class="font-medium text-gray-700">Active Policy</label>
                                    <p class="text-gray-500">Enable this policy for immediate use.</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="is_government_policy" name="is_government_policy" type="checkbox" 
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                           {{ old('is_government_policy') ? 'checked' : '' }}>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_government_policy" class="font-medium text-gray-700">Government Mandated</label>
                                    <p class="text-gray-500">Flag as a government-regulated policy.</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="requires_approval" name="requires_approval" type="checkbox" 
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                           {{ old('requires_approval', true) ? 'checked' : '' }}>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="requires_approval" class="font-medium text-gray-700">Requires Approval</label>
                                    <p class="text-gray-500">Supervisor approval needed for applications.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <div class="text-xs text-gray-500 italic">
                            All fields marked with an asterisk (*) are required.
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('leave-policies.index') }}">
                                <x-secondary-button>
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </a>
                            <x-primary-button>
                                {{ __('Create Policy') }}
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Optional: Add visual toggle for checkbox cards
        document.querySelectorAll('input[type="checkbox"][name="employment_statuses[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const label = this.closest('label');
                if (this.checked) {
                    label.classList.add('bg-blue-50', 'border-blue-200');
                    label.classList.remove('border-gray-200');
                } else {
                    label.classList.remove('bg-blue-50', 'border-blue-200');
                    label.classList.add('border-gray-200');
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
