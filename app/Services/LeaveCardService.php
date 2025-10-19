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
     */
    public function processApprovedLeave(LeaveApplication $application): LeaveCard
    {
        try {
            DB::beginTransaction();

            $employee = $application->employee;
            $year = $application->start_date->year;

            // Get or create leave card for the year
            $leaveCard = LeaveCard::getOrCreateCard($employee, $year);

            // Add entry to leave card
            $leaveCard->addLeaveEntry($application);

            // Update remarks with all entries
            $leaveCard->remarks = $leaveCard->getFormattedRemarks();
            $leaveCard->save();

            Log::info('Leave card updated', [
                'application_id' => $application->id,
                'employee_id' => $employee->id,
                'leave_card_id' => $leaveCard->id,
            ]);

            DB::commit();

            return $leaveCard;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process leave card update', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Initialize yearly leave balances for employee
     */
    public function initializeYearlyBalances(Employee $employee, int $year): LeaveCard
    {
        $leaveCard = LeaveCard::getOrCreateCard($employee, $year);

        // Get current leave credits using credits_earned and effective_date
        $vlCredits = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', function($query) {
                $query->select('id')->from('leave_types')->where('code', 'VL');
            })
            ->whereYear('effective_date', $year)
            ->sum('credits_earned');

        $slCredits = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', function($query) {
                $query->select('id')->from('leave_types')->where('code', 'SL');
            })
            ->whereYear('effective_date', $year)
            ->sum('credits_earned');

        // Update balances
        $leaveCard->vl_balance = $vlCredits;
        $leaveCard->sl_balance = $slCredits;
        $leaveCard->last_updated = now();
        $leaveCard->save();

        return $leaveCard;
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

        return [
            'vl_balance' => $leaveCard->vl_balance,
            'sl_balance' => $leaveCard->sl_balance,
            'last_updated' => $leaveCard->last_updated,
        ];
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