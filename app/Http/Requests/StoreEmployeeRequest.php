<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\GovernmentIdFormat;

class StoreEmployeeRequest extends FormRequest
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
        return [
            'employee_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/'
            ],
            'first_name' => [
                'required', 
                'string', 
                'max:100',
                'regex:/^[a-zA-Z\s\.\-\']+$/'
            ],
            'middle_name' => [
                'nullable', 
                'string', 
                'max:100',
                'regex:/^[a-zA-Z\s\.\-\']+$/'
            ],
            'last_name' => [
                'required', 
                'string', 
                'max:100',
                'regex:/^[a-zA-Z\s\.\-\']+$/'
            ],
            'birth_date' => [
                'required', 
                'date', 
                'before:today',
                'after:1900-01-01'
            ],
            'gender' => [
                'required',
                'in:Male,Female,Other'
            ],
            'civil_status' => [
                'required',
                'in:Single,Married,Widowed,Separated,Divorced'
            ],
            'address' => [
                'required', 
                'string',
                'max:500'
            ],
            'contact_number' => [
                'required', 
                'string', 
                'max:20',
                'regex:/^(\+63|0)[0-9]{10}$/'
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
                'unique:employees,email,NULL,id,deleted_at,NULL',
                'unique:users,email,NULL,id,deleted_at,NULL'
            ],
            'position' => [
                'required', 
                'string', 
                'max:100'
            ],
            'department' => [
                'nullable',
                'string',
                'max:100'
            ],
            'office_id' => [
                'nullable',
                'exists:offices,id'
            ],
            'work_calendar_id' => [
                'required',
                'exists:work_calendars,id'
            ],
            'employment_status' => [
                'required',
                'in:probationary,regular,contractual,casual,job-order'
            ],
            'date_hired' => [
                'required', 
                'date', 
                'before_or_equal:today',
                'after:birth_date'
            ],
            'salary_grade' => [
                'required', 
                'integer', 
                'min:1', 
                'max:33'
            ],
            'step_increment' => [
                'required', 
                'integer', 
                'min:1', 
                'max:8'
            ],
            'basic_salary' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
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

    public function messages(): array
    {
        return [
            'employee_number.regex' => 'Employee number must contain only uppercase letters, numbers, and hyphens.',
            'first_name.regex' => 'First name must contain only letters, spaces, periods, hyphens, and apostrophes.',
            'middle_name.regex' => 'Middle name must contain only letters, spaces, periods, hyphens, and apostrophes.',
            'last_name.regex' => 'Last name must contain only letters, spaces, periods, hyphens, and apostrophes.',
            'birth_date.before' => 'Birth date must be before today.',
            'birth_date.after' => 'Birth date must be after 1900-01-01.',
            'contact_number.regex' => 'Contact number must be a valid Philippine mobile number (e.g., +639xxxxxxxxx or 09xxxxxxxxx).',
            'email.email' => 'Email must be a valid email address.',
            'date_hired.after' => 'Date hired must be after birth date.',
            'salary_grade.max' => 'Salary grade must not exceed 33.',
            'step_increment.max' => 'Step increment must not exceed 8.',
            'basic_salary.required' => 'Basic salary is required.',
            'basic_salary.numeric' => 'Basic salary must be a valid number.',
            'basic_salary.min' => 'Basic salary cannot be negative.',
            'work_calendar_id.required' => 'Please select a work calendar.',
        ];
    }
}
