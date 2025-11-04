<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Office;

class UpdateOfficeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $office = $this->route('office');

        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:offices,code,'.$office->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:offices,id',
            'head_title' => 'nullable|string|max:100',
            'department_head_id' => [
                'nullable',
                'exists:employees,id',
                function ($attribute, $value, $fail) use ($office) {
                    if ($value) {
                        $employee = \App\Models\Employee::find($value);
                        if (!$employee || !$this->employeeBelongsToOffice($employee, $office)) {
                            $fail('The selected department head must belong to this office.');
                        }
                    }
                },
            ],
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'location' => 'nullable|string|max:200',
        ];
    }

    /**
     * Check if an employee belongs to an office based on department matching
     */
    private function employeeBelongsToOffice($employee, $office): bool
    {
        // Direct department name match
        if ($employee->department === $office->name) {
            return true;
        }

        // Handle specific department mappings for variations
        $departmentMappings = [
            'Business Permit and Licensing Office' => ['Business Permits and Licensing Office'],
            'Municipal Civil Registrar\'s Office' => ['Local Civil Registry Office'],
            'Municipal Agriculture Office' => ['Municipal Agriculturist\'s Office'],
            'Assessor\'s Office' => ['Municipal Assessor\'s Office'],
            'Accounting Office' => ['Office of the Municipal Accountant'],
            'Budget and Treasury Office' => ['Office of the Municipal Budget Office', 'Municipal Treasurer\'s Office'],
            'Cooperatives and Tourist Office' => ['Municipal Tourism and Cultural Affairs Office'],
            'Municipal Health Office' => ['Rural Health Unit'],
        ];

        // Check if this office has mapped departments and if employee's department matches
        if (isset($departmentMappings[$office->name])) {
            return in_array($employee->department, $departmentMappings[$office->name]);
        }

        return false;
    }

    /**
     * Get the custom error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The office name is required.',
            'name.max' => 'The office name may not be greater than 255 characters.',
            'code.unique' => 'The office code has already been taken.',
            'code.max' => 'The office code may not be greater than 50 characters.',
            'parent_id.exists' => 'The selected parent office is invalid.',
            'head_title.max' => 'The head title may not be greater than 100 characters.',
            'department_head_id.exists' => 'The selected department head is invalid.',
            'department_head_id' => 'The selected department head must belong to this office.',
            'contact_number.max' => 'The contact number may not be greater than 50 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email address may not be greater than 100 characters.',
            'location.max' => 'The location may not be greater than 200 characters.',
        ];
    }
}