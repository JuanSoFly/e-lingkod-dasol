<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class RatingScale extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_default',
        'scale_configuration',
        'qet_weights',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'scale_configuration' => 'array',
        'qet_weights' => 'array',
    ];

    /**
     * Get the rating scale values for this scale.
     */
    public function ratingValues(): HasMany
    {
        return $this->hasMany(RatingScaleValue::class)
            ->orderBy('display_order', 'desc')
            ->orderBy('rating_value', 'desc');
    }

    /**
     * Get the offices that use this rating scale.
     */
    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'office_rating_scales')
            ->withPivot(['effective_from', 'effective_to', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the active rating scale values.
     */
    public function activeRatingValues(): HasMany
    {
        return $this->ratingValues();
    }

    /**
     * Get the default rating scale for the system.
     */
    public static function getDefault(): ?RatingScale
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the rating scale for a specific office.
     */
    public static function getForOffice(Office $office, ?\DateTime $date = null): ?RatingScale
    {
        $date = $date ?? now();

        return static::whereHas('offices', function ($query) use ($office, $date) {
            $query->where('office_id', $office->id)
                  ->where('effective_from', '<=', $date)
                  ->where(function ($subQuery) use ($date) {
                      $subQuery->whereNull('effective_to')
                               ->orWhere('effective_to', '>=', $date);
                  })
                  ->where('is_active', true);
        })->where('is_active', true)->first();
    }

    /**
     * Get rating scale as array for dropdown/select options.
     */
    public function getRatingScaleOptions(): array
    {
        return $this->ratingValues()
            ->orderBy('display_order', 'desc')
            ->pluck('rating_label', 'rating_value')
            ->toArray();
    }

    /**
     * Get QET weights.
     */
    public function getQETWeights(): array
    {
        return $this->qet_weights ?? [
            'quality' => 0.4,
            'efficiency' => 0.3,
            'timeliness' => 0.3,
        ];
    }

    /**
     * Calculate weighted average rating.
     */
    public function calculateWeightedAverage(array $qetRatings): float
    {
        $weights = $this->getQETWeights();

        $weightedSum = (
            ($qetRatings['quality_rating'] * $weights['quality']) +
            ($qetRatings['efficiency_rating'] * $weights['efficiency']) +
            ($qetRatings['timeliness_rating'] * $weights['timeliness'])
        );

        return round($weightedSum, 2);
    }

    /**
     * Get adjectival rating from numerical rating.
     */
    public function getAdjectivalRating(float $rating): string
    {
        $roundedRating = round($rating);
        $ratingValue = $this->ratingValues()
            ->where('rating_value', $roundedRating)
            ->first();

        return $ratingValue?->rating_label ?? 'Not Rated';
    }

    /**
     * Get rating based on percentage.
     */
    public function getRatingFromPercentage(float $percentage): array
    {
        $ratingValue = $this->ratingValues()
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->first();

        if (!$ratingValue) {
            return [
                'rating_value' => 1,
                'rating_label' => 'Not Rated',
                'color_code' => '#6b7280'
            ];
        }

        return [
            'rating_value' => $ratingValue->rating_value,
            'rating_label' => $ratingValue->rating_label,
            'color_code' => $ratingValue->color_code
        ];
    }

    /**
     * Get all rating values formatted for display.
     */
    public function getFormattedRatingValues(): Collection
    {
        return $this->ratingValues()
            ->orderBy('display_order', 'desc')
            ->get()
            ->map(function ($value) {
                return [
                    'value' => $value->rating_value,
                    'label' => $value->rating_label,
                    'min_percentage' => $value->min_percentage,
                    'max_percentage' => $value->max_percentage,
                    'color' => $value->color_code,
                    'display_order' => $value->display_order,
                ];
            });
    }

    /**
     * Set as default rating scale (unsets other defaults).
     */
    public function setAsDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Check if this scale can be deleted (not used by any office).
     */
    public function canBeDeleted(): bool
    {
        if ($this->is_default) {
            return false;
        }

        return !$this->offices()
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Get rating scale validation rules.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'qet_weights.quality' => 'required|numeric|min:0|max:1',
            'qet_weights.efficiency' => 'required|numeric|min:0|max:1',
            'qet_weights.timeliness' => 'required|numeric|min:0|max:1',
            'rating_values' => 'required|array|min:2',
            'rating_values.*.rating_value' => 'required|integer|min:1|max:5',
            'rating_values.*.rating_label' => 'required|string|max:50',
            'rating_values.*.min_percentage' => 'required|numeric|min:0|max:100',
            'rating_values.*.max_percentage' => 'required|numeric|min:0|max:100',
            'rating_values.*.color_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    /**
     * Validate QET weights sum to 1.0.
     */
    public function validateQETWeights(array $weights): bool
    {
        $sum = array_sum($weights);
        return abs($sum - 1.0) < 0.01; // Allow small floating point errors
    }

    /**
     * Get configuration as array.
     */
    public function getConfigurationArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'scale_configuration' => $this->scale_configuration,
            'qet_weights' => $this->getQETWeights(),
            'rating_values' => $this->getFormattedRatingValues()->toArray(),
        ];
    }
}
