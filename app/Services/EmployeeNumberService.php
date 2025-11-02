<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeNumberService
{
    private const PREFIX = 'DAS';
    private const YEAR_FORMAT = 'Y';

    /**
     * Generate a unique employee number
     * Format: DAS-YYYY-XXXX (e.g., DAS-2025-0001)
     */
    public function generateUniqueNumber(): string
    {
        return $this->generateUniqueNumberWithDepth(0);
    }

    /**
     * Generate unique employee number with recursion depth protection
     */
    private function generateUniqueNumberWithDepth(int $depth): string
    {
        // Prevent infinite recursion
        if ($depth >= 10) {
            throw new \Exception('Unable to generate unique employee number after 10 attempts. Please check the system.');
        }

        return DB::transaction(function () use ($depth) {
            $year = now()->format(self::YEAR_FORMAT);
            $prefix = self::PREFIX . '-' . $year;

            // Get the last sequence number for this year
            $lastEmployee = DB::table('employees')
                ->where('employee_number', 'like', $prefix . '%')
                ->lockForUpdate() // Prevent race conditions
                ->orderByRaw('CAST(SUBSTRING(employee_number, 10) AS UNSIGNED) DESC')
                ->first();

            $nextSequence = 1;

            if ($lastEmployee) {
                $lastSequence = (int) substr($lastEmployee->employee_number, -4);
                $nextSequence = $lastSequence + 1;
            }

            $employeeNumber = $prefix . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            // Double-check uniqueness
            if (DB::table('employees')->where('employee_number', $employeeNumber)->exists()) {
                Log::warning('Employee number collision detected, regenerating', [
                    'attempted_number' => $employeeNumber,
                    'depth' => $depth + 1,
                    'last_employee_number' => $lastEmployee->employee_number ?? 'none'
                ]);
                return $this->generateUniqueNumberWithDepth($depth + 1); // Recursive retry with depth tracking
            }

            Log::info('Generated unique employee number', [
                'employee_number' => $employeeNumber,
                'sequence' => $nextSequence,
                'year' => $year,
                'depth' => $depth
            ]);

            return $employeeNumber;
        });
    }

    /**
     * Validate if an employee number follows the correct format
     */
    public function isValidFormat(string $employeeNumber): bool
    {
        $pattern = '/^' . self::PREFIX . '-\d{4}-\d{4}$/';
        return preg_match($pattern, $employeeNumber) === 1;
    }

    /**
     * Extract year from employee number
     */
    public function extractYear(string $employeeNumber): ?string
    {
        if (!$this->isValidFormat($employeeNumber)) {
            return null;
        }

        return substr($employeeNumber, 4, 4);
    }

    /**
     * Get next available employee number without actually using it
     */
    public function getNextAvailableNumber(): string
    {
        try {
            $year = now()->format(self::YEAR_FORMAT);
            $prefix = self::PREFIX . '-' . $year;

            $lastEmployee = DB::table('employees')
                ->where('employee_number', 'like', $prefix . '%')
                ->orderByRaw('CAST(SUBSTRING(employee_number, 10) AS UNSIGNED) DESC')
                ->first();

            $nextSequence = 1;
            if ($lastEmployee) {
                $lastSequence = (int) substr($lastEmployee->employee_number, -4);
                $nextSequence = $lastSequence + 1;
            }

            return $prefix . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            Log::error('Error generating next available employee number', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback to timestamp-based number
            return $prefix . '-' . str_pad(now()->timestamp % 10000, 4, '0', STR_PAD_LEFT);
        }
    }
}