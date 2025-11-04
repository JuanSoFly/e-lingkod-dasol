<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class PMTValidateIpcrRequest extends FormRequest
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
            'items.*.pmt_rating' => ['nullable', 'numeric', 'between:1,5'],
            'items.*.pmt_comments' => ['nullable', 'string', 'max:2000'],
            'validation_stage' => ['nullable', 'string', 'max:100'],
            'recommended_rating' => ['nullable', 'numeric', 'between:1,5'],
            'remarks' => ['nullable', 'string', 'max:4000'],
            'quality' => ['nullable', 'numeric', 'between:1,5'],
            'efficiency' => ['nullable', 'numeric', 'between:1,5'],
            'timeliness' => ['nullable', 'numeric', 'between:1,5'],
        ];
    }
}
