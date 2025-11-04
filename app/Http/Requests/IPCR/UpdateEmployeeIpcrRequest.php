<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeIpcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:ipcr_items,id'],
            'items.*.self_rating' => ['nullable', 'numeric', 'between:1,5'],
            'items.*.remarks' => ['nullable', 'string', 'max:2000'],
            'items.*.weight' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}
