<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'overall_rating',
        'strengths',
        'areas_for_improvement',
        'recommendations',
        'reviewed_by',
        'review_date',
    ];

    protected $casts = [
        'review_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class, 'period_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Document linking relationships
     */
    public function sourceLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'source');
    }

    public function targetLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'target');
    }

    /**
     * Get linked evidence documents
     */
    public function getEvidenceDocuments(bool $activeOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->targetLinks()->where('link_type', 'performance_evidence');
        
        if ($activeOnly) {
            $query->active();
        }

        return $query->with('source')->get()->pluck('source')
                    ->filter(function ($document) {
                        return $document instanceof EmployeeDocument;
                    });
    }
}