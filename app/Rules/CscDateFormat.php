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

        try {
            // Try to parse the date in mm/dd/yyyy format
            $date = Carbon::createFromFormat('m/d/Y', $value);

            // Additional validation: date should be reasonable
            if ($date->year < 1900 || $date->year > (date('Y') + 1)) {
                $fail('The :attribute must be a valid date between 1900 and ' . (date('Y') + 1) . '.');
            }

            // Additional validation: date should not be too far in the future for most fields
            if ($date->isAfter(now()->addYear())) {
                $fail('The :attribute cannot be more than 1 year in the future.');
            }

        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
            $fail('The :attribute must be in mm/dd/yyyy format (e.g., "12/25/2023").');
        } catch (\Exception $e) {
            $fail('The :attribute must be a valid date in mm/dd/yyyy format.');
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute must be in mm/dd/yyyy format (e.g., "12/25/2023").';
    }
}