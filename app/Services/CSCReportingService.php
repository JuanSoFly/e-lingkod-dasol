<?php

namespace App\Services;

use App\Contracts\CSCReportingServiceInterface;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\PerformanceReview;
use App\Models\SexualHarassmentCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Response;

/**
 * CSC (Civil Service Commission) Reporting Service
 * 
 * Handles all Philippine Civil Service Commission reporting requirements including:
 * - Monthly Accession Report (new hires, appointments, transfers-in)
 * - Monthly Separation Report (resignations, retirements, terminations, transfers-out)
 * - Monthly DIBAR Report (Dropped from the Rolls - AWOL 30+ days)
 * - Monthly Sexual Harassment Cases Report
 * - Annual IGHR Report (Inventory of Government Human Resources)
 */
class CSCReportingService implements CSCReportingServiceInterface
{
    /**
     * Cache duration in minutes for report data
     */
    private const CACHE_DURATION = 15;

    /**
     * CSC Employment Status Mappings
     */
    private const CSC_EMPLOYMENT_STATUS = [
        'permanent' => 'Permanent',
        'temporary' => 'Temporary',
        'contractual' => 'Contractual',
        'casual' => 'Casual',
        'probationary' => 'Probationary',
        'substitute' => 'Substitute',
        'job_order' => 'Job Order',
        'contract_of_service' => 'Contract of Service',
    ];

    /**
     * CSC Action Types for Accession Report
     */
    private const CSC_ACCESSION_ACTIONS = [
        'new_appointment' => 'New Appointment',
        'promotion' => 'Promotion',
        'transfer' => 'Transfer',
        'reinstatement' => 'Reinstatement',
        'reappointment' => 'Reappointment',
        'reassignment' => 'Reassignment',
        'detail' => 'Detail',
    ];

    /**
     * CSC Action Types for Separation Report
     */
    private const CSC_SEPARATION_ACTIONS = [
        'resignation' => 'Resignation',
        'retirement' => 'Retirement',
        'termination' => 'Termination',
        'dismissal' => 'Dismissal',
        'transfer_out' => 'Transfer Out',
        'expiration_of_term' => 'Expiration of Term',
        'death' => 'Death',
        'awol' => 'AWOL',
    ];

    /**
     * CSC Sexual Harassment Case Status
     */
    private const CSC_HARASSMENT_STATUS = [
        'filed' => 'Filed',
        'under_investigation' => 'Under Investigation',
        'dismissed' => 'Dismissed',
        'resolved' => 'Resolved',
        'forwarded_to_court' => 'Forwarded to Court',
        'pending_appeal' => 'Pending Appeal',
    ];

