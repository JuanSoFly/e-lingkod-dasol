<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Apply for Leave') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('leave-applications.store') }}">
                        @csrf
                        <!-- Leave Type -->
                        <div>
                            <x-input-label for="leave_type_id" :value="__('Leave Type')" />
                            <select id="leave_type_id" name="leave_type_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select a leave type</option>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->id }}" data-code="{{ $type->code }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('leave_type_id')" class="mt-2" />
                        </div>

                        <!-- Start Date -->
                        <div class="mt-4">
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <x-text-input id="start_date" class="block mt-1 w-full" type="date" name="start_date" :value="old('start_date')" required />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>

                         <!-- End Date -->
                        <div class="mt-4">
                            <x-input-label for="end_date" :value="__('End Date')" />
                            <x-text-input id="end_date" class="block mt-1 w-full" type="date" name="end_date" :value="old('end_date')" required />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>

                        <!-- Days Requested -->
                        <div class="mt-4">
                            <x-input-label for="days_requested" :value="__('Number of Days')" />
                            <x-text-input id="days_requested" class="block mt-1 w-full" type="number" step="0.5" name="days_requested" :value="old('days_requested')" required />
                            <x-input-error :messages="$errors->get('days_requested')" class="mt-2" />
                        </div>

                        <!-- Reason -->
                        <div class="mt-4">
                            <x-input-label for="reason" :value="__('Reason')" />
                            <textarea id="reason" name="reason" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>{{ old('reason') }}</textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>{{ __('Submit Application') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const leaveTypeSelect = document.getElementById('leave_type_id');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    const SICK_CODE = 'SL';
    const BACKDATE_DAYS = 30;
    const ADVANCE_DAYS = 30;

    const formatDate = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    const shiftDate = (date, days) => {
        const copy = new Date(date);
        copy.setDate(copy.getDate() + days);
        return copy;
    };

    const today = new Date();

    const applyBounds = () => {
        const selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
        const code = selectedOption?.dataset?.code;

        if (code === SICK_CODE) {
            const min = formatDate(shiftDate(today, -BACKDATE_DAYS));
            const max = formatDate(shiftDate(today, ADVANCE_DAYS));
            startDateInput.min = min;
            startDateInput.max = max;
            endDateInput.min = startDateInput.value || min;
            endDateInput.max = max;
        } else {
            const min = formatDate(today);
            startDateInput.min = min;
            startDateInput.max = '';
            endDateInput.max = '';
            endDateInput.min = startDateInput.value || min;
        }

        // Keep end date within bounds
        if (endDateInput.value && endDateInput.max && endDateInput.value > endDateInput.max) {
            endDateInput.value = endDateInput.max;
        }
        if (endDateInput.value && endDateInput.value < endDateInput.min) {
            endDateInput.value = endDateInput.min;
        }
    };

    leaveTypeSelect.addEventListener('change', applyBounds);
    startDateInput.addEventListener('change', () => {
        endDateInput.min = startDateInput.value || startDateInput.min;
        if (endDateInput.value && endDateInput.value < endDateInput.min) {
            endDateInput.value = endDateInput.min;
        }
    });

    applyBounds();
});
</script>
@endpush
