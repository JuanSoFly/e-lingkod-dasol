<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GovernmentIdFormat implements ValidationRule
{
    private string $idType;

    public function __construct(string $idType = 'general')
    {
        $this->idType = $idType;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow null/empty values, let 'required' rule handle it
        }

        $value = trim($value);

        switch ($this->idType) {
            case 'sss':
                $this->validateSSS($value, $fail);
                break;
            case 'gsis':
                $this->validateGSIS($value, $fail);
                break;
            case 'philhealth':
                $this->validatePhilhealth($value, $fail);
                break;
            case 'pagibig':
                $this->validatePagibig($value, $fail);
                break;
            case 'tin':
                $this->validateTIN($value, $fail);
                break;
            case 'psa':
                $this->validatePSA($value, $fail);
                break;
            default:
                $this->validateGeneral($value, $fail);
                break;
        }
    }

    /**
     * Validate SSS number format
     */
    private function validateSSS(string $value, Closure $fail): void
    {
        // SSS format: XX-XXXXXXX-X (10 digits total) - supports both formatted and unformatted
        if (!preg_match('/^(?:\d{10}|\d{2}-\d{7}-\d{1})$/', $value)) {
            $fail('The :attribute must be a valid SSS number format (10 digits, e.g., 12-3456789-0 or 1234567890).');
        }
    }

    /**
     * Validate GSIS number format
     */
    private function validateGSIS(string $value, Closure $fail): void
    {
        // GSIS formats:
        // - BP No: 10 digits
        // - GSIS ID No: 11 digits
        // - UMID CRN: 12 digits with optional dashes (XXXX-XXX-XXX-X or XXXXXXXXXXXX)
        if (!preg_match('/^(?:\d{10}|\d{11}|\d{12}|\d{4}-\d{3}-\d{3}-\d{1})$/', $value)) {
            $fail('The :attribute must be a valid GSIS number format (BP No: 10 digits, GSIS ID: 11 digits, or UMID CRN: 12 digits).');
        }
    }

    /**
     * Validate PhilHealth number format
     */
    private function validatePhilhealth(string $value, Closure $fail): void
    {
        // PhilHealth format: XX-XXXXXXXXX-X (12 digits total) - supports both formatted and unformatted
        if (!preg_match('/^(?:\d{12}|\d{2}-\d{9}-\d{1})$/', $value)) {
            $fail('The :attribute must be a valid PhilHealth number format (12 digits, e.g., 12-345678912-3 or 123456789123).');
        }
    }

    /**
     * Validate Pag-IBIG number format
     */
    private function validatePagibig(string $value, Closure $fail): void
    {
        // Pag-IBIG format: XXXX-XXXX-XXXX (12 digits) - supports both formatted and unformatted
        if (!preg_match('/^(?:\d{12}|\d{4}-\d{4}-\d{4})$/', $value)) {
            $fail('The :attribute must be a valid Pag-IBIG number format (12 digits, e.g., 1234-5678-9012 or 123456789012).');
        }
    }

    /**
     * Validate TIN number format
     */
    private function validateTIN(string $value, Closure $fail): void
    {
        // TIN format:
        // - Individuals: XXX-XXX-XXX (9 digits)
        // - Entities with branch codes: XXX-XXX-XXX-XXX (12-14 digits total)
        // Supports both formatted and unformatted
        if (!preg_match('/^(?:\d{9}|\d{12}|\d{13}|\d{14}|\d{3}-\d{3}-\d{3}|\d{3}-\d{3}-\d{3}-\d{3}|\d{3}-\d{3}-\d{3}-\d{4}|\d{3}-\d{3}-\d{3}-\d{5})$/', $value)) {
            $fail('The :attribute must be a valid TIN number format (9 digits for individuals, 12-14 digits for entities with branch codes).');
        }
    }

    /**
     * Validate PSA/PhilID number format
     */
    private function validatePSA(string $value, Closure $fail): void
    {
        // PSA format: XXXX-XXXX-XXXX (12 digits typical for PhilID)
        if (!preg_match('/^\d{12}$/', $value)) {
            $fail('The :attribute must be a valid PSA/PhilID number format (12 digits).');
        }
    }

    /**
     * Validate general government ID format
     */
    private function validateGeneral(string $value, Closure $fail): void
    {
        // General validation: should be alphanumeric and reasonable length
        if (strlen($value) < 8 || strlen($value) > 25) {
            $fail('The :attribute must be between 8 and 25 characters.');
        }

        if (!preg_match('/^[A-Z0-9]+$/', $value)) {
            $fail('The :attribute must contain only letters and numbers.');
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        $messages = [
            'sss' => 'The :attribute must be a valid SSS number format (10 digits, e.g., 12-3456789-0 or 1234567890).',
            'gsis' => 'The :attribute must be a valid GSIS number format (BP No: 10 digits, GSIS ID: 11 digits, or UMID CRN: 12 digits).',
            'philhealth' => 'The :attribute must be a valid PhilHealth number format (12 digits, e.g., 12-345678912-3 or 123456789123).',
            'pagibig' => 'The :attribute must be a valid Pag-IBIG number format (12 digits, e.g., 1234-5678-9012 or 123456789012).',
            'tin' => 'The :attribute must be a valid TIN number format (9 digits for individuals, 12-14 digits for entities with branch codes).',
            'psa' => 'The :attribute must be a valid PSA/PhilID number format (12 digits).',
        ];

        return $messages[$this->idType] ?? 'The :attribute must be a valid government ID format.';
    }
}