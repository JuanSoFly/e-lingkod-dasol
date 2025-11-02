<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RatingScaleValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'rating_scale_id',
        'rating_value',
        'rating_label',
        'min_percentage',
        'max_percentage',
        'color_code',
        'display_order',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'rating_value' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Get the rating scale that owns this value.
     */
    public function ratingScale(): BelongsTo
    {
        return $this->belongsTo(RatingScale::class);
    }

    /**
     * Check if a percentage falls within this rating range.
     */
    public function isInRange(float $percentage): bool
    {
        return $percentage >= $this->min_percentage && $percentage <= $this->max_percentage;
    }

    /**
     * Get the CSS color class for this rating.
     */
    public function getColorClass(): string
    {
        $colorMap = [
            '#10b981' => 'text-green-500 bg-green-50',
            '#3b82f6' => 'text-blue-500 bg-blue-50',
            '#f59e0b' => 'text-yellow-500 bg-yellow-50',
            '#f97316' => 'text-orange-500 bg-orange-50',
            '#ef4444' => 'text-red-500 bg-red-50',
        ];

        return $colorMap[$this->color_code] ?? 'text-gray-500 bg-gray-50';
    }

    /**
     * Get the rating label with color styling.
     */
    public function getStyledLabel(): string
    {
        return sprintf(
            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium %s">%s</span>',
            $this->getColorClass(),
            $this->rating_label
        );
    }

    /**
     * Scope to get values ordered by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'desc')
                   ->orderBy('rating_value', 'desc');
    }

    /**
     * Scope to get values within a percentage range.
     */
    public function scopeInRange($query, float $percentage)
    {
        return $query->where('min_percentage', '<=', $percentage)
                   ->where('max_percentage', '>=', $percentage);
    }

    /**
     * Validation rules for rating scale values.
     */
    public static function getValidationRules(): array
    {
        return [
            'rating_value' => 'required|integer|min:1|max:5',
            'rating_label' => 'required|string|max:50',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'required|numeric|min:0|max:100',
            'color_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'display_order' => 'required|integer|min:0|max:10',
        ];
    }
}