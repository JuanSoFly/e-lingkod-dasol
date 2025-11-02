<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'year',
        'vl_balance',
        'sl_balance',
        'remarks',
        'last_updated',
    ];

    protected $casts = [
        'vl_balance' => 'decimal:2',
        'sl_balance' => 'decimal:2',
        'last_updated' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LeaveCardEntry::class)->orderBy('date');
    }

    /**
     * Get or create leave card for employee and year
     */
    public static function getOrCreateCard(Employee $employee, int $year): self
    {
        return static::firstOrCreate([
            'employee_id' => $employee->id,
            'year' => $year,
        ], [
            'vl_balance' => 0,
            'sl_balance' => 0,
            'last_updated' => now(),
        ]);
    }

    /**
     * Add leave entry to card
     */
    public function addLeaveEntry(LeaveApplication $application): LeaveCardEntry
    {
        // Capture balances BEFORE update for audit trail
        $vlBalanceBefore = $this->vl_balance;
        $slBalanceBefore = $this->sl_balance;

        // Create entry first (without balance snapshots)
        $entry = $this->entries()->create([
            'leave_application_id' => $application->id,
            'leave_type_code' => $application->leaveType->code,
            'date' => $application->start_date,
            'days' => $application->days_requested,
            'remarks' => $this->generateRemarks($application),
            'entry_type' => 'deduction',
            'created_by' => auth()->id(),
        ]);

        // Update balances
        $this->updateBalances($application);

        // Update entry with balance snapshots AFTER update
        $entry->update([
            'vl_balance_after' => $this->vl_balance,
            'sl_balance_after' => $this->sl_balance,
        ]);

        return $entry;
    }

    /**
     * Update VL and SL balances based on leave type
     */
    private function updateBalances(LeaveApplication $application): void
    {
        $leaveType = $application->leaveType->code;
        $days = $application->days_requested;

        // Deduct from appropriate balance
        if ($leaveType === 'VL') {
            $this->vl_balance = max(0, $this->vl_balance - $days);
        } elseif ($leaveType === 'SL') {
            $this->sl_balance = max(0, $this->sl_balance - $days);
        }

        // Update remarks with new entry
        $currentRemarks = $this->remarks ? $this->remarks . "\n" : '';
        $newRemarks = $this->generateRemarks($application);
        $this->remarks = $currentRemarks . $newRemarks;

        $this->last_updated = now();
        $this->save();
    }

    /**
     * Generate remarks for leave entry
     */
    private function generateRemarks(LeaveApplication $application): string
    {
        $leaveType = $application->leaveType->code;
        $startDate = $application->start_date->format('M d');
        $endDate = $application->end_date->format('M d, Y');

        if ($application->start_date->eq($application->end_date)) {
            return "{$leaveType} – {$startDate}, {$endDate}";
        } else {
            return "{$leaveType} – {$startDate}–{$application->end_date->format('d')}, {$endDate}";
        }
    }

    /**
     * Get formatted remarks for all entries
     */
    public function getFormattedRemarks(): string
    {
        return $this->entries()
            ->orderBy('date')
            ->get()
            ->map(fn($entry) => $entry->remarks)
            ->implode("\n");
    }

    /**
     * Update leave card with current credit balances
     */
    public function updateCreditBalances(array $balances): void
    {
        foreach ($balances as $leaveTypeName => $balance) {
            if (stripos($leaveTypeName, 'Vacation') !== false) {
                $this->vl_balance = $balance;
            } elseif (stripos($leaveTypeName, 'Sick') !== false) {
                $this->sl_balance = $balance;
            }
        }
        $this->last_updated = now();
        $this->save();
    }
}