    /**
     * Generate Monthly Accession Report
     * 
     * @param int $year
     * @param int $month
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateMonthlyAccessionReport(int $year, int $month, ?string $department = null): array
    {
        $cacheKey = "csc_accession_report_{$year}_{$month}" . ($department ? "_{$department}" : '');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($year, $month, $department) {
            try {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();

                $query = Employee::whereBetween('date_hired', [$startDate, $endDate])
                    ->whereNotNull('date_hired');

                if ($department) {
                    $query->where('department', $department);
                }

                $newHires = $query->get();

                // Group by employment status and action type
                $accessionData = $newHires->groupBy('employment_status')->map(function ($employees, $status) {
                    return [
                        'employment_status' => self::CSC_EMPLOYMENT_STATUS[$status] ?? $status,
                        'count' => $employees->count(),
                        'employees' => $employees->map(function ($employee) {
                            return [
                                'employee_number' => $employee->employee_number,
                                'full_name' => $this->getFullName($employee),
                                'position' => $employee->position,
                                'department' => $employee->department,
                                'salary_grade' => $employee->salary_grade,
                                'step_increment' => $employee->step_increment,
                                'date_hired' => $employee->date_hired->format('Y-m-d'),
                                'action_type' => 'New Appointment', // Default for new hires
                            ];
                        })->toArray(),
                    ];
                })->values()->toArray();

                return [
                    'report_type' => 'Monthly Accession Report',
                    'period' => $startDate->format('F Y'),
                    'department' => $department ?? 'All Departments',
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'total_accessions' => $newHires->count(),
                    'data' => $accessionData,
                    'summary' => $this->generateAccessionSummary($accessionData),
                ];
            } catch (\Exception $e) {
                Log::error('CSC Accession Report generation failed', [
                    'error' => $e->getMessage(),
                    'year' => $year,
                    'month' => $month,
                    'department' => $department,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate Monthly Separation Report
     * 
     * @param int $year
     * @param int $month
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateMonthlySeparationReport(int $year, int $month, ?string $department = null): array
    {
        $cacheKey = "csc_separation_report_{$year}_{$month}" . ($department ? "_{$department}" : '');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($year, $month, $department) {
            try {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();

                // Note: This assumes we have a separation_date field or use deleted_at for separations
                // In the current schema, we'll use deleted_at as separation date
                $query = Employee::onlyTrashed()
                    ->whereBetween('deleted_at', [$startDate, $endDate]);

                if ($department) {
                    $query->where('department', $department);
                }

                $separatedEmployees = $query->get();

                // For now, we'll classify all as resignations since we don't have separation reason field
                // This should be enhanced with a proper separation_reason field in the employees table
                $separationData = $separatedEmployees->groupBy(function ($employee) {
                    // This is a placeholder - should be based on actual separation reason
                    return 'resignation';
                })->map(function ($employees, $reason) {
                    return [
                        'separation_type' => self::CSC_SEPARATION_ACTIONS[$reason] ?? $reason,
                        'count' => $employees->count(),
                        'employees' => $employees->map(function ($employee) {
                            return [
                                'employee_number' => $employee->employee_number,
                                'full_name' => $this->getFullName($employee),
                                'position' => $employee->position,
                                'department' => $employee->department,
                                'salary_grade' => $employee->salary_grade,
                                'date_hired' => $employee->date_hired->format('Y-m-d'),
                                'separation_date' => $employee->deleted_at->format('Y-m-d'),
                                'length_of_service' => $this->calculateLengthOfService($employee->date_hired, $employee->deleted_at),
                                'separation_reason' => 'Resignation', // Default - should be from database
                            ];
                        })->toArray(),
                    ];
                })->values()->toArray();

                return [
                    'report_type' => 'Monthly Separation Report',
                    'period' => $startDate->format('F Y'),
                    'department' => $department ?? 'All Departments',
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'total_separations' => $separatedEmployees->count(),
                    'data' => $separationData,
                    'summary' => $this->generateSeparationSummary($separationData),
                ];
            } catch (\Exception $e) {
                Log::error('CSC Separation Report generation failed', [
                    'error' => $e->getMessage(),
                    'year' => $year,
                    'month' => $month,
                    'department' => $department,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate Monthly DIBAR Report (Dropped from the Rolls)
     * Tracks employees AWOL for 30+ days
     * 
     * @param int $year
     * @param int $month
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateMonthlyDIBARReport(int $year, int $month, ?string $department = null): array
    {
        $cacheKey = "csc_dibar_report_{$year}_{$month}" . ($department ? "_{$department}" : '');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($year, $month, $department) {
            try {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();

                // Find employees who have been on unauthorized absence for 30+ days
                // This requires tracking attendance data - for now we'll use leave applications
                // that are overdue and not approved as a proxy
                $awolThreshold = 30; // days
                $cutoffDate = $endDate->copy()->subDays($awolThreshold);

                $query = Employee::where('employment_status', 'active');

                if ($department) {
                    $query->where('department', $department);
                }

                $activeEmployees = $query->get();

                // Check for employees who might be AWOL based on leave patterns
                // This is a simplified logic - should be enhanced with proper attendance tracking
                $dibarCandidates = $activeEmployees->filter(function ($employee) use ($cutoffDate, $endDate) {
                    // Check if employee has any recent leave applications or attendance
                    $recentLeaves = $employee->leaveApplications()
                        ->where('status', 'approved')
                        ->where('end_date', '>=', $cutoffDate)
                        ->exists();

                    // If no recent approved leaves and it's been over 30 days, potential AWOL
                    // This is simplified logic - in real implementation, you'd check attendance records
                    return !$recentLeaves;
                });

                $dibarData = $dibarCandidates->map(function ($employee) use ($cutoffDate) {
                    return [
                        'employee_number' => $employee->employee_number,
                        'full_name' => $this->getFullName($employee),
                        'position' => $employee->position,
                        'department' => $employee->department,
                        'salary_grade' => $employee->salary_grade,
                        'date_hired' => $employee->date_hired->format('Y-m-d'),
                        'last_attendance_date' => $cutoffDate->format('Y-m-d'), // Placeholder
                        'days_absent' => now()->diffInDays($cutoffDate),
                        'status' => 'Potential AWOL',
                        'action_recommended' => 'Review for DIBAR',
                    ];
                })->toArray();

                return [
                    'report_type' => 'Monthly DIBAR Report (Dropped from the Rolls)',
                    'period' => $startDate->format('F Y'),
                    'department' => $department ?? 'All Departments',
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'total_cases' => count($dibarData),
                    'awol_threshold_days' => $awolThreshold,
                    'data' => $dibarData,
                    'summary' => [
                        'total_potential_awol' => count($dibarData),
                        'departments_affected' => collect($dibarData)->pluck('department')->unique()->count(),
                        'recommendations' => [
                            'immediate_review' => count($dibarData),
                            'further_investigation' => 0,
                            'ready_for_dibar' => 0,
                        ],
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('CSC DIBAR Report generation failed', [
                    'error' => $e->getMessage(),
                    'year' => $year,
                    'month' => $month,
                    'department' => $department,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate Monthly Sexual Harassment Cases Report
     * 
     * @param int $year
     * @param int $month
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateMonthlySexualHarassmentReport(int $year, int $month, ?string $department = null): array
    {
        $cacheKey = "csc_harassment_report_{$year}_{$month}" . ($department ? "_{$department}" : '');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($year, $month, $department) {
            try {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();

                // Get cases filed in the month
                $newCasesQuery = SexualHarassmentCase::filedInMonth($year, $month);
                if ($department) {
                    $newCasesQuery->byDepartment($department);
                }
                $newCases = $newCasesQuery->get();

                // Get cases resolved in the month
                $resolvedCasesQuery = SexualHarassmentCase::resolvedInMonth($year, $month);
                if ($department) {
                    $resolvedCasesQuery->byDepartment($department);
                }
                $resolvedCases = $resolvedCasesQuery->get();

                // Get ongoing cases (filed before or during the month but not resolved)
                $ongoingCasesQuery = SexualHarassmentCase::where('filed_date', '<=', $endDate)
                    ->where(function ($query) use ($endDate) {
                        $query->whereNull('resolution_date')
                              ->orWhere('resolution_date', '>', $endDate);
                    })
                    ->pending();
                if ($department) {
                    $ongoingCasesQuery->byDepartment($department);
                }
                $ongoingCases = $ongoingCasesQuery->get();

                // Get dismissed cases in the month
                $dismissedCasesQuery = SexualHarassmentCase::byStatus(SexualHarassmentCase::STATUS_DISMISSED)
                    ->whereYear('resolution_date', $year)
                    ->whereMonth('resolution_date', $month);
                if ($department) {
                    $dismissedCasesQuery->byDepartment($department);
                }
                $dismissedCases = $dismissedCasesQuery->get();

                // Get cases forwarded to court in the month
                $courtCasesQuery = SexualHarassmentCase::where('forwarded_to_court', true)
                    ->whereYear('court_filing_date', $year)
                    ->whereMonth('court_filing_date', $month);
                if ($department) {
                    $courtCasesQuery->byDepartment($department);
                }
                $courtCases = $courtCasesQuery->get();

                // Get pending appeals
                $pendingAppealsQuery = SexualHarassmentCase::byStatus(SexualHarassmentCase::STATUS_PENDING_APPEAL);
                if ($department) {
                    $pendingAppealsQuery->byDepartment($department);
                }
                $pendingAppeals = $pendingAppealsQuery->get();

                return [
                    'report_type' => 'Monthly Sexual Harassment Cases Report',
                    'period' => $startDate->format('F Y'),
                    'department' => $department ?? 'All Departments',
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'total_cases' => $newCases->count() + $ongoingCases->count(),
                    'data' => [
                        'new_cases' => $this->formatHarassmentCases($newCases),
                        'ongoing_cases' => $this->formatHarassmentCases($ongoingCases),
                        'resolved_cases' => $this->formatHarassmentCases($resolvedCases),
                        'dismissed_cases' => $this->formatHarassmentCases($dismissedCases),
                        'court_cases' => $this->formatHarassmentCases($courtCases),
                        'pending_appeals' => $this->formatHarassmentCases($pendingAppeals),
                    ],
                    'summary' => [
                        'new_cases_filed' => $newCases->count(),
                        'cases_under_investigation' => $ongoingCases->where('case_status', SexualHarassmentCase::STATUS_UNDER_INVESTIGATION)->count(),
                        'cases_resolved' => $resolvedCases->where('case_status', SexualHarassmentCase::STATUS_RESOLVED)->count(),
                        'cases_dismissed' => $dismissedCases->count(),
                        'cases_forwarded_to_court' => $courtCases->count(),
                        'pending_appeals' => $pendingAppeals->count(),
                        'average_resolution_days' => $this->calculateAverageResolutionDays($resolvedCases),
                    ],
                    'statistics' => [
                        'by_status' => $this->getHarassmentCasesByStatus($year, $month, $department),
                        'by_department' => $this->getHarassmentCasesByDepartment($year, $month),
                        'resolution_types' => $this->getHarassmentResolutionTypes($year, $month, $department),
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('CSC Sexual Harassment Report generation failed', [
                    'error' => $e->getMessage(),
                    'year' => $year,
                    'month' => $month,
                    'department' => $department,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate Annual IGHR Report (Inventory of Government Human Resources)
     * 
     * @param int $year
     * @param string|null $department Filter by department
     * @return array
     */
    public function generateAnnualIGHRReport(int $year, ?string $department = null): array
    {
        $cacheKey = "csc_ighr_report_{$year}" . ($department ? "_{$department}" : '');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($year, $department) {
            try {
                $startDate = Carbon::create($year, 1, 1)->startOfYear();
                $endDate = Carbon::create($year, 12, 31)->endOfYear();

                $query = Employee::whereYear('date_hired', '<=', $year);

                if ($department) {
                    $query->where('department', $department);
                }

                $employees = $query->get();

                // Analyze by employment status
                $byEmploymentStatus = $employees->groupBy('employment_status')->map(function ($group, $status) {
                    return [
                        'status' => self::CSC_EMPLOYMENT_STATUS[$status] ?? $status,
                        'count' => $group->count(),
                        'percentage' => 0, // Will be calculated below
                    ];
                });

                // Analyze by department
                $byDepartment = $employees->groupBy('department')->map(function ($group, $dept) {
                    return [
                        'department' => $dept,
                        'count' => $group->count(),
                        'percentage' => 0, // Will be calculated below
                    ];
                });

                // Analyze by salary grade
                $bySalaryGrade = $employees->groupBy('salary_grade')->map(function ($group, $grade) {
                    return [
                        'salary_grade' => $grade,
                        'count' => $group->count(),
                        'percentage' => 0, // Will be calculated below
                    ];
                })->sortBy('salary_grade');

                // Analyze by gender
                $byGender = $employees->groupBy('gender')->map(function ($group, $gender) {
                    return [
                        'gender' => $gender,
                        'count' => $group->count(),
                        'percentage' => 0, // Will be calculated below
                    ];
                });

                // Calculate percentages
                $totalEmployees = $employees->count();
                if ($totalEmployees > 0) {
                    $byEmploymentStatus = $byEmploymentStatus->map(function ($item) use ($totalEmployees) {
                        $item['percentage'] = round(($item['count'] / $totalEmployees) * 100, 2);
                        return $item;
                    });

                    $byDepartment = $byDepartment->map(function ($item) use ($totalEmployees) {
                        $item['percentage'] = round(($item['count'] / $totalEmployees) * 100, 2);
                        return $item;
                    });

                    $bySalaryGrade = $bySalaryGrade->map(function ($item) use ($totalEmployees) {
                        $item['percentage'] = round(($item['count'] / $totalEmployees) * 100, 2);
                        return $item;
                    });

                    $byGender = $byGender->map(function ($item) use ($totalEmployees) {
                        $item['percentage'] = round(($item['count'] / $totalEmployees) * 100, 2);
                        return $item;
                    });
                }

                // Performance statistics
                $performanceStats = $this->getPerformanceStatistics($year, $department);

                return [
                    'report_type' => 'Annual IGHR Report (Inventory of Government Human Resources)',
                    'year' => $year,
                    'department' => $department ?? 'All Departments',
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'total_employees' => $totalEmployees,
                    'demographics' => [
                        'by_employment_status' => $byEmploymentStatus->values()->toArray(),
                        'by_department' => $byDepartment->values()->toArray(),
                        'by_salary_grade' => $bySalaryGrade->values()->toArray(),
                        'by_gender' => $byGender->values()->toArray(),
                    ],
                    'workforce_metrics' => [
                        'average_age' => $this->calculateAverageAge($employees),
                        'average_length_of_service' => $this->calculateAverageLengthOfService($employees),
                        'turnover_rate' => $this->calculateTurnoverRate($year, $department),
                        'new_hires_count' => $this->getNewHiresCount($year, $department),
                        'separations_count' => $this->getSeparationsCount($year, $department),
                    ],
                    'performance_metrics' => $performanceStats,
                    'recommendations' => $this->generateIGHRRecommendations($employees, $year),
                ];
            } catch (\Exception $e) {
                Log::error('CSC IGHR Report generation failed', [
                    'error' => $e->getMessage(),
                    'year' => $year,
                    'department' => $department,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Export report to PDF
     * 
     * @param array $reportData
     * @param string $reportType
     * @return Response
     */
    public function exportToPDF(array $reportData, string $reportType): Response
    {
        try {
            $pdf = Pdf::loadView('reports.csc.pdf_template', [
                'reportData' => $reportData,
                'reportType' => $reportType,
            ]);

            $filename = $this->generateFilename($reportType, $reportData);

            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            Log::error('CSC Report PDF export failed', [
                'error' => $e->getMessage(),
                'report_type' => $reportType,
            ]);
            throw $e;
        }
    }

    /**
     * Export report to Excel
     * 
     * @param array $reportData
     * @param string $reportType
     * @return Response
     */
    public function exportToExcel(array $reportData, string $reportType): Response
    {
        try {
            $filename = $this->generateFilename($reportType, $reportData);

            return Excel::download(
                new CSCReportExport($reportData, $reportType),
                $filename . '.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('CSC Report Excel export failed', [
                'error' => $e->getMessage(),
                'report_type' => $reportType,
            ]);
            throw $e;
        }
    }

    /**
     * Validate report parameters
     * 
     * @param array $params
     * @return array Validation errors
     */
    public function validateReportParameters(array $params): array
    {
        $errors = [];

        // Validate year
        if (!isset($params['year']) || !is_numeric($params['year'])) {
            $errors[] = 'Year is required and must be numeric';
        } elseif ($params['year'] < 2000 || $params['year'] > now()->year + 1) {
            $errors[] = 'Year must be between 2000 and ' . (now()->year + 1);
        }

        // Validate month for monthly reports
        if (isset($params['month'])) {
            if (!is_numeric($params['month']) || $params['month'] < 1 || $params['month'] > 12) {
                $errors[] = 'Month must be between 1 and 12';
            }
        }

        // Validate department
        if (isset($params['department']) && !empty($params['department'])) {
            $validDepartments = Employee::distinct()->pluck('department')->filter()->toArray();
            if (!in_array($params['department'], $validDepartments)) {
                $errors[] = 'Invalid department specified';
            }
        }

        return $errors;
    }

    /**
     * Clear report caches
     * 
     * @param string|null $reportType Specific report type to clear, null for all
     */
    public function clearReportCache(?string $reportType = null): void
    {
        $cachePatterns = [
            'csc_accession_report_',
            'csc_separation_report_',
            'csc_dibar_report_',
            'csc_harassment_report_',
            'csc_ighr_report_',
        ];

        if ($reportType) {
            $pattern = "csc_{$reportType}_report_";
            Cache::forget($pattern);
        } else {
            foreach ($cachePatterns as $pattern) {
                // Note: Laravel doesn't have a built-in way to clear by pattern
                // This is a simplified approach - in production, consider using tags
                Cache::flush();
                break;
            }
        }

        Log::info('CSC Report cache cleared', ['report_type' => $reportType ?? 'all']);
    }

    // =============================================================================
    // PRIVATE HELPER METHODS
    // =============================================================================

    /**
     * Get full name of employee
     */
    private function getFullName(Employee $employee): string
    {
        $name = $employee->first_name;
        if ($employee->middle_name) {
            $name .= ' ' . $employee->middle_name;
        }
        $name .= ' ' . $employee->last_name;
        return $name;
    }

    /**
     * Calculate length of service
     */
    private function calculateLengthOfService(Carbon $dateHired, Carbon $endDate): string
    {
        $diff = $endDate->diff($dateHired);
        
        $years = $diff->y;
        $months = $diff->m;
        $days = $diff->d;

        $parts = [];
        if ($years > 0) $parts[] = "{$years} year" . ($years > 1 ? 's' : '');
        if ($months > 0) $parts[] = "{$months} month" . ($months > 1 ? 's' : '');
        if ($days > 0) $parts[] = "{$days} day" . ($days > 1 ? 's' : '');

        return implode(', ', $parts) ?: '0 days';
    }

    /**
     * Generate accession summary
     */
    private function generateAccessionSummary(array $accessionData): array
    {
        $summary = [
            'total_by_status' => [],
            'highest_grade' => 0,
            'most_common_position' => '',
            'departments_affected' => 0,
        ];

        foreach ($accessionData as $statusGroup) {
            $summary['total_by_status'][$statusGroup['employment_status']] = $statusGroup['count'];
            
            foreach ($statusGroup['employees'] as $employee) {
                if ($employee['salary_grade'] > $summary['highest_grade']) {
                    $summary['highest_grade'] = $employee['salary_grade'];
                }
            }
        }

        return $summary;
    }

    /**
     * Generate separation summary
     */
    private function generateSeparationSummary(array $separationData): array
    {
        $summary = [
            'total_by_type' => [],
            'average_length_of_service' => '',
            'departments_affected' => 0,
        ];

        foreach ($separationData as $typeGroup) {
            $summary['total_by_type'][$typeGroup['separation_type']] = $typeGroup['count'];
        }

        return $summary;
    }

    /**
     * Calculate average age of employees
     */
    private function calculateAverageAge(Collection $employees): float
    {
        if ($employees->isEmpty()) {
            return 0;
        }

        $totalAge = $employees->sum(function ($employee) {
            return now()->diffInYears($employee->birth_date);
        });

        return round($totalAge / $employees->count(), 1);
    }

    /**
     * Calculate average length of service
     */
    private function calculateAverageLengthOfService(Collection $employees): string
    {
        if ($employees->isEmpty()) {
            return '0 years';
        }

        $totalDays = $employees->sum(function ($employee) {
            return now()->diffInDays($employee->date_hired);
        });

        $averageDays = $totalDays / $employees->count();
        $averageYears = round($averageDays / 365, 1);

        return "{$averageYears} years";
    }

    /**
     * Calculate turnover rate for the year
     */
    private function calculateTurnoverRate(int $year, ?string $department = null): float
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = Carbon::create($year, 12, 31)->endOfYear();

        $query = Employee::query();
        if ($department) {
            $query->where('department', $department);
        }

        $totalEmployees = $query->count();
        
        $separationsQuery = Employee::onlyTrashed()
            ->whereBetween('deleted_at', [$startDate, $endDate]);
        
        if ($department) {
            $separationsQuery->where('department', $department);
        }

        $separations = $separationsQuery->count();

        if ($totalEmployees == 0) {
            return 0;
        }

        return round(($separations / $totalEmployees) * 100, 2);
    }

    /**
     * Get new hires count for the year
     */
    private function getNewHiresCount(int $year, ?string $department = null): int
    {
        $query = Employee::whereYear('date_hired', $year);
        
        if ($department) {
            $query->where('department', $department);
        }

        return $query->count();
    }

    /**
     * Get separations count for the year
     */
    private function getSeparationsCount(int $year, ?string $department = null): int
    {
        $query = Employee::onlyTrashed()->whereYear('deleted_at', $year);
        
        if ($department) {
            $query->where('department', $department);
        }

        return $query->count();
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStatistics(int $year, ?string $department = null): array
    {
        $query = PerformanceReview::whereYear('review_date', $year);
        
        if ($department) {
            $query->whereHas('employee', function ($q) use ($department) {
                $q->where('department', $department);
            });
        }

        $reviews = $query->get();

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [],
            ];
        }

        $ratingDistribution = $reviews->groupBy('overall_rating')->map(function ($group) {
            return $group->count();
        })->toArray();

        return [
            'total_reviews' => $reviews->count(),
            'average_rating' => round($reviews->avg('overall_rating'), 2),
            'rating_distribution' => $ratingDistribution,
        ];
    }

    /**
     * Generate IGHR recommendations
     */
    private function generateIGHRRecommendations(Collection $employees, int $year): array
    {
        $recommendations = [];

        // Check for retirement eligibility
        $retirementEligible = $employees->filter(function ($employee) {
            $age = now()->diffInYears($employee->birth_date);
            $serviceYears = now()->diffInYears($employee->date_hired);
            return $age >= 60 || $serviceYears >= 30;
        })->count();

        if ($retirementEligible > 0) {
            $recommendations[] = "Consider succession planning for {$retirementEligible} employees eligible for retirement.";
        }

        // Check for training needs
        $newEmployees = $employees->filter(function ($employee) {
            return now()->diffInYears($employee->date_hired) < 2;
        })->count();

        if ($newEmployees > 0) {
            $recommendations[] = "Implement comprehensive training programs for {$newEmployees} relatively new employees.";
        }

        // Check for diversity
        $genderDistribution = $employees->groupBy('gender');
        $maleCount = $genderDistribution->get('Male', collect())->count();
        $femaleCount = $genderDistribution->get('Female', collect())->count();
        $total = $maleCount + $femaleCount;

        if ($total > 0) {
            $malePercentage = ($maleCount / $total) * 100;
            $femalePercentage = ($femaleCount / $total) * 100;

            if (abs($malePercentage - $femalePercentage) > 30) {
                $recommendations[] = "Consider gender diversity initiatives to balance workforce composition.";
            }
        }

        return $recommendations;
    }

    /**
     * Generate filename for exports
     */
    private function generateFilename(string $reportType, array $reportData): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $type = str_replace(' ', '_', strtolower($reportType));
        
        if (isset($reportData['period'])) {
            $period = str_replace(' ', '_', strtolower($reportData['period']));
            return "csc_{$type}_{$period}_{$timestamp}";
        }
        
        if (isset($reportData['year'])) {
            return "csc_{$type}_{$reportData['year']}_{$timestamp}";
        }

        return "csc_{$type}_{$timestamp}";
    }

    /**
     * Format harassment cases for report output
     */
    private function formatHarassmentCases(Collection $cases): array
    {
        return $cases->map(function ($case) {
            return [
                'case_number' => $case->case_number,
                'complainant_name' => $case->is_confidential ? '[CONFIDENTIAL]' : $case->complainant_name,
                'respondent_name' => $case->is_confidential ? '[CONFIDENTIAL]' : $case->respondent_name,
                'department' => $case->department_involved,
                'incident_date' => $case->incident_date->format('Y-m-d'),
                'filed_date' => $case->filed_date->format('Y-m-d'),
                'case_status' => $case->case_status_label,
                'investigation_status' => $case->investigation_status,
                'resolution_date' => $case->resolution_date?->format('Y-m-d'),
                'resolution_type' => $case->resolution_type_label,
                'days_open' => $case->days_open,
                'is_forwarded_to_court' => $case->forwarded_to_court ? 'Yes' : 'No',
                'appeal_filed' => $case->appeal_filed ? 'Yes' : 'No',
            ];
        })->toArray();
    }

    /**
     * Calculate average resolution days for harassment cases
     */
    private function calculateAverageResolutionDays(Collection $resolvedCases): float
    {
        if ($resolvedCases->isEmpty()) {
            return 0;
        }

        $totalDays = $resolvedCases->sum('days_open');
        return round($totalDays / $resolvedCases->count(), 1);
    }

    /**
     * Get harassment cases grouped by status
     */
    private function getHarassmentCasesByStatus(int $year, int $month, ?string $department = null): array
    {
        $query = SexualHarassmentCase::whereYear('filed_date', $year)
            ->whereMonth('filed_date', $month);

        if ($department) {
            $query->byDepartment($department);
        }

        return $query->selectRaw('case_status, COUNT(*) as count')
            ->groupBy('case_status')
            ->pluck('count', 'case_status')
            ->toArray();
    }

    /**
     * Get harassment cases grouped by department
     */
    private function getHarassmentCasesByDepartment(int $year, int $month): array
    {
        return SexualHarassmentCase::whereYear('filed_date', $year)
            ->whereMonth('filed_date', $month)
            ->selectRaw('department_involved, COUNT(*) as count')
            ->groupBy('department_involved')
            ->pluck('count', 'department_involved')
            ->toArray();
    }

    /**
     * Get harassment case resolution types
     */
    private function getHarassmentResolutionTypes(int $year, int $month, ?string $department = null): array
    {
        $query = SexualHarassmentCase::whereYear('resolution_date', $year)
            ->whereMonth('resolution_date', $month)
            ->whereNotNull('resolution_type');

        if ($department) {
            $query->byDepartment($department);
        }

        return $query->selectRaw('resolution_type, COUNT(*) as count')
            ->groupBy('resolution_type')
            ->pluck('count', 'resolution_type')
            ->toArray();
    }
}

/**
 * CSC Report Export Class for Excel
 */
class CSCReportExport implements \Maatwebsite\Excel\Concerns\FromArray, 
                                  \Maatwebsite\Excel\Concerns\WithHeadings,
                                  \Maatwebsite\Excel\Concerns\WithTitle
{
    private array $reportData;
    private string $reportType;

    public function __construct(array $reportData, string $reportType)
    {
        $this->reportData = $reportData;
        $this->reportType = $reportType;
    }

    public function array(): array
    {
        // Convert report data to array format suitable for Excel
        $exportData = [];
        
        if (isset($this->reportData['data'])) {
            foreach ($this->reportData['data'] as $section) {
                if (isset($section['employees'])) {
                    foreach ($section['employees'] as $employee) {
                        $exportData[] = $employee;
                    }
                }
            }
        }

        return $exportData;
    }

    public function headings(): array
    {
        // Return appropriate headings based on report type
        return [
            'Employee Number',
            'Full Name',
            'Position',
            'Department',
            'Salary Grade',
            'Date Hired',
            'Employment Status',
            'Action Type',
        ];
    }

    public function title(): string
    {
        return str_replace(' ', '_', $this->reportType);
    }
}