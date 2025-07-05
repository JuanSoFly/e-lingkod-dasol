<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupervisorRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('performance.evaluate');
    }

    public function rules(): array
    {
        return [
            'supervisor_rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}