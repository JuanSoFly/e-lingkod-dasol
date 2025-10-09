<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SalaryGradeFormat implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow null/empty values, let 'required' rule handle it
        }

        // Remove whitespace and convert to uppercase
        $formattedValue = strtoupper(trim($value));

        // Validate CSC salary grade format: "00-0" (2 digits, hyphen, 1 digit)
        // Examples: "01-0", "12-3", "22-5", "30-1"
        if (!preg_match('/^\d{2}-\d{1}$/', $formattedValue)) {
            $fail('The :attribute must follow CSC format "00-0" (e.g., "12-3", "01-0").');
            return;
        }

        // Additional validation: salary grade should be within reasonable range
        $parts = explode('-', $formattedValue);
        $grade = (int) $parts[0];
        $step = (int) $parts[1];

        // Salary grades in Philippine government typically range from 1-33
        if ($grade < 1 || $grade > 33) {
            $fail('The :attribute grade must be between 01 and 33.');
        }

        // Steps typically range from 1-8
        if ($step < 0 || $step > 8) {
            $fail('The :attribute step must be between 0 and 8.');
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute must follow CSC format "00-0" (e.g., "12-3", "01-0").';
    }
}