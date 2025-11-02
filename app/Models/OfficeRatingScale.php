<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OfficeRatingScale extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'rating_scale_id',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the office that owns this rating scale assignment.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the rating scale assigned to this office.
     */
    public function ratingScale(): BelongsTo
    {
        return $this->belongsTo(RatingScale::class);
    }

    /**
     * Check if this assignment is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();

        return $this->is_active &&
               $this->effective_from <= $now &&
               ($this->effective_to === null || $this->effective_to >= $now);
    }

    /**
     * Check if this assignment is active on a specific date.
     */
    public function isActiveOn(\DateTime $date): bool
    {
        return $this->is_active &&
               $this->effective_from <= $date &&
               ($this->effective_to === null || $this->effective_to >= $date);
    }

    /**
     * Deactivate this rating scale assignment.
     */
    public function deactivate(): void
    {
        $this->update([
            'effective_to' => now(),
            'is_active' => false,
            'updated_by' => auth()->user()->name ?? 'system',
        ]);
    }

    /**
     * Extend the effective period.
     */
    public function extendTo(?\DateTime $newEndDate): void
    {
        $this->update([
            'effective_to' => $newEndDate,
            'updated_by' => auth()->user()->name ?? 'system',
        ]);
    }

    /**
     * Scope to get currently active assignments.
     */
    public function scopeCurrentlyActive($query)
    {
        return $query->where('is_active', true)
                   ->where('effective_from', '<=', now())
                   ->where(function ($subQuery) {
                       $subQuery->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', now());
                   });
    }

    /**
     * Scope to get assignments active on a specific date.
     */
    public function scopeActiveOn($query, \DateTime $date)
    {
        return $query->where('is_active', true)
                   ->where('effective_from', '<=', $date)
                   ->where(function ($subQuery) use ($date) {
                       $subQuery->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $date);
                   });
    }

    /**
     * Scope to get assignments for a specific office.
     */
    public function scopeForOffice($query, Office $office)
    {
        return $query->where('office_id', $office->id);
    }

    /**
     * Scope to get assignments using a specific rating scale.
     */
    public function scopeUsingScale($query, RatingScale $scale)
    {
        return $query->where('rating_scale_id', $scale->id);
    }

    /**
     * Validation rules for office rating scales.
     */
    public static function getValidationRules(): array
    {
        return [
            'office_id' => 'required|exists:offices,id',
            'rating_scale_id' => 'required|exists:rating_scales,id',
            'effective_from' => 'required|date|before_or_equal:effective_to',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'boolean',
        ];
    }
}