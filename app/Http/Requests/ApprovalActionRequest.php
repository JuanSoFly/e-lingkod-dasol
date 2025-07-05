<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('documentApprovalRequest'));
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in(['approve', 'reject', 'request_changes'])
            ],
            'comments' => [
                'nullable',
                'string',
                'max:2000'
            ],
            'is_internal_comment' => [
                'boolean'
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:10'
            ],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Approval action is required.',
            'action.in' => 'Invalid approval action. Must be approve, reject, or request_changes.',
            'comments.max' => 'Comments cannot exceed 2000 characters.',
            'attachments.max' => 'Maximum of 10 attachments allowed.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.max' => 'Each attachment cannot exceed 10MB.',
            'attachments.*.mimes' => 'Attachments must be PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, or TXT files.'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_internal_comment' => $this->boolean('is_internal_comment', false)
        ]);
    }
}
