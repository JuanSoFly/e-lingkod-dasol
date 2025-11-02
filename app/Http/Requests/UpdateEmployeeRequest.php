<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $employeeId = $this->route('employee')->id;
        $userId = $this->route('employee')->user->id ?? null;

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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'office_id' => ['nullable', 'exists:offices,id'],
            'work_calendar_id' => ['nullable', 'exists:work_calendars,id'],
            'employment_status' => ['required', 'string', 'max:255'],
            'date_hired' => ['required', 'date'],
            'salary_grade' => ['required', 'integer'],
            'step_increment' => ['required', 'integer'],
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
