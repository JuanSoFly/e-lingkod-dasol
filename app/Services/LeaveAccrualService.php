<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveAccrualLog;
use App\Models\LeaveCard;
use App\Models\LeaveCredit;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveAccrualService
{
    /**
     * Run monthly accrual for all eligible employees.
     *
     * @return int number of accrual entries created
     */
    public function accrueForMonth(?int $year = null, ?int $month = null): int
    {
        $target = $this->resolveTargetMonth($year, $month);
        $year = (int) $target->year;
        $month = (int) $target->month;
        $created = 0;

        // Exclude employment categories that do not earn CSC leave by default
        $excludedStatuses = ['job_order', 'jo', 'cos', 'contract_of_service'];

        Employee::whereNotNull('date_hired')
            ->whereNotIn('employment_status', $excludedStatuses)
            ->chunk(100, function ($employees) use ($year, $month, &$created) {
                foreach ($employees as $employee) {
                    $policies = app(LeavePolicyService::class)
                        ->getApplicablePolicies($employee)
                        ->where('accrual_method', 'monthly');

                    foreach ($policies as $policy) {
                        $rate = $policy->monthly_accrual_rate ?? 0;
                        if ($rate <= 0) {
                            continue;
                        }

                        // Guard against duplicate accruals for the same month/type
                        $alreadyAccrued = LeaveAccrualLog::where('employee_id', $employee->id)
                            ->where('leave_type_id', $policy->leave_type_id)
                            ->where('year', $year)
                            ->where('month', $month)
                            ->exists();

                        if ($alreadyAccrued) {
                            continue;
                        }

                        // TODO: integrate LWOP/attendance deduction; credits should be reduced if not in "actual service" (LWOP).
                        // Reference: docs/leave-credits/lwop.md
                        $increment = $rate;

                        DB::transaction(function () use ($employee, $policy, $year, $month, $increment, &$created) {
                            $leaveCredit = LeaveCredit::firstOrCreate(
                                [
                                    'employee_id' => $employee->id,
                                    'leave_type_id' => $policy->leave_type_id,
                                    'year' => $year,
                                ],
                                [
                                    'earned_credits' => 0,
                                    'used_credits' => 0,
                                    'remaining_credits' => 0,
                                    'effective_date' => Carbon::create($year, 1, 1),
                                ]
                            );

                            $leaveCredit->earned_credits += $increment;
                            $leaveCredit->remaining_credits = max(
                                0,
                                $leaveCredit->earned_credits - $leaveCredit->used_credits
                            );
                            $leaveCredit->save();

                            // Keep leave cards in sync for VL/SL
                            $leaveTypeCode = $policy->leaveType->code ?? null;
                            if (in_array($leaveTypeCode, ['VL', 'SL'])) {
                                $leaveCard = LeaveCard::getOrCreateCard($employee, $year);
                                if ($leaveTypeCode === 'VL') {
                                    $leaveCard->vl_balance += $increment;
                                } elseif ($leaveTypeCode === 'SL') {
                                    $leaveCard->sl_balance += $increment;
                                }
                                $leaveCard->last_updated = now();
                                $leaveCard->save();
                            }

                            LeaveAccrualLog::create([
                                'employee_id' => $employee->id,
                                'leave_type_id' => $policy->leave_type_id,
                                'year' => $year,
                                'month' => $month,
                                'days_accrued' => $increment,
                            ]);

                            $created++;
                        });
                    }
                }
            });

        Log::info('Monthly leave accrual completed', [
            'year' => $year,
            'month' => $month,
            'entries_created' => $created,
        ]);

        return $created;
    }

    /**
     * Recompute leave credits for a given year based on accrual logs.
     *
     * @return int number of leave credits updated
     */
    public function reconcileYear(int $year): int
    {
        $updated = 0;

        $logs = LeaveAccrualLog::where('year', $year)
            ->select('employee_id', 'leave_type_id', DB::raw('SUM(days_accrued) as total'))
            ->groupBy('employee_id', 'leave_type_id')
            ->get();

        foreach ($logs as $log) {
            $leaveCredit = LeaveCredit::firstOrCreate(
                [
                    'employee_id' => $log->employee_id,
                    'leave_type_id' => $log->leave_type_id,
                    'year' => $year,
                ],
                [
                    'earned_credits' => 0,
                    'used_credits' => 0,
                    'remaining_credits' => 0,
                    'effective_date' => Carbon::create($year, 1, 1),
                ]
            );

            $leaveCredit->earned_credits = $log->total;
            $leaveCredit->remaining_credits = max(0, $leaveCredit->earned_credits - $leaveCredit->used_credits);
            $leaveCredit->save();
            $updated++;

            // Sync leave card balances for VL/SL
            $leaveTypeCode = $leaveCredit->leaveType->code ?? null;
            if (in_array($leaveTypeCode, ['VL', 'SL'])) {
                $leaveCard = LeaveCard::getOrCreateCard($leaveCredit->employee, $year);
                if ($leaveTypeCode === 'VL') {
                    $leaveCard->vl_balance = $leaveCredit->remaining_credits;
                } elseif ($leaveTypeCode === 'SL') {
                    $leaveCard->sl_balance = $leaveCredit->remaining_credits;
                }
                $leaveCard->last_updated = now();
                $leaveCard->save();
            }
        }

        return $updated;
    }

    private function resolveTargetMonth(?int $year, ?int $month): Carbon
    {
        if ($year && $month) {
            return Carbon::create($year, $month, 1)->startOfMonth();
        }

        // Default: accrue for the most recently completed month
        return now()->subMonthNoOverflow()->startOfMonth();
    }
}
