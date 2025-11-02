<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfficeRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:offices,code',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:offices,id',
            'head_title' => 'nullable|string|max:100',
            'department_head_id' => 'nullable|exists:employees,id',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'location' => 'nullable|string|max:200',
        ];
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
            'contact_number.max' => 'The contact number may not be greater than 50 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email address may not be greater than 100 characters.',
            'location.max' => 'The location may not be greater than 200 characters.',
        ];
    }
}