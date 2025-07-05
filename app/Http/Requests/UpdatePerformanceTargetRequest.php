<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerformanceTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('performance.create');
    }

    public function rules(): array
    {
        return [
            'objective' => ['required', 'string'],
            'target' => ['required', 'string'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'success_indicator' => ['required', 'string'],
        ];
    }
}