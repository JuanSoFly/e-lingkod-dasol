<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveCard;
use App\Models\LeaveCardEntry;
use App\Models\LeaveCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveCardService
{
    /**
     * Process approved leave application and update leave card
     * Note: This method should be called within a DB transaction from the controller
     */
    public function processApprovedLeave(LeaveApplication $application): LeaveCard
    {
        $employee = $application->employee;
        $year = $application->start_date->year;

        // Get or create leave card for the year
        $leaveCard = LeaveCard::getOrCreateCard($employee, $year);

        // If the card was recently created, initialize its balances from LeaveCredit
        if ($leaveCard->wasRecentlyCreated) {
            $this->initializeYearlyBalances($employee, $year);
            $leaveCard = $leaveCard->fresh();
        }

        // Capture pre-update balances for logging
        $vlBefore = $leaveCard->vl_balance;
        $slBefore = $leaveCard->sl_balance;

        // Add entry to leave card (this updates LeaveCard balances and captures snapshots)
        $leaveCard->addLeaveEntry($application);

        // Synchronize LeaveCredit records for consistency within same transaction
        $this->syncLeaveCredits($application, $employee, $year);

        // Update remarks with all entries
        $leaveCard->remarks = $leaveCard->getFormattedRemarks();
        $leaveCard->save();

        Log::info('Leave card updated', [
            'application_id' => $application->id,
            'employee_id' => $employee->id,
            'leave_card_id' => $leaveCard->id,
            'balances_before' => ['vl' => $vlBefore, 'sl' => $slBefore],
            'balances_after' => ['vl' => $leaveCard->vl_balance, 'sl' => $leaveCard->sl_balance],
        ]);

        return $leaveCard;
    }

    /**
     * Synchronize LeaveCredit records with LeaveCard balances
     * Ensures both systems stay in sync within the same transaction
     */
    private function syncLeaveCredits(LeaveApplication $application, Employee $employee, int $year): void
    {
        $leaveType = $application->leaveType;
        $daysUsed = $application->days_requested;

        // Find or create LeaveCredit record (should be locked by controller)
        $leaveCredit = \App\Models\LeaveCredit::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'earned_credits' => 0,
                'used_credits' => 0,
                'remaining_credits' => 0,
                'effective_date' => now(),
                // Don't set created_by here as auth might not be available in all contexts
            ]
        );

        // For VL and SL, ensure LeaveCredit matches LeaveCard balance
        if ($leaveType->code === 'VL' || $leaveType->code === 'SL') {
            $leaveCard = \App\Models\LeaveCard::where('employee_id', $employee->id)
                ->where('year', $year)
                ->first();

            if ($leaveCard) {
                if ($leaveType->code === 'VL') {
                    $leaveCredit->remaining_credits = max(0, $leaveCard->vl_balance);
                    $leaveCredit->used_credits = max(0, ($leaveCredit->earned_credits ?? 0) - $leaveCard->vl_balance);
                } elseif ($leaveType->code === 'SL') {
                    $leaveCredit->remaining_credits = max(0, $leaveCard->sl_balance);
                    $leaveCredit->used_credits = max(0, ($leaveCredit->earned_credits ?? 0) - $leaveCard->sl_balance);
                }
            }
        } else {
            // For other leave types, use the standard calculation
            $leaveCredit->used_credits += $daysUsed;
            $leaveCredit->remaining_credits = max(0, $leaveCredit->earned_credits - $leaveCredit->used_credits);
        }

        // Set audit fields if user is authenticated
        if (auth()->check()) {
            $leaveCredit->updated_by = auth()->id();
        }
        $leaveCredit->save();

        Log::info('LeaveCredit synchronized', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_type_code' => $leaveType->code,
            'year' => $year,
            'days_used' => $daysUsed,
            'used_credits' => $leaveCredit->used_credits,
            'remaining_credits' => $leaveCredit->remaining_credits,
        ]);
    }

    /**
     * Initialize yearly leave balances for employee
     */
    public function initializeYearlyBalances(Employee $employee, int $year): LeaveCard
    {
        $leaveCard = LeaveCard::getOrCreateCard($employee, $year);

        // Ensure leave credits exist for VL and SL
        $this->ensureInitialCreditsExist($employee, $year);

        // Get current leave credits using remaining_credits to reflect usage
        $vlCredits = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', function($query) {
                $query->select('id')->from('leave_types')->where('code', 'VL');
            })
            ->where('year', $year)
            ->sum('remaining_credits');

        $slCredits = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', function($query) {
                $query->select('id')->from('leave_types')->where('code', 'SL');
            })
            ->where('year', $year)
            ->sum('remaining_credits');

        // Update balances
        $leaveCard->vl_balance = $vlCredits;
        $leaveCard->sl_balance = $slCredits;
        $leaveCard->last_updated = now();
        $leaveCard->save();

        return $leaveCard;
    }

    /**
     * Ensure initial leave credits exist for an employee
     * Creates VL and SL credits if they don't exist
     */
    public function ensureInitialCreditsExist(Employee $employee, int $year): void
    {
        // Get VL and SL leave types
        $vlType = \App\Models\LeaveType::where('code', 'VL')->first();
        $slType = \App\Models\LeaveType::where('code', 'SL')->first();

        if (!$vlType || !$slType) {
            Log::warning('VL or SL leave types not found', [
                'employee_id' => $employee->id,
                'year' => $year
            ]);
            return;
        }

        $policyService = app(LeavePolicyService::class);
        $policies = $policyService->getApplicablePolicies($employee);
        
        $vlPolicy = $policies->where('leave_type_id', $vlType->id)->first();
        $slPolicy = $policies->where('leave_type_id', $slType->id)->first();

        $today = now();
        $targetYear = $year;
        
        if ($targetYear < $today->year) {
            $endMonth = 12;
        } elseif ($targetYear === $today->year) {
            $endMonth = $today->month - 1; // months completed so far
        } else {
            $endMonth = 0;
        }

        $startMonth = 1;
        if ($employee->date_hired && $employee->date_hired->year === $targetYear) {
            $startMonth = $employee->date_hired->month;
        }

        // Create VL credit if doesn't exist
        $vlCredit = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', $vlType->id)
            ->where('year', $year)
            ->first();

        if (!$vlCredit) {
            $vlRate = ($vlPolicy && $vlPolicy->accrual_method === 'monthly') ? ($vlPolicy->monthly_accrual_rate ?? 1.25) : 1.25;
            $vlEarned = 0;
            
            DB::transaction(function () use ($employee, $vlType, $year, $startMonth, $endMonth, $vlRate, &$vlEarned) {
                // Loop through elapsed months to log them and calculate credits
                for ($m = $startMonth; $m <= $endMonth; $m++) {
                    $vlEarned += $vlRate;
                    
                    // Create log
                    \App\Models\LeaveAccrualLog::firstOrCreate([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $vlType->id,
                        'year' => $year,
                        'month' => $m,
                    ], [
                        'days_accrued' => $vlRate,
                    ]);
                }
                
                LeaveCredit::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $vlType->id,
                    'year' => $year,
                    'earned_credits' => $vlEarned,
                    'used_credits' => 0,
                    'remaining_credits' => $vlEarned,
                    'effective_date' => \Carbon\Carbon::create($year, 1, 1),
                ]);
            });

            Log::info('Created initial VL credit for employee with auto-backfill', [
                'employee_id' => $employee->id,
                'year' => $year,
                'credits' => $vlEarned
            ]);
        }

        // Create SL credit if doesn't exist
        $slCredit = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', $slType->id)
            ->where('year', $year)
            ->first();

        if (!$slCredit) {
            $slRate = ($slPolicy && $slPolicy->accrual_method === 'monthly') ? ($slPolicy->monthly_accrual_rate ?? 1.25) : 1.25;
            $slEarned = 0;
            
            DB::transaction(function () use ($employee, $slType, $year, $startMonth, $endMonth, $slRate, &$slEarned) {
                // Loop through elapsed months to log them and calculate credits
                for ($m = $startMonth; $m <= $endMonth; $m++) {
                    $slEarned += $slRate;
                    
                    // Create log
                    \App\Models\LeaveAccrualLog::firstOrCreate([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $slType->id,
                        'year' => $year,
                        'month' => $m,
                    ], [
                        'days_accrued' => $slRate,
                    ]);
                }
                
                LeaveCredit::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $slType->id,
                    'year' => $year,
                    'earned_credits' => $slEarned,
                    'used_credits' => 0,
                    'remaining_credits' => $slEarned,
                    'effective_date' => \Carbon\Carbon::create($year, 1, 1),
                ]);
            });

            Log::info('Created initial SL credit for employee with auto-backfill', [
                'employee_id' => $employee->id,
                'year' => $year,
                'credits' => $slEarned
            ]);
        }
    }

    /**
     * Get employee's current leave balances
     */
    public function getCurrentBalances(Employee $employee): array
    {
        $year = now()->year;
        $leaveCard = LeaveCard::where('employee_id', $employee->id)
            ->where('year', $year)
            ->first();

        if (!$leaveCard) {
            $leaveCard = $this->initializeYearlyBalances($employee, $year);
        }

        // Get all leave types with their current balances
        $leaveTypes = \App\Models\LeaveType::where('is_active', true)->get();
        $balances = [];

        foreach ($leaveTypes as $type) {
            $balances[$type->code] = $this->getLeaveTypeBalance($employee, $type, $year);
        }

        return array_merge([
            'vl_balance' => $leaveCard->vl_balance,
            'sl_balance' => $leaveCard->sl_balance,
            'last_updated' => $leaveCard->last_updated,
        ], $balances);
    }

    /**
     * Get balance for a specific leave type using LeaveCredit data
     */
    private function getLeaveTypeBalance(Employee $employee, \App\Models\LeaveType $leaveType, int $year): float
    {
        // For VL and SL, use LeaveCard data (existing functionality)
        if ($leaveType->code === 'VL') {
            $leaveCard = LeaveCard::where('employee_id', $employee->id)
                ->where('year', $year)
                ->first();
            return $leaveCard ? $leaveCard->vl_balance : 0;
        }

        if ($leaveType->code === 'SL') {
            $leaveCard = LeaveCard::where('employee_id', $employee->id)
                ->where('year', $year)
                ->first();
            return $leaveCard ? $leaveCard->sl_balance : 0;
        }

        // For other leave types, use LeaveCredit data
        $leaveCredit = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if ($leaveCredit) {
            return $leaveCredit->remaining_credits;
        }

        // If no LeaveCredit record exists, return the max days from LeaveType
        // This ensures all leave types show a meaningful value
        return $leaveType->max_days_per_year ?? 0;
    }

    /**
     * Get employee's leave history
     */
    public function getLeaveHistory(Employee $employee, int $year = null): array
    {
        $year = $year ?? now()->year;
        $leaveCard = LeaveCard::where('employee_id', $employee->id)
            ->where('year', $year)
            ->with(['entries.leaveApplication.leaveType'])
            ->first();

        if (!$leaveCard) {
            return [
                'entries' => [],
                'current_balances' => [
                    'vl_balance' => 0,
                    'sl_balance' => 0,
                ],
            ];
        }

        return [
            'entries' => $leaveCard->entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'leave_type' => $entry->leave_type_code,
                    'date' => $entry->date->format('Y-m-d'),
                    'days' => $entry->days,
                    'remarks' => $entry->remarks,
                    'application_id' => $entry->leave_application_id,
                ];
            }),
            'current_balances' => [
                'vl_balance' => $leaveCard->vl_balance,
                'sl_balance' => $leaveCard->sl_balance,
                'last_updated' => $leaveCard->last_updated->format('Y-m-d'),
            ],
        ];
    }

    
    /**
     * Create leave card entry for manual adjustments
     */
    public function createManualEntry(Employee $employee, array $data): LeaveCardEntry
    {
        try {
            DB::beginTransaction();

            $year = $data['date'] instanceof \DateTime
                ? $data['date']->format('Y')
                : date('Y', strtotime($data['date']));

            $leaveCard = LeaveCard::getOrCreateCard($employee, (int)$year);

            $entry = $leaveCard->entries()->create([
                'leave_application_id' => null,
                'leave_type_code' => $data['leave_type_code'],
                'date' => $data['date'],
                'days' => $data['days'],
                'remarks' => $data['remarks'],
                'entry_type' => $data['entry_type'] ?? 'adjustment',
                'created_by' => auth()->id(),
            ]);

            // Update remarks
            $leaveCard->remarks = $leaveCard->getFormattedRemarks();
            $leaveCard->last_updated = now();
            $leaveCard->save();

            DB::commit();

            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create manual leave card entry', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all leave cards for HR/Admin view
     */
    public function getAllLeaveCards(int $year = null, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $year = $year ?? now()->year;

        $query = LeaveCard::with(['employee', 'entries'])
            ->where('year', $year);

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['department'])) {
            $query->whereHas('employee', function($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
        }

        return $query->get();
    }
}
