<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $request = $this->route('document_approval');
        
        return auth()->check() && (
            // Request owner can edit if in draft or returned status
            (auth()->id() === $request->requester_id && in_array($request->status, ['draft', 'returned'])) ||
            // Admins can always edit
            auth()->user()->hasRole(['Super Admin', 'HR Admin'])
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'nullable|date|after:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The request title is required.',
            'title.max' => 'The title cannot exceed 255 characters.',
            'description.max' => 'The description cannot exceed 2000 characters.',
            'priority.required' => 'Please select a priority level.',
            'priority.in' => 'Invalid priority level selected.',
            'deadline.date' => 'Please provide a valid deadline date.',
            'deadline.after' => 'The deadline must be a future date.',
        ];
    }
}
