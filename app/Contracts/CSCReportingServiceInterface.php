<?php

namespace App\Contracts;

use Illuminate\Http\Response;

/**
 * CSC Reporting Service Interface
 * 
 * Defines the contract for Philippine Civil Service Commission reporting requirements
 */
interface CSCReportingServiceInterface
{
    /**
     * Generate Monthly Accession Report
     * 
     * @param int $year
     * @param int $month
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateMonthlyAccessionReport(int $year, int $month, ?string $department = null): array;

    /**
     * Generate Monthly Separation Report
     * 
     * @param int $year
     * @param int $month
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateMonthlySeparationReport(int $year, int $month, ?string $department = null): array;

    /**
     * Generate Monthly DIBAR Report (Dropped from the Rolls)
     * 
     * @param int $year
     * @param int $month
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateMonthlyDIBARReport(int $year, int $month, ?string $department = null): array;

    /**
     * Generate Monthly Sexual Harassment Cases Report
     * 
     * @param int $year
     * @param int $month
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateMonthlySexualHarassmentReport(int $year, int $month, ?string $department = null): array;

    /**
     * Generate Annual IGHR Report (Inventory of Government Human Resources)
     * 
     * @param int $year
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateAnnualIGHRReport(int $year, ?string $department = null): array;

    /**
     * Export report to PDF
     * 
     * @param array $reportData
     * @param string $reportType
     * @return Response
     */
    public function exportToPDF(array $reportData, string $reportType): Response;

    /**
     * Export report to Excel
     * 
     * @param array $reportData
     * @param string $reportType
     * @return Response
     */
    public function exportToExcel(array $reportData, string $reportType): Response;

    /**
     * Validate report parameters
     * 
     * @param array $params
     * @return array Validation errors
     */
    public function validateReportParameters(array $params): array;

    /**
     * Clear report caches
     * 
     * @param string|null $reportType Specific report type to clear, null for all
     */
    public function clearReportCache(?string $reportType = null): void;
}