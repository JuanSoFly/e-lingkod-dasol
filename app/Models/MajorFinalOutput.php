<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class MajorFinalOutput extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'description',
        'parent_id',
        'level',
        'is_active',
        'office_id',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'level' => 'integer',
    ];

    /**
     * Get the office that owns the MFO
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the parent MFO
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MajorFinalOutput::class, 'parent_id');
    }

    /**
     * Get the child MFOs
     */
    public function children(): HasMany
    {
        return $this->hasMany(MajorFinalOutput::class, 'parent_id');
    }

    /**
     * Get the success indicators for this MFO
     */
    public function successIndicators(): HasMany
    {
        return $this->hasMany(SuccessIndicator::class, 'mfo_id');
    }

    /**
     * Get active success indicators only
     */
    public function activeSuccessIndicators(): HasMany
    {
        return $this->successIndicators()->where('is_active', true);
    }

    /**
     * Scope to get only active MFOs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root level MFOs (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get MFOs by level
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to get MFOs for specific office
     */
    public function scopeForOffice($query, $officeId)
    {
        return $query->where('office_id', $officeId);
    }

    /**
     * Get the full hierarchical path
     */
    public function getFullPathAttribute(): string
    {
        $path = collect([$this->title]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->title);
            $parent = $parent->parent;
        }

        return $path->implode(' > ');
    }

    /**
     * Get the hierarchical code path
     */
    public function getFullCodePathAttribute(): string
    {
        $path = collect([$this->code]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->code);
            $parent = $parent->parent;
        }

        return $path->implode('.');
    }

    /**
     * Check if this MFO has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->where('is_active', true)->exists();
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (recursive)
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get the root ancestor
     */
    public function root(): MajorFinalOutput
    {
        $root = $this;
        while ($root->parent) {
            $root = $root->parent;
        }
        return $root;
    }

    /**
     * Get MFO statistics
     */
    public function getStatisticsAttribute(): array
    {
        return [
            'total_success_indicators' => $this->successIndicators()->count(),
            'active_success_indicators' => $this->activeSuccessIndicators()->count(),
            'direct_children' => $this->children()->where('is_active', true)->count(),
            'total_descendants' => $this->getAllDescendantsCount(),
            'average_rating' => $this->activeSuccessIndicators()
                ->whereNotNull('average_rating')
                ->avg('average_rating'),
        ];
    }

    /**
     * Get total count of all descendants
     */
    private function getAllDescendantsCount(): int
    {
        $count = $this->children()->where('is_active', true)->count();

        foreach ($this->children()->where('is_active', true)->get() as $child) {
            $count += $child->getAllDescendantsCount();
        }

        return $count;
    }

    /**
     * Get MFOs with their success indicators ratings summary
     */
    public function getRatingsSummaryAttribute(): array
    {
        $indicators = $this->activeSuccessIndicators;
        $ratedIndicators = $indicators->whereNotNull('average_rating');

        if ($ratedIndicators->isEmpty()) {
            return [
                'total_indicators' => $indicators->count(),
                'rated_indicators' => 0,
                'average_rating' => 0,
                'rating_distribution' => [],
            ];
        }

        $distribution = $ratedIndicators->groupBy('adjectival_rating')->map(function ($group) {
            return $group->count();
        });

        return [
            'total_indicators' => $indicators->count(),
            'rated_indicators' => $ratedIndicators->count(),
            'average_rating' => round($ratedIndicators->avg('average_rating'), 2),
            'rating_distribution' => $distribution->toArray(),
        ];
    }
}
