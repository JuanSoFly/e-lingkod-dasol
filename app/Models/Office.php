<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_id',
        'level',
        'head_title',
        'department_head_id',
        'contact_number',
        'email',
        'location',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'level' => 'integer',
    ];

    /**
     * Get the parent office
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'parent_id');
    }

    /**
     * Get the child offices
     */
    public function children(): HasMany
    {
        return $this->hasMany(Office::class, 'parent_id');
    }

    /**
     * Get the department head
     */
    public function departmentHead(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'department_head_id');
    }

    /**
     * Get the major final outputs for this office
     */
    public function majorFinalOutputs(): HasMany
    {
        return $this->hasMany(MajorFinalOutput::class);
    }

    /**
     * Get active major final outputs only
     */
    public function activeMajorFinalOutputs(): HasMany
    {
        return $this->majorFinalOutputs()->where('is_active', true);
    }

    /**
     * Get the office assignments
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(OfficeAssignment::class);
    }

    /**
     * Get the office assignments (alias for assignments)
     */
    public function officeAssignments(): HasMany
    {
        return $this->assignments();
    }

    /**
     * Get active assignments only
     */
    public function activeAssignments(): HasMany
    {
        return $this->assignments()->where('is_active', true);
    }

    /**
     * Get the OPCR workflows for this office
     */
    public function opcrWorkflows(): HasMany
    {
        return $this->hasMany(OPCRWorkflow::class);
    }

    /**
     * Get employees assigned to this office through assignments
     */
    public function employeesThroughAssignments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Employee::class,
            OfficeAssignment::class,
            'office_id',
            'id',
            'id',
            'employee_id'
        )->where('office_assignments.is_active', true);
    }

    /**
     * Get employees belonging to this office based on department matching
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'office_id');
    }

    /**
     * Get employee count for this office based on department matching
     */
    public function getEmployeesCountAttribute()
    {
        $query = Employee::where(function ($q) {
            // Direct department name match
            $q->where('department', $this->name);

            // Handle specific department mappings for variations
            $departmentMappings = [
                'Business Permit and Licensing Office' => ['Business Permits and Licensing Office'],
                'Municipal Civil Registrar\'s Office' => ['Local Civil Registry Office'],
                'Municipal Agriculture Office' => ['Municipal Agriculturist\'s Office'],
                'Assessor\'s Office' => ['Municipal Assessor\'s Office'],
                'Accounting Office' => ['Office of the Municipal Accountant'],
                'Budget and Treasury Office' => ['Office of the Municipal Budget Office', 'Municipal Treasurer\'s Office'],
                'Cooperatives and Tourist Office' => ['Municipal Tourism and Cultural Affairs Office'],
                'Municipal Health Office' => ['Rural Health Unit'],
            ];

            foreach ($departmentMappings as $officeName => $departments) {
                if ($this->name === $officeName) {
                    $q->orWhereIn('department', $departments);
                }
            }
        });

        return $query->count();
    }

    /**
     * Get active MFO count for this office.
     */
    public function getMfosCountAttribute(): int
    {
        if (array_key_exists('mfos_count', $this->attributes)) {
            return (int) $this->attributes['mfos_count'];
        }

        if (array_key_exists('active_major_final_outputs_count', $this->attributes)) {
            return (int) $this->attributes['active_major_final_outputs_count'];
        }

        if ($this->relationLoaded('activeMajorFinalOutputs')) {
            return $this->activeMajorFinalOutputs->count();
        }

        return $this->activeMajorFinalOutputs()->count();
    }

    /**
     * Get OPCR workflow count for this office.
     */
    public function getOpcrCountAttribute(): int
    {
        if (array_key_exists('opcr_count', $this->attributes)) {
            return (int) $this->attributes['opcr_count'];
        }

        if (array_key_exists('opcr_workflows_count', $this->attributes)) {
            return (int) $this->attributes['opcr_workflows_count'];
        }

        if ($this->relationLoaded('opcrWorkflows')) {
            return $this->opcrWorkflows->count();
        }

        return $this->opcrWorkflows()->count();
    }

    /**
     * Scope to get only active offices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root level offices (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get offices by level
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to search offices
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    /**
     * Get the full hierarchical path
     */
    public function getFullPathAttribute(): string
    {
        $path = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->name);
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
     * Check if this office has children
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
    public function root(): Office
    {
        $root = $this;
        while ($root->parent) {
            $root = $root->parent;
        }
        return $root;
    }

    /**
     * Get all descendant IDs (recursive)
     */
    public function getDescendantIdsAttribute(): array
    {
        $ids = [];

        foreach ($this->children()->where('is_active', true)->get() as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->descendant_ids);
        }

        return $ids;
    }

    /**
     * Get office statistics
     */
    public function getStatisticsAttribute(): array
    {
        return [
            'total_employees' => $this->employees()->count(),
            'active_mfos' => $this->majorFinalOutputs()->where('is_active', true)->count(),
            'total_success_indicators' => $this->activeMajorFinalOutputs()
                ->withCount('activeSuccessIndicators')
                ->get()
                ->sum('active_success_indicators_count'),
            'active_workflows' => $this->opcrWorkflows()
                ->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation'])
                ->count(),
            'completed_workflows' => $this->opcrWorkflows()
                ->where('workflow_state', 'final_approval')
                ->count(),
            'department_head' => $this->departmentHead ? [
                'id' => $this->departmentHead->id,
                'name' => $this->departmentHead->full_name,
                'employee_number' => $this->departmentHead->employee_number,
            ] : null,
        ];
    }

    /**
     * Get active users by role
     */
    public function getUsersByRole(string $role): Collection
    {
        return $this->activeAssignments()
            ->where('role', $role)
            ->with('user')
            ->get()
            ->pluck('user');
    }

    /**
     * Get department heads
     */
    public function getDepartmentHeadsAttribute(): Collection
    {
        return $this->getUsersByRole('Department Head');
    }

    /**
     * Get assessors
     */
    public function getAssessorsAttribute(): Collection
    {
        return $this->getUsersByRole('Assessor');
    }

    /**
     * Get final approvers
     */
    public function getFinalApproversAttribute(): Collection
    {
        return $this->getUsersByRole('Final Approver');
    }

    /**
     * Check if user has specific role in this office
     */
    public function hasUserRole($user, string $role): bool
    {
        return $this->activeAssignments()
            ->where('user_id', $user->id ?? $user)
            ->where('role', $role)
            ->exists();
    }

    /**
     * Assign user to office with role
     */
    public function assignUser($user, string $role, array $metadata = []): OfficeAssignment
    {
        return $this->assignments()->create([
            'user_id' => $user->id ?? $user,
            'role' => $role,
            'is_active' => true,
            'assigned_at' => now(),
            'assigned_by' => auth()->id(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Remove user assignment
     */
    public function removeUserAssignment($user, string $role): bool
    {
        return $this->assignments()
            ->where('user_id', $user->id ?? $user)
            ->where('role', $role)
            ->update([
                'is_active' => false,
                'removed_at' => now(),
                'removed_by' => auth()->id(),
            ]);
    }

    /**
     * Get OPCR performance summary
     */
    public function getOPCRPerformanceSummaryAttribute(): array
    {
        $workflows = $this->opcrWorkflows()
            ->with('office')
            ->get();

        $byState = $workflows->groupBy('workflow_state')
            ->map(fn($group) => $group->count())
            ->toArray();

        $completedWorkflows = $workflows->where('workflow_state', 'final_approval');
        $averageRating = $completedWorkflows->avg('overall_rating');

        return [
            'total_workflows' => $workflows->count(),
            'workflows_by_state' => $byState,
            'average_rating' => round($averageRating, 2),
            'completion_rate' => $workflows->count() > 0
                ? round(($byState['final_approval'] ?? 0) / $workflows->count() * 100, 2)
                : 0,
        ];
    }

    /**
     * Create office with automatic code generation
     */
    public static function createWithAutoCode(array $data): self
    {
        if (empty($data['code'])) {
            $parentCode = '';
            if (!empty($data['parent_id'])) {
                $parent = self::find($data['parent_id']);
                if ($parent) {
                    $parentCode = $parent->code . '-';
                }
            }

            $latestCode = DB::table('offices')
                ->whereRaw('code REGEXP \'^' . preg_quote($parentCode) . '[0-9]+$\'')
                ->orderByRaw('CAST(SUBSTRING(code, LENGTH(?) + 1) AS UNSIGNED) DESC', [$parentCode])
                ->value('code');

            $nextNumber = $latestCode ? (int)substr($latestCode, strlen($parentCode)) + 1 : 1;
            $autoCode = $parentCode . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $data['code'] = $autoCode;
        }

        if (empty($data['level'])) {
            $data['level'] = !empty($data['parent_id'])
                ? (self::find($data['parent_id'])->level ?? 0) + 1
                : 1;
        }

        return self::create(array_merge($data, ['is_active' => true]));
    }

    /**
     * Get validation rules for the model
     */
    public static function getValidationRules(): array
    {
        return [
            'code' => 'nullable|string|max:50|unique:offices,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:offices,id',
            'level' => 'nullable|integer|min:1',
            'head_title' => 'nullable|string|max:100',
            'department_head_id' => 'nullable|exists:employees,id',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'location' => 'nullable|string|max:200',
        ];
    }

    /**
     * Get custom validation messages
     */
    public static function getValidationMessages(): array
    {
        return [
            'name.required' => 'The office name is required.',
            'code.unique' => 'The office code has already been taken.',
            'parent_id.exists' => 'The selected parent office is invalid.',
            'department_head_id.exists' => 'The selected department head is invalid.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }

    /**
     * Check if office can be deleted
     */
    public function canBeDeleted(): bool
    {
        // Check if office has active employees
        if ($this->employees()->exists()) {
            return false;
        }

        // Check if office has active MFOs
        if ($this->activeMajorFinalOutputs()->exists()) {
            return false;
        }

        // Check if office has active workflows
        if ($this->opcrWorkflows()->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation'])->exists()) {
            return false;
        }

        // Check if office has active children
        if ($this->children()->where('is_active', true)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Get office hierarchy as tree
     */
    public static function getHierarchyTree(): array
    {
        $rootOffices = self::with(['children' => function ($query) {
            $query->with('children')->where('is_active', true);
        }])->whereNull('parent_id')->where('is_active', true)->get();

        return $rootOffices->map(function ($office) {
            return [
                'id' => $office->id,
                'code' => $office->code,
                'name' => $office->name,
                'level' => $office->level,
                'children' => $office->children->map(function ($child) {
                    return self::buildTreeNode($child);
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Build tree node recursively
     */
    private static function buildTreeNode(Office $office): array
    {
        return [
            'id' => $office->id,
            'code' => $office->code,
            'name' => $office->name,
            'level' => $office->level,
            'children' => $office->children->map(function ($child) {
                return self::buildTreeNode($child);
            })->toArray(),
        ];
    }

    /**
     * Get all offices as flat list with path
     */
    public static function getFlatList(): Collection
    {
        return self::where('is_active', true)
            ->orderBy('level')
            ->orderBy('code')
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'code' => $office->code,
                    'name' => $office->name,
                    'full_path' => $office->full_path,
                    'level' => $office->level,
                    'is_selectable' => true,
                ];
            });
    }
}
