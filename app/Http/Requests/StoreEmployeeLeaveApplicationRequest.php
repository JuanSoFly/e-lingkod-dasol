<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use App\Services\HolidayService;

class StoreEmployeeLeaveApplicationRequest extends FormRequest
{
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

        return [
            // Leave type is always required (even for drafts)
            'leave_type_id' => ['required', 'exists:leave_types,id'],

            // Dates are flexible for drafts but required for submission
            'start_date' => $isDraft
                ? ['nullable', 'date', 'after_or_equal:today']
                : ['required', 'date', 'after_or_equal:today'],
            'end_date' => $isDraft
                ? ['nullable', 'date', 'after_or_equal:start_date']
                : ['required', 'date', 'after_or_equal:start_date'],

            // Days requested is calculated automatically, not input by user
            // Don't include it in validation since controller calculates it

            // Reason is flexible for drafts
            'reason' => $isDraft
                ? ['nullable', 'string', 'max:500']
                : ['required', 'string', 'max:500'],

            // Department head confirmation is not required for drafts but must be accepted for submissions
            'dept_head_informed' => $isDraft
                ? ['nullable', 'string', 'in:0,1']
                : ['required', 'string', 'in:1'],

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
            $this->validateWorkingPeriod($validator);
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
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'reason.required' => 'Please provide a reason for your leave application.',
            'reason.max' => 'Reason must not exceed 500 characters.',
            'dept_head_informed.required' => 'Please confirm that your department head has been informed.',
            'dept_head_informed.accepted' => 'You must confirm that your department head has been informed before submitting this leave application.',
            'documents.*.mimes' => 'Document must be a PDF, DOC, DOCX, JPG, JPEG, or PNG file.',
            'documents.*.max' => 'Document size must not exceed 2MB.',
        ];
    }
}
