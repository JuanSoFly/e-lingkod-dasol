<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmployeeNumberService;
use Illuminate\Http\JsonResponse;

class EmployeeNumberController extends Controller
{
    public function __construct(
        private EmployeeNumberService $employeeNumberService
    ) {}

    /**
     * Get next available employee number
     */
    public function getNextNumber(): JsonResponse
    {
        try {
            $nextNumber = $this->employeeNumberService->getNextAvailableNumber();

            return response()->json([
                'success' => true,
                'employee_number' => $nextNumber,
                'format' => 'DAS-YYYY-XXXX',
                'example' => $nextNumber
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate employee number',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}