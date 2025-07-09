<?php

namespace App\Http\Controllers;

use App\Services\CSCReportingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * CSC Report Controller
 * 
 * Handles Philippine Civil Service Commission reporting endpoints
 */
class CSCReportController extends Controller
{
    private CSCReportingService $cscReportingService;

    public function __construct(CSCReportingService $cscReportingService)
    {
        $this->cscReportingService = $cscReportingService;
        
        // Apply middleware for authorization
        $this->middleware(['auth', 'permission:report.view']);
        $this->middleware('permission:report.export')->only(['exportPDF', 'exportExcel']);
    }

    /**
     * Generate Monthly Accession Report
     */
    public function monthlyAccessionReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . (now()->year + 1),
                'month' => 'required|integer|min:1|max:12',
                'department' => 'nullable|string|max:255',
            ]);

            $report = $this->cscReportingService->generateMonthlyAccessionReport(
                $validated['year'],
                $validated['month'],
                $validated['department'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSC Accession Report generation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate Monthly Separation Report
     */
    public function monthlySeparationReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . (now()->year + 1),
                'month' => 'required|integer|min:1|max:12',
                'department' => 'nullable|string|max:255',
            ]);

            $report = $this->cscReportingService->generateMonthlySeparationReport(
                $validated['year'],
                $validated['month'],
                $validated['department'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSC Separation Report generation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate Monthly DIBAR Report
     */
    public function monthlyDIBARReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . (now()->year + 1),
                'month' => 'required|integer|min:1|max:12',
                'department' => 'nullable|string|max:255',
            ]);

            $report = $this->cscReportingService->generateMonthlyDIBARReport(
                $validated['year'],
                $validated['month'],
                $validated['department'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSC DIBAR Report generation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate Monthly Sexual Harassment Cases Report
     */
    public function monthlySexualHarassmentReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . (now()->year + 1),
                'month' => 'required|integer|min:1|max:12',
                'department' => 'nullable|string|max:255',
            ]);

            $report = $this->cscReportingService->generateMonthlySexualHarassmentReport(
                $validated['year'],
                $validated['month'],
                $validated['department'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSC Sexual Harassment Report generation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate Annual IGHR Report
     */
    public function annualIGHRReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2000|max:' . (now()->year + 1),
                'department' => 'nullable|string|max:255',
            ]);

            $report = $this->cscReportingService->generateAnnualIGHRReport(
                $validated['year'],
                $validated['department'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSC IGHR Report generation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export report to PDF
     */
    public function exportPDF(Request $request): Response|JsonResponse
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|string|in:accession,separation,dibar,harassment,ighr',
                'year' => 'required|integer|min:2000|max:' . (now()->year + 1),
                'month' => 'nullable|integer|min:1|max:12',
                'department' => 'nullable|string|max:255',
            ]);

            // Generate the report data based on type
            $reportData = $this->generateReportByType($validated);

            return $this->cscReportingService->exportToPDF($reportData, $validated['report_type']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSC Report PDF export failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export report to Excel
     */
    public function exportExcel(Request $request): Response|JsonResponse
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|string|in:accession,separation,dibar,harassment,ighr',
                'year' => 'required|integer|min:2000|max:' . (now()->year + 1),
                'month' => 'nullable|integer|min:1|max:12',
                'department' => 'nullable|string|max:255',
            ]);

            // Generate the report data based on type
            $reportData = $this->generateReportByType($validated);

            return $this->cscReportingService->exportToExcel($reportData, $validated['report_type']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CSC Report Excel export failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Clear report cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'report_type' => 'nullable|string|in:accession,separation,dibar,harassment,ighr',
            ]);

            $this->cscReportingService->clearReportCache($validated['report_type'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('CSC Report cache clear failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get available departments for filtering
     */
    public function getDepartments(): JsonResponse
    {
        try {
            $departments = \App\Models\Employee::distinct()
                ->whereNotNull('department')
                ->pluck('department')
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $departments,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get departments', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get departments',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get report validation errors
     */
    public function validateReportParameters(Request $request): JsonResponse
    {
        try {
            $errors = $this->cscReportingService->validateReportParameters($request->all());

            return response()->json([
                'success' => empty($errors),
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('CSC Report validation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate report data based on type
     */
    private function generateReportByType(array $validated): array
    {
        switch ($validated['report_type']) {
            case 'accession':
                return $this->cscReportingService->generateMonthlyAccessionReport(
                    $validated['year'],
                    $validated['month'],
                    $validated['department'] ?? null
                );

            case 'separation':
                return $this->cscReportingService->generateMonthlySeparationReport(
                    $validated['year'],
                    $validated['month'],
                    $validated['department'] ?? null
                );

            case 'dibar':
                return $this->cscReportingService->generateMonthlyDIBARReport(
                    $validated['year'],
                    $validated['month'],
                    $validated['department'] ?? null
                );

            case 'harassment':
                return $this->cscReportingService->generateMonthlySexualHarassmentReport(
                    $validated['year'],
                    $validated['month'],
                    $validated['department'] ?? null
                );

            case 'ighr':
                return $this->cscReportingService->generateAnnualIGHRReport(
                    $validated['year'],
                    $validated['department'] ?? null
                );

            default:
                throw new \InvalidArgumentException('Invalid report type');
        }
    }
}