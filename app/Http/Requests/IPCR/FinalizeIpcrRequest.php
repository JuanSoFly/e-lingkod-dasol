<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeIpcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'final_score' => ['required', 'numeric', 'between:1,5'],
            'performance_level' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
