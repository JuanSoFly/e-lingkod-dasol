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

        $cleanValue = preg_replace('/[\s\-\.\#]+/', '', strtoupper(trim($value)));

        switch ($this->idType) {
            case 'sss':
                $this->validateSSS($cleanValue, $fail);
                break;
            case 'gsis':
                $this->validateGSIS($cleanValue, $fail);
                break;
            case 'philhealth':
                $this->validatePhilhealth($cleanValue, $fail);
                break;
            case 'pagibig':
                $this->validatePagibig($cleanValue, $fail);
                break;
            case 'tin':
                $this->validateTIN($cleanValue, $fail);
                break;
            case 'psa':
                $this->validatePSA($cleanValue, $fail);
                break;
            default:
                $this->validateGeneral($cleanValue, $fail);
                break;
        }
    }

    /**
     * Validate SSS number format
     */
    private function validateSSS(string $value, Closure $fail): void
    {
        // SSS format: XX-XXXXXXX-X (10 digits total)
        if (!preg_match('/^\d{2}\d{7}\d{1}$/', $value)) {
            $fail('The :attribute must be a valid SSS number format (10 digits).');
        }
    }

    /**
     * Validate GSIS number format
     */
    private function validateGSIS(string $value, Closure $fail): void
    {
        // GSIS format: XXXXXXXXXX-X (11 digits + 1 check digit)
        if (!preg_match('/^\d{11}\d{1}$/', $value)) {
            $fail('The :attribute must be a valid GSIS number format (12 digits).');
        }
    }

    /**
     * Validate PhilHealth number format
     */
    private function validatePhilhealth(string $value, Closure $fail): void
    {
        // PhilHealth format: XX-XXXXXXXXX-X (12 digits total)
        if (!preg_match('/^\d{2}\d{9}\d{1}$/', $value)) {
            $fail('The :attribute must be a valid PhilHealth number format (12 digits).');
        }
    }

    /**
     * Validate Pag-IBIG number format
     */
    private function validatePagibig(string $value, Closure $fail): void
    {
        // Pag-IBIG format: XXXX-XXXX-XXXX (12 digits)
        if (!preg_match('/^\d{4}\d{4}\d{4}$/', $value)) {
            $fail('The :attribute must be a valid Pag-IBIG number format (12 digits).');
        }
    }

    /**
     * Validate TIN number format
     */
    private function validateTIN(string $value, Closure $fail): void
    {
        // TIN format: XXX-XXX-XXX-XXX (12 digits) or XXX-XXX-XXX (9 digits)
        if (!preg_match('/^\d{9}$|^\d{12}$/', $value)) {
            $fail('The :attribute must be a valid TIN number format (9 or 12 digits).');
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
            'sss' => 'The :attribute must be a valid SSS number format (10 digits).',
            'gsis' => 'The :attribute must be a valid GSIS number format (12 digits).',
            'philhealth' => 'The :attribute must be a valid PhilHealth number format (12 digits).',
            'pagibig' => 'The :attribute must be a valid Pag-IBIG number format (12 digits).',
            'tin' => 'The :attribute must be a valid TIN number format (9 or 12 digits).',
            'psa' => 'The :attribute must be a valid PSA/PhilID number format (12 digits).',
        ];

        return $messages[$this->idType] ?? 'The :attribute must be a valid government ID format.';
    }
}