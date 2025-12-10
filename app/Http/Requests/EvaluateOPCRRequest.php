<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class EvaluateOPCRRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('opcr.assess');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'save_as_draft' => [
                'sometimes',
                'boolean',
            ],
            'evaluations' => [
                'required',
                'array',
                'min:1',
            ],
            'evaluations.*.target_id' => [
                'required',
                'exists:performance_targets,id',
                function ($attribute, $value, $fail) {
                    // Validate that target belongs to the workflow being evaluated
                    $workflowId = $this->route('workflow')->id;
                    $target = \App\Models\PerformanceTarget::find($value);

                    if (!$target || $target->opcr_workflow_id != $workflowId) {
                        $fail('Invalid target specified for evaluation.');
                    }
                },
            ],
            'evaluations.*.accomplished_quality' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
                function ($attribute, $value, $fail) {
                    // Extract target_id from the associative array key or nested field
                    $attributeParts = explode('.', $attribute);
                    $targetKey = $attributeParts[1]; // This is the target_id when using associative array

                    // Try to get target_id from the nested field first (for backward compatibility)
                    $targetId = $this->input("evaluations.{$targetKey}.target_id");

                    // If nested target_id doesn't exist, use the key itself as target_id
                    if (!$targetId && is_numeric($targetKey)) {
                        $targetId = $targetKey;
                    }

                    $target = \App\Models\PerformanceTarget::find($targetId);

                    if ($target && $target->target_quality && $value > ($target->target_quality * 5)) {
                        $fail('Accomplished quality cannot exceed 5 times the target quality.');
                    }
                },
            ],
            'evaluations.*.accomplished_efficiency' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-\.,%]+$/',
            ],
            'evaluations.*.accomplished_timeliness' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-\.,%]+$/',
            ],
            'evaluations.*.quality_rating' => [
                'required',
                'numeric',
                'min:0',
                'max:5',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'evaluations.*.efficiency_rating' => [
                'required',
                'numeric',
                'min:0',
                'max:5',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'evaluations.*.timeliness_rating' => [
                'required',
                'numeric',
                'min:0',
                'max:5',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'evaluations.*.remarks' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9\s\-\.,();:\/&]+$/', // Allow reasonable characters
            ],
            'overall_remarks' => [
                $this->boolean('save_as_draft') ? 'sometimes' : 'required',
                'string',
                'min:10',
                'max:2000',
                'regex:/^[a-zA-Z0-9\s\-\.,();:\/&\n\r]+$/', // Allow line breaks
            ],
            'recommendations' => [
                'nullable',
                'string',
                'max:1000',
                'regex:/^[a-zA-Z0-9\s\-\.,();:\/&\n\r]+$/',
            ],
            'action' => [
                $this->boolean('save_as_draft') ? 'sometimes' : 'required',
                'string',
                'in:submit,return',
            ],
            'return_reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'evaluations.required' => 'At least one evaluation is required.',
            'evaluations.*.target_id.required' => 'Target ID is required for each evaluation.',
            'evaluations.*.accomplished_quality.required' => 'Accomplished quality is required.',
            'evaluations.*.accomplished_quality.numeric' => 'Accomplished quality must be a number.',
            'evaluations.*.accomplished_quality.min' => 'Accomplished quality cannot be negative.',
            'evaluations.*.accomplished_efficiency.required' => 'Accomplished efficiency description is required.',
            'evaluations.*.accomplished_timeliness.required' => 'Accomplished timeliness description is required.',
            'evaluations.*.quality_rating.required' => 'Quality rating is required.',
            'evaluations.*.quality_rating.min' => 'Quality rating must be at least 0.',
            'evaluations.*.quality_rating.max' => 'Quality rating cannot exceed 5.',
            'evaluations.*.efficiency_rating.required' => 'Efficiency rating is required.',
            'evaluations.*.efficiency_rating.min' => 'Efficiency rating must be at least 0.',
            'evaluations.*.efficiency_rating.max' => 'Efficiency rating cannot exceed 5.',
            'evaluations.*.timeliness_rating.required' => 'Timeliness rating is required.',
            'evaluations.*.timeliness_rating.min' => 'Timeliness rating must be at least 0.',
            'evaluations.*.timeliness_rating.max' => 'Timeliness rating cannot exceed 5.',
            'overall_remarks.required' => 'Overall remarks are required.',
            'overall_remarks.min' => 'Overall remarks must be at least 10 characters.',
            'action.required' => 'Please select an action (Submit or Return for Revision).',
            'action.in' => 'Invalid action selected.',
            'return_reason.required_if' => 'Return reason is required when returning for revision.',
            'return_reason.min' => 'Return reason must be at least 10 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateEvaluationRules($validator);
        });
    }

    /**
     * Validate evaluation-specific business rules
     */
    protected function validateEvaluationRules($validator)
    {
        $workflow = $this->route('workflow');
        $evaluations = $this->input('evaluations', []);

        if (!is_array($evaluations)) {
            $evaluations = [];
        }

        // Ensure we are comparing against the latest target count
        $totalTargets = $workflow->targets()->count();
        $evaluatedTargets = count($evaluations);

        if ($totalTargets !== $evaluatedTargets) {
            $validator->errors()->add('incomplete_evaluation',
                "All {$totalTargets} targets must be evaluated. Currently evaluated: {$evaluatedTargets} targets.");
        }

        // Validate rating consistency - Handle both numeric indices and target_id keys
        foreach ($evaluations as $targetKey => $evaluation) {
            $qualityRating = $evaluation['quality_rating'] ?? 0;
            $efficiencyRating = $evaluation['efficiency_rating'] ?? 0;
            $timelinessRating = $evaluation['timeliness_rating'] ?? 0;

            // Check for extremely low ratings without justification
            $averageRating = ($qualityRating + $efficiencyRating + $timelinessRating) / 3;
            if ($averageRating < 1.5 && empty(trim($evaluation['remarks'] ?? ''))) {
                $validator->errors()->add("evaluations.{$targetKey}.remarks_required",
                    'Remarks are required for ratings below 1.5.');
            }

            // Validate rating ranges are reasonable
            if ($qualityRating > 5 || $efficiencyRating > 5 || $timelinessRating > 5) {
                $validator->errors()->add("evaluations.{$targetKey}.invalid_rating",
                    'Individual ratings cannot exceed 5.0.');
            }

            // Validate that the target exists and belongs to this workflow
            $targetId = $evaluation['target_id'] ?? (is_numeric($targetKey) ? (int)$targetKey : null);
            if ($targetId) {
                $target = \App\Models\PerformanceTarget::find($targetId);
                if (!$target || $target->opcr_workflow_id != $workflow->id) {
                    $validator->errors()->add("evaluations.{$targetKey}.invalid_target",
                        'Invalid target specified for evaluation.');
                }
            }
        }

        // Validate that return reason is provided when action is 'return'
        if ($this->input('action') === 'return') {
            if (!filled($this->input('return_reason'))) {
                $validator->errors()->add('return_reason', 'Return reason is required when returning OPCR for revision.');
            } elseif (strlen($this->input('return_reason')) < 10) {
                $validator->errors()->add('return_reason', 'Return reason must be at least 10 characters.');
            } elseif (!preg_match('/^[a-zA-Z0-9\s\-\.,();:\/&\n\r]+$/', $this->input('return_reason'))) {
                $validator->errors()->add('return_reason', 'The Return Reason field format is invalid.');
            }
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'evaluations.*.target_id' => 'Target',
            'evaluations.*.accomplished_quality' => 'Accomplished Quality',
            'evaluations.*.accomplished_efficiency' => 'Accomplished Efficiency',
            'evaluations.*.accomplished_timeliness' => 'Accomplished Timeliness',
            'evaluations.*.quality_rating' => 'Quality Rating',
            'evaluations.*.efficiency_rating' => 'Efficiency Rating',
            'evaluations.*.timeliness_rating' => 'Timeliness Rating',
            'evaluations.*.remarks' => 'Target Remarks',
            'overall_remarks' => 'Overall Remarks',
            'recommendations' => 'Recommendations',
            'action' => 'Action',
            'return_reason' => 'Return Reason',
        ];
    }
}
