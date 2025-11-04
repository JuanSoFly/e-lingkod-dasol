<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class HeadApprovalIpcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'exists:ipcr_items,id'],
            'items.*.head_rating' => ['nullable', 'numeric', 'between:1,5'],
            'items.*.head_comments' => ['nullable', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
