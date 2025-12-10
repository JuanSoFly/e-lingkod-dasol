<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\GovernmentIdFormat;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee->id;
        $userId = optional($employee->user()->withTrashed()->first())->id;

        return [
            'employee_number' => ['required', 'string', 'max:255', Rule::unique('employees')->ignore($employeeId)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'string'],
            'civil_status' => ['required', 'string'],
            'address' => ['required', 'string'],
            'contact_number' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)->whereNull('deleted_at'),
            ],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'office_id' => ['nullable', 'exists:offices,id'],
            'office_role' => ['nullable', 'string', 'max:255'],
            'is_department_head' => ['nullable', 'boolean'],
            'work_calendar_id' => ['nullable', 'exists:work_calendars,id'],
            'employment_status' => ['required', 'string', 'max:255'],
            'date_hired' => ['required', 'date'],
            'salary_grade' => ['required', 'integer'],
            'step_increment' => ['required', 'integer'],
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            // Government ID validation
            'tin_number' => [
                'nullable',
                'string',
                'max:20',
                new GovernmentIdFormat('tin')
            ],
            'sss_number' => [
                'nullable',
                'string',
                'max:20',
                new GovernmentIdFormat('sss')
            ],
            'pagibig_number' => [
                'nullable',
                'string',
                'max:20',
                new GovernmentIdFormat('pagibig')
            ],
            'philhealth_number' => [
                'nullable',
                'string',
                'max:20',
                new GovernmentIdFormat('philhealth')
            ],
            'gsis_number' => [
                'nullable',
                'string',
                'max:20',
                new GovernmentIdFormat('gsis')
            ],
        ];
    }
}
