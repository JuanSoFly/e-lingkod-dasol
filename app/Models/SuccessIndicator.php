<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class SuccessIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'mfo_id',
        'code',
        'title',
        'description',
        'target_quantity',
        'target_efficiency',
        'target_timeliness',
        'accomplished_quantity',
        'accomplished_efficiency',
        'accomplished_timeliness',
        'rating_quantity',
        'rating_efficiency',
        'rating_timeliness',
        'average_rating',
        'adjectival_rating',
        'remarks',
        'evidence_documents',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'target_quantity' => 'decimal:2',
        'accomplished_quantity' => 'decimal:2',
        'rating_quantity' => 'integer',
        'rating_efficiency' => 'integer',
        'rating_timeliness' => 'integer',
        'average_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'evidence_documents' => 'array',
    ];

    /**
     * Get the MFO that owns the success indicator
     */
    public function mfo(): BelongsTo
    {
        return $this->belongsTo(MajorFinalOutput::class, 'mfo_id');
    }

    /**
     * Get the user who created the success indicator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the performance targets for this success indicator
     */
    public function performanceTargets(): HasMany
    {
        return $this->hasMany(PerformanceTarget::class, 'success_indicator_id');
    }

    /**
     * Get the performance ratings for this success indicator (direct relationship)
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(PerformanceRating::class, 'success_indicator_id');
    }

    /**
     * Get performance ratings through performance targets (alternative relationship)
     */
    public function ratingsThroughTargets(): HasManyThrough
    {
        return $this->hasManyThrough(
            PerformanceRating::class,
            PerformanceTarget::class,
            'success_indicator_id', // Foreign key on performance_targets table
            'target_id', // Foreign key on performance_ratings table
            'id', // Local key on success_indicators table
            'id' // Local key on performance_targets table
        );
    }

    /**
     * Get the latest rating for this success indicator
     */
    public function latestRating()
    {
        return $this->ratings()->latest()->first();
    }

    /**
     * Scope to get only active success indicators
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get success indicators by MFO
     */
    public function scopeByMFO($query, $mfoId)
    {
        return $query->where('mfo_id', $mfoId);
    }

    /**
     * Scope to get rated success indicators
     */
    public function scopeRated($query)
    {
        return $query->whereNotNull('average_rating');
    }

    /**
     * Scope to get success indicators needing rating
     */
    public function scopeNeedingRating($query)
    {
        return $query->whereNull('average_rating')
                    ->whereNotNull('accomplished_quantity');
    }

    /**
     * Calculate QET average rating
     */
    public function calculateQETRating(): ?float
    {
        if ($this->rating_quantity === null ||
            $this->rating_efficiency === null ||
            $this->rating_timeliness === null) {
            return null;
        }

        return round(($this->rating_quantity + $this->rating_efficiency + $this->rating_timeliness) / 3, 2);
    }

    /**
     * Get adjectival rating based on average rating
     */
    public function getAdjectivalRatingAttribute(): ?string
    {
        $rating = $this->average_rating;

        if ($rating === null) {
            return null;
        }

        return match (true) {
            $rating >= 4.5 => 'Outstanding',
            $rating >= 3.5 => 'Very Satisfactory',
            $rating >= 2.5 => 'Satisfactory',
            $rating >= 1.5 => 'Unsatisfactory',
            default => 'Poor',
        };
    }

    /**
     * Update QET ratings and calculate average
     */
    public function updateQETRatings(array $ratings): bool
    {
        $this->rating_quantity = $ratings['rating_quantity'] ?? null;
        $this->rating_efficiency = $ratings['rating_efficiency'] ?? null;
        $this->rating_timeliness = $ratings['rating_timeliness'] ?? null;
        $this->remarks = $ratings['remarks'] ?? null;
        $this->evidence_documents = $ratings['evidence_documents'] ?? $this->evidence_documents;

        // Calculate average rating
        if ($this->rating_quantity !== null &&
            $this->rating_efficiency !== null &&
            $this->rating_timeliness !== null) {
            $this->average_rating = $this->calculateQETRating();
            $this->adjectival_rating = $this->getAdjectivalRatingAttribute();
        }

        return $this->save();
    }

    /**
     * Update accomplishments
     */
    public function updateAccomplishments(array $accomplishments): bool
    {
        try {
            $this->accomplished_quantity = $accomplishments['accomplished_quantity'] ?? null;
            $this->accomplished_efficiency = $accomplishments['accomplished_efficiency'] ?? null;
            $this->accomplished_timeliness = $accomplishments['accomplished_timeliness'] ?? null;
            $this->remarks = $accomplishments['remarks'] ?? $this->remarks;
            $this->evidence_documents = $accomplishments['evidence_documents'] ?? $this->evidence_documents;

            return $this->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get performance percentage
     */
    public function getPerformancePercentageAttribute(): ?float
    {
        if ($this->target_quantity === null || $this->accomplished_quantity === null || $this->target_quantity == 0) {
            return null;
        }

        return round(($this->accomplished_quantity / $this->target_quantity) * 100, 2);
    }

    /**
     * Check if target is met
     */
    public function getIsTargetMetAttribute(): bool
    {
        $percentage = $this->performance_percentage;
        return $percentage !== null && $percentage >= 100;
    }

    /**
     * Get rating color class for UI
     */
    public function getRatingColorAttribute(): string
    {
        return match ($this->adjectival_rating) {
            'Outstanding' => 'text-green-600 bg-green-100',
            'Very Satisfactory' => 'text-blue-600 bg-blue-100',
            'Satisfactory' => 'text-yellow-600 bg-yellow-100',
            'Unsatisfactory' => 'text-orange-600 bg-orange-100',
            'Poor' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * Get QET rating details
     */
    public function getQETDetailsAttribute(): array
    {
        return [
            'quantity' => [
                'target' => $this->target_quantity,
                'accomplished' => $this->accomplished_quantity,
                'rating' => $this->rating_quantity,
                'percentage' => $this->performance_percentage,
                'is_met' => $this->is_target_met,
            ],
            'efficiency' => [
                'target' => $this->target_efficiency,
                'accomplished' => $this->accomplished_efficiency,
                'rating' => $this->rating_efficiency,
            ],
            'timeliness' => [
                'target' => $this->target_timeliness,
                'accomplished' => $this->accomplished_timeliness,
                'rating' => $this->rating_timeliness,
            ],
            'overall' => [
                'average_rating' => $this->average_rating,
                'adjectival_rating' => $this->adjectival_rating,
                'color_class' => $this->rating_color,
            ],
        ];
    }

    /**
     * Get validation rules for the model
     */
    public static function getValidationRules(): array
    {
        return [
            'mfo_id' => 'required|exists:major_final_outputs,id',
            'code' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_quantity' => 'nullable|numeric|min:0',
            'target_efficiency' => 'nullable|string|max:100',
            'target_timeliness' => 'nullable|string|max:100',
            'accomplished_quantity' => 'nullable|numeric|min:0',
            'accomplished_efficiency' => 'nullable|string|max:100',
            'accomplished_timeliness' => 'nullable|string|max:100',
            'rating_quantity' => 'nullable|integer|min:1|max:5',
            'rating_efficiency' => 'nullable|integer|min:1|max:5',
            'rating_timeliness' => 'nullable|integer|min:1|max:5',
            'remarks' => 'nullable|string',
            'evidence_documents' => 'nullable|array',
            'evidence_documents.*' => 'exists:documents,id',
        ];
    }

    /**
     * Get custom validation messages
     */
    public static function getValidationMessages(): array
    {
        return [
            'mfo_id.required' => 'The MFO field is required.',
            'mfo_id.exists' => 'The selected MFO is invalid.',
            'code.required' => 'The success indicator code is required.',
            'title.required' => 'The success indicator title is required.',
            'rating_quantity.min' => 'Quantity rating must be at least 1.',
            'rating_quantity.max' => 'Quantity rating must not exceed 5.',
            'rating_efficiency.min' => 'Efficiency rating must be at least 1.',
            'rating_efficiency.max' => 'Efficiency rating must not exceed 5.',
            'rating_timeliness.min' => 'Timeliness rating must be at least 1.',
            'rating_timeliness.max' => 'Timeliness rating must not exceed 5.',
        ];
    }

    /**
     * Create success indicator with automatic code generation
     */
    public static function createWithAutoCode(array $data, int $mfoId): self
    {
        $latestCode = DB::table('success_indicators')
            ->where('mfo_id', $mfoId)
            ->whereRaw('code REGEXP \'^SI-[0-9]+$\'')
            ->orderByRaw('CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC')
            ->value('code');

        $nextNumber = $latestCode ? (int)substr($latestCode, 3) + 1 : 1;
        $autoCode = 'SI-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return self::create(array_merge($data, [
            'code' => $autoCode,
            'mfo_id' => $mfoId,
            'is_active' => true,
        ]));
    }

    /**
     * Scope to search success indicators
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('remarks', 'like', "%{$search}%");
        });
    }

    /**
     * Get evidence documents with details
     */
    public function getEvidenceDocumentsWithDetailsAttribute(): array
    {
        if (empty($this->evidence_documents)) {
            return [];
        }

        return Document::whereIn('id', $this->evidence_documents)
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'file_path' => $document->file_path,
                    'file_size' => $document->file_size,
                    'mime_type' => $document->mime_type,
                    'download_url' => route('documents.download', $document->id),
                ];
            })
            ->toArray();
    }
}