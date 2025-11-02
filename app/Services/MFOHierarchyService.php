<?php

namespace App\Services;

use App\Models\MajorFinalOutput;
use App\Models\SuccessIndicator;
use App\Models\Office;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;

class MFOHierarchyService
{
    /**
     * Create MFO hierarchy for an office
     */
    public function createMFOHierarchy(Office $office, array $hierarchyData): Collection
    {
        return DB::transaction(function () use ($office, $hierarchyData) {
            $createdMFOs = collect();

            foreach ($hierarchyData as $mfoData) {
                $mfo = $this->createMFO($office, $mfoData);
                $createdMFOs->push($mfo);

                // Create child MFOs if any
                if (!empty($mfoData['children'])) {
                    $this->createChildMFOs($mfo, $mfoData['children']);
                }

                // Create success indicators
                if (!empty($mfoData['success_indicators'])) {
                    $this->createSuccessIndicators($mfo, $mfoData['success_indicators']);
                }
            }

            Activity::log('MFO hierarchy created', [
                'office_id' => $office->id,
                'mfo_count' => $createdMFOs->count(),
                'created_by' => auth()->id(),
            ]);

            return $createdMFOs;
        });
    }

    /**
     * Create a single MFO
     */
    private function createMFO(Office $office, array $data): MajorFinalOutput
    {
        return MajorFinalOutput::create([
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'level' => $data['level'] ?? 1,
            'office_id' => $office->id,
            'is_active' => $data['is_active'] ?? true,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Create child MFOs recursively
     */
    private function createChildMFOs(MajorFinalOutput $parent, array $childrenData): void
    {
        foreach ($childrenData as $childData) {
            $child = $this->createMFO($parent->office, array_merge($childData, [
                'parent_id' => $parent->id,
                'level' => $parent->level + 1,
            ]));

            if (!empty($childData['children'])) {
                $this->createChildMFOs($child, $childData['children']);
            }

            if (!empty($childData['success_indicators'])) {
                $this->createSuccessIndicators($child, $childData['success_indicators']);
            }
        }
    }

    /**
     * Create success indicators for an MFO
     */
    private function createSuccessIndicators(MajorFinalOutput $mfo, array $indicatorsData): void
    {
        foreach ($indicatorsData as $indicatorData) {
            SuccessIndicator::create([
                'mfo_id' => $mfo->id,
                'code' => $indicatorData['code'],
                'title' => $indicatorData['title'],
                'description' => $indicatorData['description'] ?? null,
                'target_quantity' => $indicatorData['target_quantity'] ?? null,
                'target_efficiency' => $indicatorData['target_efficiency'] ?? null,
                'target_timeliness' => $indicatorData['target_timeliness'] ?? null,
                'created_by' => auth()->id(),
                'is_active' => $indicatorData['is_active'] ?? true,
            ]);
        }
    }

    /**
     * Get MFO hierarchy tree for an office
     */
    public function getMFOHierarchy(Office $office, bool $includeInactive = false): Collection
    {
        $query = MajorFinalOutput::with([
            'successIndicators' => function ($query) use ($includeInactive) {
                if (!$includeInactive) {
                    $query->where('is_active', true);
                }
            },
            'children' => function ($query) use ($includeInactive) {
                if (!$includeInactive) {
                    $query->where('is_active', true);
                }
            }
        ])
        ->where('office_id', $office->id);

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        $mfos = $query->whereNull('parent_id')
            ->orderBy('level')
            ->orderBy('code')
            ->get();

        return $this->buildHierarchyTree($mfos);
    }

    /**
     * Build hierarchical tree structure
     */
    private function buildHierarchyTree(Collection $mfos): Collection
    {
        return $mfos->map(function ($mfo) {
            return [
                'id' => $mfo->id,
                'code' => $mfo->code,
                'title' => $mfo->title,
                'description' => $mfo->description,
                'level' => $mfo->level,
                'is_active' => $mfo->is_active,
                'success_indicators_count' => $mfo->successIndicators->count(),
                'success_indicators' => $mfo->successIndicators->map(function ($si) {
                    return [
                        'id' => $si->id,
                        'code' => $si->code,
                        'title' => $si->title,
                        'description' => $si->description,
                        'target_quantity' => $si->target_quantity,
                        'target_efficiency' => $si->target_efficiency,
                        'target_timeliness' => $si->target_timeliness,
                        'accomplished_quantity' => $si->accomplished_quantity,
                        'accomplished_efficiency' => $si->accomplished_efficiency,
                        'accomplished_timeliness' => $si->accomplished_timeliness,
                        'average_rating' => $si->average_rating,
                        'adjectival_rating' => $si->adjectival_rating,
                        'is_active' => $si->is_active,
                    ];
                }),
                'children' => $this->buildHierarchyTree($mfo->children),
            ];
        });
    }

    /**
     * Update MFO hierarchy
     */
    public function updateMFOHierarchy(MajorFinalOutput $mfo, array $data): MajorFinalOutput
    {
        return DB::transaction(function () use ($mfo, $data) {
            $mfo->update([
                'title' => $data['title'] ?? $mfo->title,
                'description' => $data['description'] ?? $mfo->description,
                'is_active' => $data['is_active'] ?? $mfo->is_active,
                'metadata' => $data['metadata'] ?? $mfo->metadata,
            ]);

            Activity::log('MFO updated', [
                'mfo_id' => $mfo->id,
                'changes' => $data,
                'updated_by' => auth()->id(),
            ]);

            return $mfo;
        });
    }

    /**
     * Get MFO statistics for an office
     */
    public function getMFOStatistics(Office $office): array
    {
        $mfos = MajorFinalOutput::where('office_id', $office->id)
            ->where('is_active', true)
            ->get();

        $totalMFOs = $mfos->count();
        $totalSIs = SuccessIndicator::whereIn('mfo_id', $mfos->pluck('id'))
            ->where('is_active', true)
            ->count();

        $siCountsByLevel = $mfos->groupBy('level')->map(function ($group) {
            return [
                'mfo_count' => $group->count(),
                'si_count' => SuccessIndicator::whereIn('mfo_id', $group->pluck('id'))
                    ->where('is_active', true)
                    ->count(),
            ];
        });

        return [
            'total_mfos' => $totalMFOs,
            'total_success_indicators' => $totalSIs,
            'hierarchy_levels' => $mfos->max('level') ?? 0,
            'mfos_by_level' => $siCountsByLevel,
            'average_si_per_mfo' => $totalMFOs > 0 ? round($totalSIs / $totalMFOs, 2) : 0,
        ];
    }

    /**
     * Get all descendant MFO IDs for the given MFO.
     */
    public function getAllDescendantIds(MajorFinalOutput $mfo, bool $includeInactive = false): array
    {
        $descendantIds = [];
        $parentIds = [$mfo->id];

        while (!empty($parentIds)) {
            $childrenQuery = MajorFinalOutput::query()
                ->whereIn('parent_id', $parentIds);

            if (!$includeInactive) {
                $childrenQuery->where('is_active', true);
            }

            $children = $childrenQuery->pluck('id');

            if ($children->isEmpty()) {
                break;
            }

            $descendantIds = array_merge($descendantIds, $children->all());
            $parentIds = $children->all();
        }

        return array_values(array_unique($descendantIds));
    }

    /**
     * Validate MFO code uniqueness within office
     */
    public function validateMFOCode(Office $office, string $code, ?int $excludeId = null): bool
    {
        $query = MajorFinalOutput::where('office_id', $office->id)
            ->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Validate Success Indicator code uniqueness within MFO
     */
    public function validateSuccessIndicatorCode(MajorFinalOutput $mfo, string $code, ?int $excludeId = null): bool
    {
        $query = SuccessIndicator::where('mfo_id', $mfo->id)
            ->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Get available MFO codes for office
     */
    public function generateMFOCode(Office $office): string
    {
        $maxCode = MajorFinalOutput::where('office_id', $office->id)
            ->where('code', 'like', 'MFO-%')
            ->max('code');

        if ($maxCode) {
            $number = intval(str_replace('MFO-', '', $maxCode)) + 1;
        } else {
            $number = 1;
        }

        return 'MFO-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get available Success Indicator codes for MFO
     */
    public function generateSuccessIndicatorCode(MajorFinalOutput $mfo): string
    {
        $maxCode = SuccessIndicator::where('mfo_id', $mfo->id)
            ->where('code', 'like', 'SI-%')
            ->max('code');

        if ($maxCode) {
            $number = intval(str_replace('SI-', '', $maxCode)) + 1;
        } else {
            $number = 1;
        }

        return 'SI-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Delete MFO and its hierarchy (soft delete)
     */
    public function deleteMFOHierarchy(MajorFinalOutput $mfo): bool
    {
        return DB::transaction(function () use ($mfo) {
            // Soft delete all children recursively
            $this->softDeleteChildren($mfo);

            // Soft delete success indicators
            $mfo->successIndicators()->update(['is_active' => false]);

            // Soft delete MFO itself
            $mfo->update(['is_active' => false]);

            Activity::log('MFO hierarchy deleted', [
                'mfo_id' => $mfo->id,
                'deleted_by' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Soft delete children recursively
     */
    private function softDeleteChildren(MajorFinalOutput $mfo): void
    {
        foreach ($mfo->children as $child) {
            $this->softDeleteChildren($child);
            $child->successIndicators()->update(['is_active' => false]);
            $child->update(['is_active' => false]);
        }
    }

    /**
     * Restore MFO hierarchy
     */
    public function restoreMFOHierarchy(MajorFinalOutput $mfo): bool
    {
        return DB::transaction(function () use ($mfo) {
            // Restore MFO
            $mfo->update(['is_active' => true]);

            // Restore success indicators
            $mfo->successIndicators()->update(['is_active' => true]);

            // Restore children recursively
            $this->restoreChildren($mfo);

            Activity::log('MFO hierarchy restored', [
                'mfo_id' => $mfo->id,
                'restored_by' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Restore children recursively
     */
    private function restoreChildren(MajorFinalOutput $mfo): void
    {
        foreach ($mfo->children as $child) {
            $child->update(['is_active' => true]);
            $child->successIndicators()->update(['is_active' => true]);
            $this->restoreChildren($child);
        }
    }
}
