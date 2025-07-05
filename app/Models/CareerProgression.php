<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CareerProgression extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'career_progression';

    protected $fillable = [
        'employee_id',
        'from_position',
        'to_position',
        'from_department',
        'to_department',
        'promotion_date',
        'promotion_type',
        'salary_grade_from',
        'salary_grade_to',
        'step_increment_from',
        'step_increment_to',
        'monthly_salary_from',
        'monthly_salary_to',
        'appointing_authority',
        'order_number',
        'order_series',
        'order_date',
        'effective_date',
        'status',
        'from_employment_status',
        'to_employment_status',
        'nature_of_appointment',
        'justification',
        'remarks',
        'is_temporary',
        'temporary_until',
        'appointment_document_path',
        'is_csc_approved',
        'csc_approval_date',
        'csc_approval_number',
        'created_by',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'promotion_date' => 'date',
        'order_date' => 'date',
        'effective_date' => 'date',
        'temporary_until' => 'date',
        'csc_approval_date' => 'date',
        'approved_at' => 'datetime',
        'is_temporary' => 'boolean',
        'is_csc_approved' => 'boolean',
        'monthly_salary_from' => 'decimal:2',
        'monthly_salary_to' => 'decimal:2'
    ];

    /**
     * Get the employee that owns this career progression.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this record.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the "from" salary grade information.
     */
    public function fromSalaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'salary_grade_from', 'grade_level')
                    ->where('step_increment', $this->step_increment_from ?? 1)
                    ->where('status', 'Active');
    }

    /**
     * Get the "to" salary grade information.
     */
    public function toSalaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'salary_grade_to', 'grade_level')
                    ->where('step_increment', $this->step_increment_to ?? 1)
                    ->where('status', 'Active');
    }

    /**
     * Scope a query to only include active progressions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope a query to only include completed progressions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    /**
     * Scope a query to only include promotions (not lateral transfers).
     */
    public function scopePromotions($query)
    {
        return $query->where('promotion_type', 'like', '%Promotion%')
                    ->orWhere(function($q) {
                        $q->whereColumn('salary_grade_to', '>', 'salary_grade_from');
                    });
    }

    /**
     * Scope a query to only include lateral transfers.
     */
    public function scopeLateralTransfers($query)
    {
        return $query->where('promotion_type', 'Lateral Transfer')
                    ->orWhere(function($q) {
                        $q->whereColumn('salary_grade_to', '=', 'salary_grade_from')
                          ->where('from_department', '!=', 'to_department');
                    });
    }

    /**
     * Scope a query to only include CSC approved progressions.
     */
    public function scopeCscApproved($query)
    {
        return $query->where('is_csc_approved', true);
    }

    /**
     * Check if this is a promotion (salary grade increase).
     */
    public function isPromotion(): bool
    {
        if ($this->salary_grade_from && $this->salary_grade_to) {
            return $this->salary_grade_to > $this->salary_grade_from;
        }

        if ($this->monthly_salary_from && $this->monthly_salary_to) {
            return $this->monthly_salary_to > $this->monthly_salary_from;
        }

        return in_array($this->promotion_type, ['Regular Promotion', 'Merit Promotion']);
    }

    /**
     * Check if this is a lateral transfer.
     */
    public function isLateralTransfer(): bool
    {
        return $this->promotion_type === 'Lateral Transfer' ||
               ($this->salary_grade_from === $this->salary_grade_to && 
                $this->from_department !== $this->to_department);
    }

    /**
     * Check if this is a temporary assignment.
     */
    public function isTemporary(): bool
    {
        return $this->is_temporary || 
               in_array($this->promotion_type, ['Acting Capacity', 'Officer-in-Charge', 'Temporary Assignment']);
    }

    /**
     * Get the salary increase amount.
     */
    public function getSalaryIncrease(): ?float
    {
        if ($this->monthly_salary_from && $this->monthly_salary_to) {
            return $this->monthly_salary_to - $this->monthly_salary_from;
        }

        return null;
    }

    /**
     * Get the salary increase percentage.
     */
    public function getSalaryIncreasePercentage(): ?float
    {
        if ($this->monthly_salary_from && $this->monthly_salary_to && $this->monthly_salary_from > 0) {
            return (($this->monthly_salary_to - $this->monthly_salary_from) / $this->monthly_salary_from) * 100;
        }

        return null;
    }

    /**
     * Get formatted salary increase.
     */
    public function getFormattedSalaryIncreaseAttribute(): ?string
    {
        $increase = $this->getSalaryIncrease();
        if ($increase === null) {
            return null;
        }

        $percentage = $this->getSalaryIncreasePercentage();
        $percentageStr = $percentage ? ' (' . number_format($percentage, 2) . '%)' : '';

        return '₱' . number_format($increase, 2) . $percentageStr;
    }

    /**
     * Get the grade level increase.
     */
    public function getGradeLevelIncrease(): ?int
    {
        if ($this->salary_grade_from && $this->salary_grade_to) {
            return $this->salary_grade_to - $this->salary_grade_from;
        }

        return null;
    }

    /**
     * Check if the effective date has passed.
     */
    public function isEffective(): bool
    {
        return $this->effective_date && $this->effective_date->isPast();
    }

    /**
     * Check if temporary assignment has expired.
     */
    public function isTemporaryExpired(): bool
    {
        return $this->is_temporary && 
               $this->temporary_until && 
               $this->temporary_until->isPast();
    }

    /**
     * Get formatted order reference.
     */
    public function getFormattedOrderReferenceAttribute(): ?string
    {
        if ($this->order_number && $this->order_series) {
            return $this->order_number . ', s. ' . $this->order_series;
        }

        return $this->order_number;
    }

    /**
     * Boot method for model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-update employee's current position and salary when progression is effective
        static::saved(function ($progression) {
            if ($progression->isEffective() && $progression->status === 'Active') {
                $progression->employee->update([
                    'position' => $progression->to_position,
                    'department' => $progression->to_department,
                    'salary_grade' => $progression->salary_grade_to,
                    'step_increment' => $progression->step_increment_to,
                    'employment_status' => $progression->to_employment_status,
                    'last_promotion_date' => $progression->effective_date,
                    'previous_position' => $progression->from_position
                ]);
            }
        });
    }

    /**
     * The promotion types available.
     */
    public static function getPromotionTypes(): array
    {
        return [
            'Regular Promotion' => 'Regular Promotion',
            'Merit Promotion' => 'Merit Promotion',
            'Lateral Transfer' => 'Lateral Transfer',
            'Reassignment' => 'Reassignment',
            'Detail' => 'Detail',
            'Secondment' => 'Secondment',
            'Acting Capacity' => 'Acting Capacity',
            'Officer-in-Charge' => 'Officer-in-Charge',
            'Temporary Assignment' => 'Temporary Assignment',
            'Demotion' => 'Demotion',
            'Other' => 'Other'
        ];
    }

    /**
     * The employment statuses available.
     */
    public static function getEmploymentStatuses(): array
    {
        return [
            'Regular' => 'Regular',
            'Contractual' => 'Contractual',
            'Casual' => 'Casual',
            'Co-terminus' => 'Co-terminus',
            'Job Order' => 'Job Order',
            'Contract of Service' => 'Contract of Service',
            'Temporary' => 'Temporary',
            'Probationary' => 'Probationary',
            'Other' => 'Other'
        ];
    }

    /**
     * The nature of appointments available.
     */
    public static function getNatureOfAppointments(): array
    {
        return [
            'Original' => 'Original',
            'Promotion' => 'Promotion',
            'Transfer' => 'Transfer',
            'Reappointment' => 'Reappointment',
            'Reinstatement' => 'Reinstatement',
            'Reemployment' => 'Reemployment',
            'Detail' => 'Detail',
            'Secondment' => 'Secondment',
            'Other' => 'Other'
        ];
    }

    /**
     * The status options available.
     */
    public static function getStatusOptions(): array
    {
        return [
            'Active' => 'Active',
            'Completed' => 'Completed',
            'Cancelled' => 'Cancelled',
            'Pending' => 'Pending',
            'Superseded' => 'Superseded'
        ];
    }
}