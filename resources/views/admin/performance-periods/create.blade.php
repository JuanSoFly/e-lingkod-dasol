<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Performance Period
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.performance-periods.store') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="year" :value="__('Year')" />
                                    <select id="year" name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                        @for($y = date('Y'); $y <= date('Y') + 2; $y++)
                                            <option value="{{ $y }}" {{ old('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                    <x-input-error :messages="$errors->get('year')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="semester" :value="__('Semester')" />
                                    <select id="semester" name="semester" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                        <option value="first" {{ old('semester') == 'first' ? 'selected' : '' }}>First Semester</option>
                                        <option value="second" {{ old('semester') == 'second' ? 'selected' : '' }}>Second Semester</option>
                                        <option value="annual" {{ old('semester') == 'annual' ? 'selected' : '' }}>Annual</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('semester')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-input-label for="name" :value="__('Period Name (Optional)')" />
                                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Leave blank for default format (e.g. 2026 - 1st Semester)" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">Custom name for the performance period. If left empty, it will be automatically generated.</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Date Configuration -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Date Configuration</h3>
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-800">
                                            Performance periods define the evaluation timeframe for OPCR submissions. Ensure dates don't overlap with existing periods.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="start_date" :value="__('Start Date')" />
                                    <input type="date"
                                           id="start_date"
                                           name="start_date"
                                           value="{{ old('start_date') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           required>
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="end_date" :value="__('End Date')" />
                                    <input type="date"
                                           id="end_date"
                                           name="end_date"
                                           value="{{ old('end_date') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           required>
                                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Date Preview -->
                            <div class="mt-4 p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Period Duration:</span>
                                    <span id="duration-preview" class="text-gray-900">Please select dates</span>
                                </p>
                            </div>
                        </div>

                        <!-- Initial Status -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Initial Status</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                        <option value="upcoming" {{ old('status', 'upcoming') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500">
                                        <span class="font-medium">Upcoming:</span> Not yet started, will be activated manually<br>
                                        <span class="font-medium">Active:</span> Currently accepting OPCR submissions<br>
                                        <span class="font-medium">Inactive:</span> Suspended, not accepting submissions
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Settings</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="notify_department_heads" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">Notify department heads when period is activated</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="send_reminders" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">Send automatic reminders before deadline</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="{{ route('admin.performance-periods.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Create Period
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Update duration preview when dates change
        function updateDurationPreview() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const preview = document.getElementById('duration-preview');

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);

                if (end <= start) {
                    preview.textContent = 'End date must be after start date';
                    preview.className = 'text-red-600 font-medium';
                    return;
                }

                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                const months = Math.floor(days / 30);
                const remainingDays = days % 30;

                let durationText = '';
                if (months > 0) {
                    durationText = `${months} month${months > 1 ? 's' : ''}`;
                    if (remainingDays > 0) {
                        durationText += ` ${remainingDays} day${remainingDays > 1 ? 's' : ''}`;
                    }
                } else {
                    durationText = `${days} day${days > 1 ? 's' : ''}`;
                }

                preview.textContent = durationText;
                preview.className = 'text-gray-900';
            } else {
                preview.textContent = 'Please select dates';
                preview.className = 'text-gray-500';
            }
        }

        // Add event listeners
        document.getElementById('start_date').addEventListener('change', updateDurationPreview);
        document.getElementById('end_date').addEventListener('change', updateDurationPreview);

        // Initialize preview
        updateDurationPreview();

        // Auto-set end date based on typical semester duration
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDate = document.getElementById('end_date');

            if (startDate && !endDate.value) {
                const start = new Date(startDate);
                const semester = document.getElementById('semester').value;

                // Set default end date based on semester type
                let defaultEnd = new Date(start);
                if (semester === 'annual') {
                    defaultEnd.setFullYear(start.getFullYear() + 1);
                    defaultEnd.setMonth(11); // December
                    defaultEnd.setDate(31);
                } else {
                    defaultEnd.setMonth(start.getMonth() + 6); // ~6 months for semester
                }

                endDate.value = defaultEnd.toISOString().split('T')[0];
                updateDurationPreview();
            }
        });

        // Validate no overlapping periods
        document.querySelector('form').addEventListener('submit', async function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            const status = document.getElementById('status').value;

            if (endDate <= startDate) {
                e.preventDefault();
                alert('End date must be after start date');
                return;
            }

            if (status === 'active') {
                // Check if start date is in the past
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                startDate.setHours(0, 0, 0, 0);

                if (startDate > today) {
                    const message = 'You are setting this period as active, but the start date is in the future. The period will be activated on the start date. Continue?';
                    const confirmed = window.confirmDialog
                        ? await window.confirmDialog({
                            title: 'Activate Future Period',
                            message,
                            confirmLabel: 'Continue',
                            cancelLabel: 'Cancel'
                        })
                        : window.confirm(message);

                    if (!confirmed) {
                        e.preventDefault();
                        return;
                    }
                }
            }
        });
    </script>
</x-app-layout>
