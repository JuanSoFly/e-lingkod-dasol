<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LeaveType;
use Carbon\Carbon;

class StoreLeaveApplicationRequest extends FormRequest
{
    private const SICK_LEAVE_CODE = 'SL';
    private const SICK_BACKDATE_WINDOW_DAYS = 30;
    private const SICK_ADVANCE_WINDOW_DAYS = 30;

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
        $leaveType = $this->input('leave_type_id')
            ? LeaveType::find($this->input('leave_type_id'))
            : null;

        $isSickLeave = $leaveType?->code === self::SICK_LEAVE_CODE;
        $minSickDate = Carbon::now()->subDays(self::SICK_BACKDATE_WINDOW_DAYS)->toDateString();
        $maxSickDate = Carbon::now()->addDays(self::SICK_ADVANCE_WINDOW_DAYS)->toDateString();

        return [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => array_filter([
                'required',
                'date',
                $isSickLeave ? "after_or_equal:{$minSickDate}" : 'after_or_equal:today',
                $isSickLeave ? "before_or_equal:{$maxSickDate}" : null,
            ]),
            'end_date' => array_filter([
                'required',
                'date',
                'after_or_equal:start_date',
                $isSickLeave ? "before_or_equal:{$maxSickDate}" : null,
            ]),
            'days_requested' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['required', 'string', 'max:1000'],
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
}
