<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use App\Services\HolidayService;

class StoreEmployeeLeaveApplicationRequest extends FormRequest
{
    private const SICK_LEAVE_CODE = 'SL';
    private const SICK_BACKDATE_WINDOW_DAYS = 30;
    private const SICK_ADVANCE_WINDOW_DAYS = 30;

    /**
     * Normalize incoming values before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_draft' => $this->normalizeBoolean($this->input('is_draft', false)),
        ]);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('leave.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDraft = $this->input('is_draft', false);
        $isSickLeave = $this->isSickLeave();

        $minSickDate = now()->subDays(self::SICK_BACKDATE_WINDOW_DAYS)->toDateString();
        $maxSickDate = now()->addDays(self::SICK_ADVANCE_WINDOW_DAYS)->toDateString();

        return [
            // Leave type is always required (even for drafts)
            'leave_type_id' => ['required', 'exists:leave_types,id'],

            // Dates are flexible for drafts but required for submission
            'start_date' => array_filter([
                $isDraft ? 'nullable' : 'required',
                'date',
                $isSickLeave ? "after_or_equal:{$minSickDate}" : 'after_or_equal:today',
                $isSickLeave ? "before_or_equal:{$maxSickDate}" : null,
            ]),
            'end_date' => array_filter([
                $isDraft ? 'nullable' : 'required',
                'date',
                'after_or_equal:start_date',
                $isSickLeave ? "before_or_equal:{$maxSickDate}" : null,
            ]),

            // Days requested is calculated automatically, not input by user
            // Don't include it in validation since controller calculates it

            // Reason is flexible for drafts
            'reason' => $isDraft
                ? ['nullable', 'string', 'max:500']
                : ['required', 'string', 'max:500'],

            // Department head confirmation is not required for drafts but must be accepted for submissions
            'dept_head_informed' => $isDraft
                ? ['nullable', 'string', 'in:0,1']
                : ['required', 'accepted'],

            // Document uploads (optional for both submission and drafts)
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:2048'],

            // Draft flag
            'is_draft' => ['boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateMaternityLeaveEligibility($validator);
            $this->validatePaternityLeaveEligibility($validator);

            $isDraft = filter_var($this->input('is_draft', false), FILTER_VALIDATE_BOOLEAN);

            if (!$isDraft) {
                $this->validateWorkingPeriod($validator);
                $this->validateExistingPendingApplications($validator);
                $this->validateLeaveCredits($validator);
                $this->validateLeadTimes($validator);
                $this->validateMedicalDocumentation($validator);
            }
        });
    }

    /**
     * Validate maternity leave eligibility based on employee gender.
     */
    protected function validateMaternityLeaveEligibility($validator): void
    {
        $leaveTypeId = $this->input('leave_type_id');
        $employee = $this->user()->employee;

        // Skip validation if no employee linked or no leave type selected
        if (!$employee || !$leaveTypeId) {
            return;
        }

        // Get the leave type to check if it's maternity leave
        $leaveType = LeaveType::find($leaveTypeId);

        if ($leaveType && $leaveType->code === 'ML') {
            // Maternity leave (ML) is only available for female employees
            if (strtolower($employee->gender) !== 'female') {
                $validator->errors()->add('leave_type_id',
                    'Maternity leave is only available for female employees.');
            }
        }
    }

    /**
     * Validate paternity leave eligibility based on employee gender.
     */
    protected function validatePaternityLeaveEligibility($validator): void
    {
        $leaveTypeId = $this->input('leave_type_id');
        $employee = $this->user()->employee;

        // Skip validation if no employee linked or no leave type selected
        if (!$employee || !$leaveTypeId) {
            return;
        }

        // Get the leave type to check if it's paternity leave
        $leaveType = LeaveType::find($leaveTypeId);

        if ($leaveType && $leaveType->code === 'PL') {
            // Paternity leave (PL) is only available for male employees
            if (strtolower($employee->gender) !== 'male') {
                $validator->errors()->add('leave_type_id',
                    'Paternity leave is only available for male employees.');
            }
        }
    }

    private function isSickLeave(): bool
    {
        $leaveTypeId = $this->input('leave_type_id');
        if (!$leaveTypeId) {
            return false;
        }

        $leaveType = LeaveType::find($leaveTypeId);

        return $leaveType?->code === self::SICK_LEAVE_CODE;
    }

    protected function validateWorkingPeriod($validator): void
    {
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');
        $employee = $this->user()->employee;

        if (!$employee || !$startDate || !$endDate) {
            return;
        }

        $holidayService = app(HolidayService::class);

        try {
            $start = Carbon::parse($startDate, config('app.timezone', 'Asia/Manila'))->startOfDay();
            $end = Carbon::parse($endDate, config('app.timezone', 'Asia/Manila'))->startOfDay();
        } catch (\Exception $e) {
            return;
        }

        $workWeek = $employee->workCalendar?->work_week;

        if (!$holidayService->isWorkingWeekday($start, $workWeek) || $holidayService->isNonWorking($start, $employee)) {
            $validator->errors()->add('start_date', 'Start date falls on a non-working day.');
        }

        if (!$holidayService->isWorkingWeekday($end, $workWeek) || $holidayService->isNonWorking($end, $employee)) {
            $validator->errors()->add('end_date', 'End date falls on a non-working day.');
        }

        if (!$validator->errors()->has('start_date') && !$validator->errors()->has('end_date')) {
            $days = $holidayService->businessDaysBetween($start, $end, $employee, $workWeek);
            if ($days <= 0) {
                $validator->errors()->add('end_date', 'Selected date range does not include any working days.');
            }
        }
    }

