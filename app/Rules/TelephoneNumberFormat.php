<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneNumberFormat implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow null/empty values, let 'required' rule handle it
        }

        // Remove common formatting characters for validation
        $cleanNumber = preg_replace('/[\s\-\(\)]+/', '', trim($value));

        // Philippine telephone number formats:
        // - Local: 7 digits (e.g., 1234567)
        // - With area code: 8-11 digits (e.g., 021234567, 0212345678, 02123456789)
        // - Mobile: 10-11 digits starting with 09 or +639
        if (!preg_match('/^(\d{7,11}|09\d{8,9})$/', $cleanNumber)) {
            $fail('The :attribute must be a valid Philippine telephone number format.');
        }

        // Additional validation for area codes
        if (strlen($cleanNumber) >= 8 && !str_starts_with($cleanNumber, '09')) {
            $areaCode = substr($cleanNumber, 0, 2);
            $validAreaCodes = [
                '02', // Metro Manila
                '032', // Cebu
                '033', // Iloilo
                '034', // Bacolod
                '035', // Dumaguete
                '036', // Puerto Princesa
                '041', // Batangas
                '042', // Lucena
                '043', // Occidental Mindoro
                '044', // Oriental Mindoro
                '045', // Pampanga
                '046', // Marinduque, Romblon
                '047', // Tarlac
                '048', // Palawan
                '049', // Quezon
                '052', // Legazpi
                '053', // Catanduanes, Masbate
                '054', // Sorsogon
                '055', // Naga
                '056', // Daet
                '061', // Zamboanga
                '062', // Tawi-tawi, Sulu
                '063', // Basilan
                '064', // Lamitan
                '065', // Dipolog
                '066', // Pagadian
                '067', // Ozamiz
                '068', // Iligan
                '069', // Butuan
                '072', // Tuguegarao
                '073', // Baguio
                '074', // Laoag
                '075', // San Fernando
                '076', // Vigan
                '077', // San Fernando
                '078', // Tuguegarao
                '082', // Davao
                '083', // General Santos
                '084', // Tagum
                '085', // Surigao
                '086', // Bislig
                '087', // Malaybalay
                '088', // Cagayan de Oro
                '089', // Iligan
            ];

            // Check if area code is valid
            $areaCode = strlen($cleanNumber) >= 9 ? substr($cleanNumber, 0, 3) : substr($cleanNumber, 0, 2);
            if (!in_array($areaCode, $validAreaCodes)) {
                $fail('The :attribute contains an invalid Philippine area code.');
            }
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute must be a valid Philippine telephone number (e.g., "02-1234-5678" or "09123456789").';
    }
}