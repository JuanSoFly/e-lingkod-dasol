<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('performance.create');
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'exists:performance_periods,id'],
            'objective' => ['required', 'string'],
            'target' => ['required', 'string'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'success_indicator' => ['required', 'string'],
        ];
    }
}