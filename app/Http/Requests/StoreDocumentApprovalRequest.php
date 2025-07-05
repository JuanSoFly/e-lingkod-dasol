<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole(['Super Admin', 'HR Admin', 'Employee']) ||
            auth()->user()->hasPermissionTo('document-approval.create')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'employee_id' => 'nullable|exists:employees,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'nullable|date|after:today',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_type.required' => 'Please select a document type.',
            'title.required' => 'The request title is required.',
            'title.max' => 'The title cannot exceed 255 characters.',
            'description.max' => 'The description cannot exceed 2000 characters.',
            'employee_id.exists' => 'The selected employee is invalid.',
            'priority.required' => 'Please select a priority level.',
            'priority.in' => 'Invalid priority level selected.',
            'deadline.date' => 'Please provide a valid deadline date.',
            'deadline.after' => 'The deadline must be a future date.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.max' => 'Each file cannot exceed 10MB.',
            'attachments.*.mimes' => 'Supported file types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_type' => 'document type',
            'employee_id' => 'employee',
            'attachments.*' => 'attachment',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('priority')) {
            $this->merge(['priority' => 'medium']);
        }

        // Set employee_id to current user's employee if not provided and user is an employee
        if (!$this->has('employee_id') && auth()->user()->employee) {
            $this->merge(['employee_id' => auth()->user()->employee->id]);
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function getDocumentTypes(): array
    {
        return [
            'leave_application' => 'Leave Application',
            'performance_evaluation' => 'Performance Evaluation',
            'training_certificate' => 'Training Certificate',
            'disciplinary_action' => 'Disciplinary Action',
            'employee_document' => 'Employee Document',
            'policy_acknowledgment' => 'Policy Acknowledgment',
            'overtime_request' => 'Overtime Request',
            'travel_order' => 'Travel Order',
            'promotion_request' => 'Promotion Request',
            'transfer_request' => 'Transfer Request',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return \Illuminate\Validation\Validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: Check if document type is valid
            $documentTypes = array_keys($this->getDocumentTypes());
            if ($this->document_type && !in_array($this->document_type, $documentTypes)) {
                $validator->errors()->add('document_type', 'Invalid document type selected.');
            }

            // Custom validation: Employee can only create requests for themselves unless admin
            if ($this->employee_id && 
                !auth()->user()->hasRole(['Super Admin', 'HR Admin']) &&
                auth()->user()->employee &&
                $this->employee_id != auth()->user()->employee->id) {
                $validator->errors()->add('employee_id', 'You can only create requests for yourself.');
            }
        });
    }
}