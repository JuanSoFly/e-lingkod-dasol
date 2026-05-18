<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class LeavePolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'employment_statuses',
        'positions',
        'employee_type',
        'leave_type_id',
        'max_days_per_year',
        'max_days_per_month',
        'max_consecutive_days',
        'min_days_per_application',
        'minimum_tenure_months',
        'requires_medical_certificate',
        'medical_cert_required_days',
        'accrual_method',
        'monthly_accrual_rate',
        'allow_prorated_first_year',
        'allow_negative_balance',
        'allow_carryover',
        'max_carryover_days',
        'carryover_expiry_date',
        'min_advance_notice_days',
        'max_advance_notice_days',
        'blocked_dates',
        'required_documents',
        'requires_approval',
        'approval_hierarchy',
        'auto_approve_threshold',
        'auto_approve_days',
        'is_government_policy',
        'legal_basis',
        'csc_reportable',
        'gender_restriction',
        'effective_start_date',
        'effective_end_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'employment_statuses' => 'array',
        'positions' => 'array',
        'max_days_per_year' => 'decimal:2',
        'max_days_per_month' => 'decimal:2',
        'max_consecutive_days' => 'decimal:2',
        'min_days_per_application' => 'decimal:2',
        'requires_medical_certificate' => 'boolean',
        'monthly_accrual_rate' => 'decimal:2',
        'allow_prorated_first_year' => 'boolean',
        'allow_negative_balance' => 'boolean',
        'allow_carryover' => 'boolean',
        'max_carryover_days' => 'decimal:2',
        'carryover_expiry_date' => 'date',
        'blocked_dates' => 'array',
        'required_documents' => 'array',
        'requires_approval' => 'boolean',
        'approval_hierarchy' => 'array',
        'auto_approve_threshold' => 'boolean',
        'auto_approve_days' => 'decimal:2',
        'is_government_policy' => 'boolean',
        'csc_reportable' => 'boolean',
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffective($query, $date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        
        return $query->where('effective_start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('effective_end_date')
                          ->orWhere('effective_end_date', '>=', $date);
                    });
    }

    public function scopeForEmploymentStatus($query, $employmentStatus)
    {
        $employmentStatus = Employee::normalizedEmploymentStatus($employmentStatus);

        return $query->where(function ($q) use ($employmentStatus) {
            $q->whereNull('employment_statuses')
              ->orWhereJsonContains('employment_statuses', $employmentStatus);
        });
    }

    public function scopeForPosition($query, $position)
    {
        return $query->where(function ($q) use ($position) {
            $q->whereNull('positions')
              ->orWhereJsonContains('positions', $position);
        });
    }

    public function scopeForGender($query, $gender)
    {
        return $query->where(function ($q) use ($gender) {
            $q->where('gender_restriction', 'none')
              ->orWhere('gender_restriction', strtolower($gender));
        });
    }

    /**
     * Helper Methods
     */

    /**
     * Check if this policy applies to a specific employee
     */
    public function appliesTo(Employee $employee): bool
    {
        // Check if policy is active and effective
        if (!$this->is_active || !$this->isEffective()) {
            return false;
        }

        // Check employment status
        if ($this->employment_statuses &&
            !in_array(Employee::normalizedEmploymentStatus($employee->employment_status), $this->employment_statuses, true)) {
            return false;
        }

        // Check position
        if ($this->positions && 
            !in_array($employee->position, $this->positions)) {
            return false;
        }

        // Check gender restriction
        if ($this->gender_restriction !== 'none' && 
            strtolower($employee->gender) !== $this->gender_restriction) {
            return false;
        }

        // Check minimum tenure
        if ($this->minimum_tenure_months > 0) {
            $tenureMonths = $employee->date_hired ? 
                Carbon::parse($employee->date_hired)->diffInMonths(now()) : 0;
            
            if ($tenureMonths < $this->minimum_tenure_months) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if policy is currently effective
     */
    public function isEffective($date = null): bool
    {
        $date = $date ? Carbon::parse($date) : now();
        
        if ($this->effective_start_date > $date) {
            return false;
        }

        if ($this->effective_end_date && $this->effective_end_date < $date) {
            return false;
        }

        return true;
    }

    /**
     * Calculate annual entitlement for an employee
     */
    public function calculateAnnualEntitlement(Employee $employee, int $year = null): float
    {
        $year = $year ?? now()->year;
        
        if (!$this->appliesTo($employee)) {
            return 0;
        }

        $baseEntitlement = $this->max_days_per_year;

        // Apply pro-rating for first year if enabled
        if ($this->allow_prorated_first_year && 
            $employee->date_hired && 
            Carbon::parse($employee->date_hired)->year == $year) {
            
            $hireDate = Carbon::parse($employee->date_hired);
            $yearEnd = Carbon::create($year, 12, 31);
            $monthsRemaining = $hireDate->diffInMonths($yearEnd) + 1;
            
            $baseEntitlement = ($baseEntitlement / 12) * $monthsRemaining;
        }

        return round($baseEntitlement, 2);
    }

    /**
     * Calculate monthly accrual for an employee
     */
    public function calculateMonthlyAccrual(Employee $employee): float
    {
        if (!$this->appliesTo($employee)) {
            return 0;
        }

        if ($this->accrual_method === 'monthly' && $this->monthly_accrual_rate) {
            return $this->monthly_accrual_rate;
        }

        // Default: annual entitlement divided by 12
        return round($this->max_days_per_year / 12, 2);
    }

    /**
     * Check if application requires medical certificate
     */
    public function requiresMedicalCertificate(float $days): bool
    {
        if (!$this->requires_medical_certificate) {
            return false;
        }

        if ($this->medical_cert_required_days === null) {
            return true;
        }

        return $days >= $this->medical_cert_required_days;
    }

    /**
     * Check if date is in blocked period
     */
    public function isDateBlocked(Carbon $date): bool
    {
        if (!$this->blocked_dates) {
            return false;
        }

        foreach ($this->blocked_dates as $blockedPeriod) {
            $start = Carbon::parse($blockedPeriod['start']);
            $end = Carbon::parse($blockedPeriod['end']);
            
            if ($date->between($start, $end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if application can be auto-approved
     */
    public function canAutoApprove(float $days): bool
    {
        if (!$this->auto_approve_threshold) {
            return false;
        }

        return $this->auto_approve_days && $days <= $this->auto_approve_days;
    }

    /**
     * Get approval hierarchy for this policy
     */
    public function getApprovalHierarchy(): array
    {
        return $this->approval_hierarchy ?? ['immediate_supervisor', 'hr'];
    }

    /**
     * Validate leave application against this policy
     */
    public function validateApplication(Employee $employee, array $applicationData): array
    {
        $errors = [];

        // Check if policy applies
        if (!$this->appliesTo($employee)) {
            $errors[] = 'This leave type is not available for your employment status or position.';
            return $errors;
        }

        $days = $applicationData['days_requested'] ?? 0;
        $startDate = Carbon::parse($applicationData['start_date']);
        $endDate = Carbon::parse($applicationData['end_date']);

        // Check minimum days
        if ($days < $this->min_days_per_application) {
            $errors[] = "Minimum {$this->min_days_per_application} day(s) required for this leave type.";
        }

        // Check maximum consecutive days
        if ($this->max_consecutive_days && $days > $this->max_consecutive_days) {
            $errors[] = "Maximum {$this->max_consecutive_days} consecutive day(s) allowed.";
        }

        // Check advance notice
        $noticeGiven = now()->diffInDays($startDate);
        if ($noticeGiven < $this->min_advance_notice_days) {
            $errors[] = "Minimum {$this->min_advance_notice_days} day(s) advance notice required.";
        }

        if ($this->max_advance_notice_days && $noticeGiven > $this->max_advance_notice_days) {
            $errors[] = "Maximum {$this->max_advance_notice_days} day(s) advance notice allowed.";
        }

        // Check blocked dates
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($this->isDateBlocked($current)) {
                $errors[] = "Leave cannot be taken during blocked period ({$current->format('M d, Y')}).";
                break;
            }
            $current->addDay();
        }

        // Check medical certificate requirement
        if ($this->requiresMedicalCertificate($days) && 
            empty($applicationData['medical_certificate'])) {
            $errors[] = "Medical certificate required for {$days} day(s) of leave.";
        }

        return $errors;
    }

    /**
     * Get policy summary for display
     */
    public function getSummary(): array
    {
        return [
            'name' => $this->name,
            'leave_type' => $this->leaveType->name,
            'max_days_per_year' => $this->max_days_per_year,
            'employment_statuses' => $this->employment_statuses,
            'requires_approval' => $this->requires_approval,
            'min_advance_notice' => $this->min_advance_notice_days,
            'effective_period' => [
                'start' => $this->effective_start_date->format('M d, Y'),
                'end' => $this->effective_end_date?->format('M d, Y') ?? 'Ongoing'
            ]
        ];
    }
}
