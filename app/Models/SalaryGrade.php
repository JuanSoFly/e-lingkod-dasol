<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class SalaryGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_level',
        'step_increment',
        'monthly_salary',
        'daily_rate',
        'hourly_rate',
        'pera_allowance',
        'productivity_allowance',
        'hazard_allowance',
        'subsistence_allowance',
        'laundry_allowance',
        'overtime_rate_regular',
        'overtime_rate_special',
        'overtime_rate_legal',
        'night_differential_rate',
        'position_level',
        'ssl_tranche',
        'effective_date',
        'end_date',
        'status',
        'dbu_number',
        'legal_basis',
        'remarks',
        'annual_adjustment_percentage',
        'adjustment_year'
    ];

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'pera_allowance' => 'decimal:2',
        'productivity_allowance' => 'decimal:2',
        'hazard_allowance' => 'decimal:2',
        'subsistence_allowance' => 'decimal:2',
        'laundry_allowance' => 'decimal:2',
        'overtime_rate_regular' => 'decimal:2',
        'overtime_rate_special' => 'decimal:2',
        'overtime_rate_legal' => 'decimal:2',
        'night_differential_rate' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
        'annual_adjustment_percentage' => 'decimal:2'
    ];

    /**
     * Get the employees with this salary grade.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'salary_grade', 'grade_level')
                    ->where('step_increment', $this->step_increment);
    }

    /**
     * Get career progressions from this salary grade.
     */
    public function careerProgressionsFrom(): HasMany
    {
        return $this->hasMany(CareerProgression::class, 'salary_grade_from', 'grade_level')
                    ->where('step_increment_from', $this->step_increment);
    }

    /**
     * Get career progressions to this salary grade.
     */
    public function careerProgressionsTo(): HasMany
    {
        return $this->hasMany(CareerProgression::class, 'salary_grade_to', 'grade_level')
                    ->where('step_increment_to', $this->step_increment);
    }

    /**
     * Scope a query to only include active salary grades.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active')
                    ->where('effective_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>', now());
                    });
    }

    /**
     * Scope a query to only include current salary grades.
     */
    public function scopeCurrent($query)
    {
        return $query->where('effective_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>', now());
                    })
                    ->where('status', 'Active');
    }

    /**
     * Scope a query to only include future salary grades.
     */
    public function scopeFuture($query)
    {
        return $query->where('effective_date', '>', now())
                    ->where('status', 'Future');
    }

    /**
     * Scope a query to filter by grade level.
     */
    public function scopeForGrade($query, int $gradeLevel)
    {
        return $query->where('grade_level', $gradeLevel);
    }

    /**
     * Scope a query to filter by step increment.
     */
    public function scopeForStep($query, int $stepIncrement)
    {
        return $query->where('step_increment', $stepIncrement);
    }

    /**
     * Scope a query to filter by position level.
     */
    public function scopeForPositionLevel($query, string $positionLevel)
    {
        return $query->where('position_level', $positionLevel);
    }

    /**
     * Get the total monthly compensation including allowances.
     */
    public function getTotalMonthlyCompensation(): float
    {
        return $this->monthly_salary + 
               $this->pera_allowance + 
               $this->productivity_allowance + 
               $this->hazard_allowance + 
               $this->subsistence_allowance + 
               $this->laundry_allowance;
    }

    /**
     * Get formatted total monthly compensation.
     */
    public function getFormattedTotalCompensationAttribute(): string
    {
        return '₱' . number_format($this->getTotalMonthlyCompensation(), 2);
    }

    /**
     * Get formatted monthly salary.
     */
    public function getFormattedMonthlySalaryAttribute(): string
    {
        return '₱' . number_format($this->monthly_salary, 2);
    }

    /**
     * Get formatted daily rate.
     */
    public function getFormattedDailyRateAttribute(): string
    {
        return '₱' . number_format($this->daily_rate, 2);
    }

    /**
     * Get formatted hourly rate.
     */
    public function getFormattedHourlyRateAttribute(): string
    {
        return '₱' . number_format($this->hourly_rate, 2);
    }

    /**
     * Calculate overtime pay for given hours.
     */
    public function calculateOvertimePay(float $hours, string $type = 'regular'): float
    {
        $rate = match($type) {
            'special' => $this->overtime_rate_special,
            'legal' => $this->overtime_rate_legal,
            default => $this->overtime_rate_regular
        };

        return $hours * $rate;
    }

    /**
     * Calculate night differential pay for given hours.
     */
    public function calculateNightDifferentialPay(float $hours): float
    {
        return $hours * $this->night_differential_rate;
    }

    /**
     * Check if this salary grade is currently effective.
     */
    public function isEffective(): bool
    {
        $now = now();
        
        return $this->effective_date <= $now &&
               ($this->end_date === null || $this->end_date > $now) &&
               $this->status === 'Active';
    }

    /**
     * Check if this salary grade is superseded.
     */
    public function isSuperseded(): bool
    {
        return $this->status === 'Superseded' || 
               ($this->end_date && $this->end_date->isPast());
    }

    /**
     * Get the next salary grade (same grade, next step).
     */
    public function getNextStep(): ?self
    {
        return static::where('grade_level', $this->grade_level)
                    ->where('step_increment', $this->step_increment + 1)
                    ->current()
                    ->first();
    }

    /**
     * Get the previous salary grade (same grade, previous step).
     */
    public function getPreviousStep(): ?self
    {
        if ($this->step_increment <= 1) {
            return null;
        }

        return static::where('grade_level', $this->grade_level)
                    ->where('step_increment', $this->step_increment - 1)
                    ->current()
                    ->first();
    }

    /**
     * Get the next grade level (next grade, step 1).
     */
    public function getNextGrade(): ?self
    {
        return static::where('grade_level', $this->grade_level + 1)
                    ->where('step_increment', 1)
                    ->current()
                    ->first();
    }

    /**
     * Get the previous grade level (previous grade, step 1).
     */
    public function getPreviousGrade(): ?self
    {
        if ($this->grade_level <= 1) {
            return null;
        }

        return static::where('grade_level', $this->grade_level - 1)
                    ->where('step_increment', 1)
                    ->current()
                    ->first();
    }

    /**
     * Get salary grade identifier (SG##-#).
     */
    public function getIdentifierAttribute(): string
    {
        return sprintf('SG%02d-%d', $this->grade_level, $this->step_increment);
    }

    /**
     * Boot method for model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate rates when salary is saved
        static::saving(function ($salaryGrade) {
            // Calculate daily rate (monthly salary / 22 working days)
            $salaryGrade->daily_rate = $salaryGrade->monthly_salary / 22;
            
            // Calculate hourly rate (daily rate / 8 hours)
            $salaryGrade->hourly_rate = $salaryGrade->daily_rate / 8;
            
            // Calculate overtime rates
            $salaryGrade->overtime_rate_regular = $salaryGrade->hourly_rate * 1.25; // 125%
            $salaryGrade->overtime_rate_special = $salaryGrade->hourly_rate * 1.30; // 130%
            $salaryGrade->overtime_rate_legal = $salaryGrade->hourly_rate * 2.00; // 200%
            
            // Calculate night differential (10% of hourly rate)
            $salaryGrade->night_differential_rate = $salaryGrade->hourly_rate * 0.10;
        });
    }

    /**
     * Find salary grade by grade level and step increment.
     */
    public static function findByGradeAndStep(int $gradeLevel, int $stepIncrement): ?self
    {
        return static::where('grade_level', $gradeLevel)
                    ->where('step_increment', $stepIncrement)
                    ->current()
                    ->first();
    }

    /**
     * Get all salary grades for a specific grade level.
     */
    public static function getGradeSteps(int $gradeLevel): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('grade_level', $gradeLevel)
                    ->current()
                    ->orderBy('step_increment')
                    ->get();
    }

    /**
     * The position levels available.
     */
    public static function getPositionLevels(): array
    {
        return [
            'First Level' => 'First Level',
            'Second Level' => 'Second Level',
            'Career Executive Service' => 'Career Executive Service',
            'Uniformed Personnel' => 'Uniformed Personnel',
            'Teaching Position' => 'Teaching Position',
            'Medical/Health' => 'Medical/Health',
            'Special Position' => 'Special Position'
        ];
    }

    /**
     * The SSL tranches available.
     */
    public static function getSslTranches(): array
    {
        return [
            'Tranche 1' => 'Tranche 1',
            'Tranche 2' => 'Tranche 2',
            'Tranche 3' => 'Tranche 3',
            'Tranche 4' => 'Tranche 4'
        ];
    }

    /**
     * The status options available.
     */
    public static function getStatusOptions(): array
    {
        return [
            'Active' => 'Active',
            'Superseded' => 'Superseded',
            'Future' => 'Future',
            'Cancelled' => 'Cancelled'
        ];
    }
}