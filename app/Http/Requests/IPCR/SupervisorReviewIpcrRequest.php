<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorReviewIpcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:ipcr_items,id'],
            'items.*.supervisor_rating' => ['nullable', 'numeric', 'between:1,5'],
            'items.*.supervisor_comments' => ['nullable', 'string', 'max:2000'],
            'overall_remarks' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
