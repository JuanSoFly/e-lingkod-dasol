<?php

namespace App\Http\Requests\IPCR;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoachingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_date' => ['required', 'date'],
            'session_type' => ['nullable', 'string', 'max:100'],
            'focus_area' => ['nullable', 'string', 'max:255'],
            'discussion_notes' => ['nullable', 'string'],
            'agreements' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'actions' => ['nullable', 'array'],
            'actions.*.focus_area' => ['required_with:actions', 'string', 'max:255'],
            'actions.*.action_item' => ['required_with:actions', 'string'],
            'actions.*.target_date' => ['nullable', 'date'],
            'actions.*.status' => ['nullable', 'string', 'max:50'],
            'actions.*.support_needed' => ['nullable', 'string'],
        ];
    }
}