    /**
     * Validate that employee has no existing pending applications.
     */
    protected function validateExistingPendingApplications($validator): void
    {
        $employee = $this->user()->employee;
        if (!$employee) {
            return;
        }

        // Check for existing pending applications
        $hasPendingApplication = \App\Models\LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingApplication) {
            $validator->errors()->add('leave_type_id',
                'You already have a pending leave application. Please wait for it to be approved or rejected before submitting a new one.');
        }
    }

    /**
     * Validate that employee has sufficient leave credits.
     */
    protected function validateLeaveCredits($validator): void
    {
        $employee = $this->user()->employee;
        $leaveTypeId = $this->input('leave_type_id');

        if (!$employee || !$leaveTypeId) {
            return;
        }

        $leaveType = LeaveType::find($leaveTypeId);
        if (!$leaveType) {
            return;
        }

        // Get current leave balances
        $leaveCardService = new \App\Services\LeaveCardService();
        $currentBalances = $leaveCardService->getCurrentBalances($employee);
        $availableCredits = $currentBalances[$leaveType->code] ?? 0;

        if ($availableCredits <= 0) {
            $validator->errors()->add('leave_type_id',
                "You have no available {$leaveType->name} credits. Your current balance is {$availableCredits} days. Please contact HR for assistance.");
        }
    }

    /**
     * Enforce CSC lead-time rules: VL 5 working days; SPLV 1 week (5 working days).
     */
    protected function validateLeadTimes($validator): void
    {
        $employee = $this->user()->employee;
        $leaveTypeId = $this->input('leave_type_id');
        $startDate = $this->input('start_date');

        if (!$employee || !$leaveTypeId || !$startDate) {
            return;
        }

        $leaveType = LeaveType::find($leaveTypeId);
        if (!$leaveType) {
            return;
        }

        $holidayService = app(HolidayService::class);
        $today = now()->startOfDay();

        try {
            $start = Carbon::parse($startDate, config('app.timezone', 'Asia/Manila'))->startOfDay();
        } catch (\Exception $e) {
            return;
        }

        $workWeek = $employee->workCalendar?->work_week;
        $leadBusinessDays = $holidayService->businessDaysBetween($today, $start, $employee, $workWeek);

        if ($leaveType->code === 'VL' && $leadBusinessDays < 5) {
            $validator->errors()->add('start_date', 'Vacation Leave must be filed at least 5 working days in advance (CSC Form 6 guidance).');
        }

        if ($leaveType->code === 'SPLV' && $leadBusinessDays < 5) {
            $validator->errors()->add('start_date', 'Special Privilege Leave must be filed at least 1 week/5 working days in advance.');
        }
    }

    /**
     * Require medical documentation for Sick Leave over 5 working days.
     */
    protected function validateMedicalDocumentation($validator): void
    {
        $employee = $this->user()->employee;
        $leaveTypeId = $this->input('leave_type_id');
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        if (!$employee || !$leaveTypeId || !$startDate || !$endDate) {
            return;
        }

        $leaveType = LeaveType::find($leaveTypeId);
        if (!$leaveType || $leaveType->code !== 'SL') {
            return;
        }

        $days = $this->computeWorkingDays($employee, $startDate, $endDate);

        if ($days > 5) {
            $documents = $this->file('documents', []);
            if (empty($documents)) {
                $validator->errors()->add('documents', 'Sick Leave over 5 working days requires a medical certificate or affidavit.');
            }
        }
    }

    private function computeWorkingDays($employee, string $startDate, string $endDate): int
    {
        $holidayService = app(HolidayService::class);
        $start = Carbon::parse($startDate, config('app.timezone', 'Asia/Manila'))->startOfDay();
        $end = Carbon::parse($endDate, config('app.timezone', 'Asia/Manila'))->startOfDay();
        $workWeek = $employee->workCalendar?->work_week;

        return $holidayService->businessDaysBetween($start, $end, $employee, $workWeek);
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'Please select a leave type.',
            'leave_type_id.exists' => 'The selected leave type is invalid.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be within the allowed window (today onward for most leaves; up to 30 days back for sick leave).',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'reason.required' => 'Please provide a reason for your leave application.',
            'reason.max' => 'Reason must not exceed 500 characters.',
            'dept_head_informed.required' => 'Please confirm that your department head has been informed.',
            'dept_head_informed.accepted' => 'You must confirm that your department head has been informed before submitting this leave application.',
            'documents.*.mimes' => 'Document must be a PDF, DOC, DOCX, JPG, JPEG, or PNG file.',
            'documents.*.max' => 'Document size must not exceed 2MB.',
            'start_date.before_or_equal' => 'For sick leave, the start date must be within 30 days from today.',
            'end_date.before_or_equal' => 'For sick leave, the end date must be within 30 days from today.',
        ];
    }
}
