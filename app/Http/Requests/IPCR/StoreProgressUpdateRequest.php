<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ipcr_item_id' => ['nullable', 'exists:ipcr_items,id'],
            'progress_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'accomplishments' => ['nullable', 'string'],
            'challenges' => ['nullable', 'string'],
            'next_steps' => ['nullable', 'string'],
        ];
    }
}
