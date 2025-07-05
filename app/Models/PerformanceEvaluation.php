<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'evaluation_period',
        'evaluation_date',
        'period_start',
        'period_end',
        'overall_rating',
        'quality_rating',
        'efficiency_rating',
        'timeliness_rating',
        'initiative_rating',
        'teamwork_rating',
        'leadership_rating',
        'goal_achievement_percentage',
        'achievements',
        'areas_for_improvement',
        'goals_next_period',
        'evaluator_comments',
        'employee_comments',
        'promotion_readiness',
        'leadership_potential',
        'evaluation_status',
        'evaluator_id',
        'approved_by',
        'submitted_at',
        'approved_at',
        'competency_scores',
        'development_plan',
        'performance_level'
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'overall_rating' => 'decimal:2',
        'quality_rating' => 'decimal:2',
        'efficiency_rating' => 'decimal:2',
        'timeliness_rating' => 'decimal:2',
        'initiative_rating' => 'decimal:2',
        'teamwork_rating' => 'decimal:2',
        'leadership_rating' => 'decimal:2',
        'goal_achievement_percentage' => 'decimal:2',
        'promotion_readiness' => 'boolean',
        'leadership_potential' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'competency_scores' => 'array'
    ];

    /**
     * Get the employee that owns the evaluation
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the evaluator (employee who conducted the evaluation)
     */
    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Scope for evaluations in a specific period
     */
    public function scopeForPeriod($query, $period)
    {
        return $query->where('evaluation_period', $period);
    }

    /**
     * Scope for evaluations with rating above threshold
     */
    public function scopeHighPerformers($query, $threshold = 4.0)
    {
        return $query->where('overall_rating', '>=', $threshold);
    }

    /**
     * Scope for evaluations with rating below threshold
     */
    public function scopeLowPerformers($query, $threshold = 3.0)
    {
        return $query->where('overall_rating', '<', $threshold);
    }

    /**
     * Scope for promotion ready employees
     */
    public function scopePromotionReady($query)
    {
        return $query->where('promotion_readiness', true)
                    ->where('overall_rating', '>=', 4.0);
    }

    /**
     * Scope for employees with leadership potential
     */
    public function scopeLeadershipPotential($query)
    {
        return $query->where('leadership_potential', true);
    }

    /**
     * Get performance level attribute
     */
    public function getPerformanceLevelAttribute()
    {
        if ($this->overall_rating >= 4.5) {
            return 'Outstanding';
        } elseif ($this->overall_rating >= 4.0) {
            return 'Exceeds Expectations';
        } elseif ($this->overall_rating >= 3.0) {
            return 'Meets Expectations';
        } elseif ($this->overall_rating >= 2.0) {
            return 'Below Expectations';
        } else {
            return 'Unsatisfactory';
        }
    }

    /**
     * Calculate average rating across all dimensions
     */
    public function getAverageRatingAttribute()
    {
        $ratings = [
            $this->quality_rating,
            $this->efficiency_rating,
            $this->timeliness_rating,
            $this->initiative_rating,
            $this->teamwork_rating,
            $this->leadership_rating
        ];

        $validRatings = array_filter($ratings, function($rating) {
            return !is_null($rating);
        });

        return !empty($validRatings) ? round(array_sum($validRatings) / count($validRatings), 2) : 0;
    }

    /**
     * Check if evaluation is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->evaluation_status === 'draft' && 
               $this->period_end < now()->subDays(30);
    }

    /**
     * Get formatted evaluation period
     */
    public function getFormattedPeriodAttribute()
    {
        return $this->period_start->format('M d, Y') . ' - ' . $this->period_end->format('M d, Y');
    }
}