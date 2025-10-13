<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
                'required',
                'string',
                'max:20',
                'unique:employees,employee_number',
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
                'in:male,female,other'
            ],
            'civil_status' => [
                'required', 
                'in:single,married,divorced,widowed,separated'
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
                'unique:users,email'
            ],
            'position' => [
                'required', 
                'string', 
                'max:100'
            ],
            'department' => [
                'required', 
                'string', 
                'max:100'
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
        ];
    }
}