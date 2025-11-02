<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreOPCRWorkflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('opcr.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:10',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-\.,()&]+$/',
            ],
            'office_id' => [
                'required',
                'exists:offices,id',
                function ($attribute, $value, $fail) {
                    // Check if user has permission for this office
                    if (!Auth::user()->hasAnyRole(['Super Admin', 'HR Admin'])) {
                        $hasAccess = Auth::user()->officeAssignments()
                            ->where('office_id', $value)
                            ->where('role', 'Department Head')
                            ->where('is_active', true)
                            ->exists();

                        if (!$hasAccess) {
                            $fail('You are not authorized to create OPCR for the selected office.');
                        }
                    }
                },
            ],
            'period_id' => [
                'required',
                'exists:performance_periods,id',
                function ($attribute, $value, $fail) {
                    // Check if period is active or within valid date range
                    $period = \App\Models\PerformancePeriod::find($value);
                    if ($period && !$period->is_active && now()->lt($period->start_date)) {
                        $fail('Cannot create OPCR for a future period.');
                    }
                },
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
                'regex:/^[a-zA-Z0-9\s\-\.,()&;:\'"\/]+$/', // Allow basic characters
            ],
            'targets' => [
                'required',
                'array',
                'min:1',
                'max:20', // Limit to 20 targets per workflow
            ],
            'targets.*.mfo_id' => [
                'required',
                'exists:major_final_outputs,id',
                'distinct',
            ],
            'targets.*.success_indicator_id' => [
                'required',
                'exists:success_indicators,id',
                function ($attribute, $value, $fail) {
                    // Validate that success indicator belongs to the selected MFO
                    $targetIndex = explode('.', $attribute)[1];
                    $mfoId = $this->input("targets.{$targetIndex}.mfo_id");

                    $indicator = \App\Models\SuccessIndicator::find($value);
                    if ($indicator && $indicator->mfo_id != $mfoId) {
                        $fail('The selected success indicator does not belong to the selected MFO.');
                    }
                },
            ],
            'targets.*.target_quantity' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'targets.*.target_efficiency' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-\.,%]+$/',
            ],
            'targets.*.target_timeliness' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-\.,%]+$/',
            ],
            'targets.*.weight' => [
                'required',
                'numeric',
                'min:0.1',
                'max:100',
                'regex:/^\d+(\.\d{1,2})?$/',
                function ($attribute, $value, $fail) {
                    // Validate that total weight does not exceed 100
                    $totalWeight = collect($this->input('targets'))->sum('weight');
                    if ($totalWeight > 100) {
                        $fail('Total weight of all targets must not exceed 100%.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The OPCR title is required.',
            'title.min' => 'The OPCR title must be at least 10 characters long.',
            'title.regex' => 'The OPCR title contains invalid characters.',
            'office_id.required' => 'Please select an office for this OPCR.',
            'office_id.exists' => 'The selected office is invalid.',
            'period_id.required' => 'Please select a performance period.',
            'period_id.exists' => 'The selected performance period is invalid.',
            'targets.required' => 'At least one performance target is required.',
            'targets.min' => 'At least one performance target is required.',
            'targets.max' => 'Cannot have more than 20 performance targets.',
            'targets.*.mfo_id.required' => 'Please select an MFO for each target.',
            'targets.*.mfo_id.distinct' => 'Each MFO can only be selected once.',
            'targets.*.success_indicator_id.required' => 'Please select a success indicator for each target.',
            'targets.*.target_quantity.numeric' => 'Target quantity must be a number.',
            'targets.*.target_quantity.min' => 'Target quantity cannot be negative.',
            'targets.*.weight.required' => 'Weight is required for each target.',
            'targets.*.weight.min' => 'Weight must be at least 0.1.',
            'targets.*.weight.max' => 'Weight cannot exceed 100.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation for business rules
            $this->validateBusinessRules($validator);
        });
    }

    /**
     * Validate business rules
     */
    protected function validateBusinessRules($validator)
    {
        // Check if OPCR already exists for this office and period
        $existingOPCR = \App\Models\OPCRWorkflow::where('office_id', $this->office_id)
            ->where('period_id', $this->period_id)
            ->whereNotIn('workflow_state', ['approved', 'rejected'])
            ->exists();

        if ($existingOPCR) {
            $validator->errors()->add('duplicate_opcr',
                'An OPCR workflow already exists for this office and period. Please complete or delete the existing workflow first.');
        }

        // Validate target weights sum to exactly 100
        if ($this->has('targets')) {
            $totalWeight = collect($this->targets)->sum('weight');
            if (abs($totalWeight - 100) > 0.01) { // Allow for floating point precision
                $validator->errors()->add('weight_total',
                    'Total weight of all targets must equal exactly 100%. Current total: ' . $totalWeight . '%');
            }
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'OPCR Title',
            'office_id' => 'Office',
            'period_id' => 'Performance Period',
            'description' => 'Description',
            'targets.*.mfo_id' => 'MFO',
            'targets.*.success_indicator_id' => 'Success Indicator',
            'targets.*.target_quantity' => 'Target Quantity',
            'targets.*.target_efficiency' => 'Target Efficiency',
            'targets.*.target_timeliness' => 'Target Timeliness',
            'targets.*.weight' => 'Weight',
        ];
    }
}