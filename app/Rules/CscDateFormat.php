<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class CscDateFormat implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow null/empty values, let 'required' rule handle it
        }

        // Define accepted date formats for Philippine government forms
        $formats = [
            'Y-m-d',        // HTML5 date input format (2023-12-25)
            'm/d/Y',        // Traditional CSC format (12/25/2023)
            'm-d-Y',        // Alternative format (12-25-2023)
            'F j, Y',       // Long format (December 25, 2023)
            'M j, Y',       // Short format (Dec 25, 2023)
        ];

        $date = null;
        $usedFormat = null;

        // Try each format until one works
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                // Additional validation: ensure the parsed date matches the input
                // This prevents Carbon from auto-correcting invalid dates
                $reformatted = $date->format($format);
                if ($reformatted !== $value) {
                    // The date was auto-corrected (e.g., 15/01/2023 became 03/01/2024)
                    continue; // Try next format
                }

                $usedFormat = $format;
                break;
            } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                continue; // Try next format
            }
        }

        // If no format worked, show error with examples
        if ($date === null) {
            $fail('The :attribute must be a valid date. Accepted formats: mm/dd/yyyy (12/25/2023), yyyy-mm-dd (2023-12-25), or Month dd, yyyy (December 25, 2023).');
            return;
        }

        // Additional validation: date should be reasonable
        if ($date->year < 1900 || $date->year > (date('Y') + 1)) {
            $fail('The :attribute must be a valid date between 1900 and ' . (date('Y') + 1) . '.');
        }

        // Additional validation: date should not be too far in the future for most fields
        if ($date->isAfter(now()->addYear())) {
            $fail('The :attribute cannot be more than 1 year in the future.');
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute must be a valid date. Accepted formats: mm/dd/yyyy (12/25/2023), yyyy-mm-dd (2023-12-25), or Month dd, yyyy (December 25, 2023).';
    }
}