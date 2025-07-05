<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSelfRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy will handle the authorization
        return true;
    }

    public function rules(): array
    {
        return [
            'self_rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}