<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class PMTFinalizeIpcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'overall_score' => ['nullable', 'numeric', 'between:1,5'],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
