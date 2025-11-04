<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDevelopmentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'focus_area' => ['nullable', 'string', 'max:255'],
            'action_item' => ['nullable', 'string'],
            'target_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'support_needed' => ['nullable', 'string'],
        ];
    }
}
