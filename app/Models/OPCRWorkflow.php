<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class OPCRWorkflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'opcr_workflows';

    protected $fillable = [
        'performance_evaluation_id',
        'office_id',
        'period_id',
        'workflow_state',
        'title',
        'opcr_workflow_id',
        'overall_rating',
        'overall_adjectival_rating',
        'summary',
        'recommendations',
        'justification',
        // Current database structure (Migration B)
        'created_by',
        'department_head_id',
        'assessor_id',
        'final_approver_id',
        'commitment_date',
        'accomplishment_date',
        'evaluation_date',
        'final_approval_date',
        'adjectival_rating',
        'final_remarks',
        'department_head_remarks',
        'assessor_remarks',
        'final_approver_remarks',
        'file_attachments',
        'is_archived',
        'archived_at',
        // Expected structure (Migration A) - for future compatibility
        'committed_by',
        'committed_at',
        'submitted_by',
        'submitted_at',
        'assessed_by',
        'assessed_at',
        'approved_by',
        'approved_at',
        'approver_remarks',
        'return_reason',
        'returned_by',
        'returned_at',
        'metadata',
        // New approval enhancement columns
        'approval_status',
        'final_rating_override',
        'performance_level',
        'rating_override_justification',
        // Planning / PMT checkpoints
        'planning_reviewer_id',
        'planning_reviewed_at',
        'planning_remarks',
        'pmt_recommender_id',
        'pmt_recommended_at',
        'pmt_remarks',
        // Compliance + override
        'return_source',
        'hrmo_override',
        'hrmo_override_reason',
    ];

    protected $casts = [
        'overall_rating' => 'decimal:2',
        'final_rating_override' => 'decimal:2',
        'metadata' => 'array',
        'file_attachments' => 'array',
        'is_archived' => 'boolean',
        'hrmo_override' => 'boolean',
        // Current database structure dates
        'commitment_date' => 'date',
        'accomplishment_date' => 'date',
        'evaluation_date' => 'date',
        'final_approval_date' => 'date',
        'archived_at' => 'datetime',
        // Expected structure dates - for future compatibility
        'committed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'assessed_at' => 'datetime',
        'approved_at' => 'datetime',
        'returned_at' => 'datetime',
        'planning_reviewed_at' => 'datetime',
        'pmt_recommended_at' => 'datetime',
    ];

    /**
     * Workflow states
     */
    const STATE_DRAFT = 'draft';
    const STATE_PLANNING_REVIEW = 'planning_review';
    const STATE_PMT_REVIEW = 'pmt_review';
    const STATE_COMMITTED = 'committed';
    const STATE_IN_PROGRESS = 'in_progress';
    const STATE_EVALUATION = 'evaluation';
    const STATE_FINAL_APPROVAL = 'final_approval';
    const STATE_RETURNED = 'returned';

    
    /**
     * Get the performance evaluation
     */
    public function performanceEvaluation(): BelongsTo
    {
        return $this->belongsTo(PerformanceEvaluation::class);
    }

    /**
     * Get the office
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the performance period
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PerformancePeriod::class);
    }

    /**
     * Get the user who committed the workflow
     */
    public function committedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by');
    }

    /**
     * Get the planning reviewer
     */
    public function planningReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planning_reviewer_id');
    }

    /**
     * Get the PMT recommender
     */
    public function pmtRecommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pmt_recommender_id');
    }

    /**
     * Get the user who submitted the workflow
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the user who assessed the workflow
     */
    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Get the user who approved the workflow
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who returned the workflow
     */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * Get the department head (current database structure)
     */
    public function departmentHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_head_id');
    }

    /**
     * Get the assessor (current database structure)
     */
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    /**
     * Get the final approver (current database structure)
     */
    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approver_id');
    }

    /**
     * Get the success indicators ratings for this workflow
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(PerformanceRating::class, 'opcr_workflow_id');
    }

    /**
     * Get performance targets for this workflow
     */
    public function targets(): HasMany
    {
        return $this->hasMany(PerformanceTarget::class, 'opcr_workflow_id');
    }

    /**
     * Get the IPCR records generated from this OPCR workflow
     */
    public function ipcrs(): HasMany
    {
        return $this->hasMany(Ipcr::class, 'opcr_workflow_id');
    }

    /**
     * Mappings to individual IPCR items during cascading
     */
    public function ipcrMappings(): HasMany
    {
        return $this->hasMany(OpcrIpcrMapping::class, 'opcr_workflow_id');
    }

    /**
     * Scope to get workflows by state
     */
    public function scopeByState($query, string $state)
    {
        return $query->where('workflow_state', $state);
    }

    /**
     * Get documents for this workflow
     */
    public function documents(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'opcr_workflow_id');
    }

    /**
     * Scope to get active workflows (not in final or returned states)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('workflow_state', [
            self::STATE_DRAFT,
            self::STATE_PLANNING_REVIEW,
            self::STATE_PMT_REVIEW,
            self::STATE_COMMITTED,
            self::STATE_IN_PROGRESS,
            self::STATE_EVALUATION,
        ]);
    }

    /**
     * Scope to get completed workflows
     */
    public function scopeCompleted($query)
    {
        return $query->where('workflow_state', self::STATE_FINAL_APPROVAL);
    }

    /**
     * Scope to get workflows for specific office
     */
    public function scopeForOffice($query, $officeId)
    {
        return $query->where('office_id', $officeId);
    }

    /**
     * Scope to get workflows for specific period
     */
    public function scopeForPeriod($query, $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /**
     * Scope to get workflows committed by user
     */
    public function scopeCommittedBy($query, $userId)
    {
        return $query->where('committed_by', $userId);
    }

    /**
     * Scope to get workflows assessed by user
     */
    public function scopeAssessedBy($query, $userId)
    {
        return $query->where('assessed_by', $userId);
    }

    /**
     * Scope to get workflows approved by user
     */
    public function scopeApprovedBy($query, $userId)
    {
        return $query->where('approved_by', $userId);
    }

    /**
     * Get workflow state display name
     */
    public function getStateDisplayNameAttribute(): string
    {
        return match ($this->workflow_state) {
            self::STATE_DRAFT => 'Draft',
            self::STATE_PLANNING_REVIEW => 'Planning Review',
            self::STATE_PMT_REVIEW => 'PMT Review',
            self::STATE_COMMITTED => 'Committed',
            self::STATE_IN_PROGRESS => 'In Progress',
            self::STATE_EVALUATION => 'Under Evaluation',
            self::STATE_FINAL_APPROVAL => 'Approved',
            self::STATE_RETURNED => 'Returned for Revision',
            default => 'Unknown',
        };
    }

    /**
     * Get workflow state color
     */
    public function getStateColorAttribute(): string
    {
        return match ($this->workflow_state) {
            self::STATE_DRAFT => 'gray',
            self::STATE_PLANNING_REVIEW => 'cyan',
            self::STATE_PMT_REVIEW => 'teal',
            self::STATE_COMMITTED => 'blue',
            self::STATE_IN_PROGRESS => 'yellow',
            self::STATE_EVALUATION => 'orange',
            self::STATE_FINAL_APPROVAL => 'green',
            self::STATE_RETURNED => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if workflow can be edited
     */
    public function canBeEdited(): bool
    {
        return $this->workflow_state === self::STATE_DRAFT;
    }

    /**
     * Check if workflow can be committed
     */
    public function canBeCommitted(): bool
    {
        return in_array($this->workflow_state, [self::STATE_DRAFT, self::STATE_RETURNED]);
    }

    /**
     * Check if workflow can be submitted
     */
    public function canBeSubmitted(): bool
    {
        return in_array($this->workflow_state, [
            self::STATE_COMMITTED,
            self::STATE_RETURNED,
        ]);
    }

    /**
     * Check if workflow can be assessed
     */
    public function canBeAssessed(): bool
    {
        return $this->workflow_state === self::STATE_IN_PROGRESS;
    }

    /**
     * Check if workflow can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->workflow_state === self::STATE_EVALUATION;
    }

    /**
     * Check if workflow can be returned
     */
    public function canBeReturned(): bool
    {
        return in_array($this->workflow_state, [
            self::STATE_PLANNING_REVIEW,
            self::STATE_PMT_REVIEW,
            self::STATE_COMMITTED,
            self::STATE_IN_PROGRESS,
            self::STATE_EVALUATION,
        ]);
    }

    /**
     * Check if workflow is completed
     */
    public function isCompleted(): bool
    {
        return $this->workflow_state === self::STATE_FINAL_APPROVAL;
    }

    /**
     * Check if workflow is active (not completed)
     */
    public function isActive(): bool
    {
        return in_array($this->workflow_state, [
            self::STATE_DRAFT,
            self::STATE_PLANNING_REVIEW,
            self::STATE_PMT_REVIEW,
            self::STATE_COMMITTED,
            self::STATE_IN_PROGRESS,
            self::STATE_EVALUATION,
        ]);
    }

    /**
     * Get allowed state transitions
     */
    public function getAllowedTransitions(): array
    {
        return match ($this->workflow_state) {
            self::STATE_DRAFT => [self::STATE_PLANNING_REVIEW, self::STATE_RETURNED],
            self::STATE_PLANNING_REVIEW => [self::STATE_PMT_REVIEW, self::STATE_RETURNED],
            self::STATE_PMT_REVIEW => [self::STATE_COMMITTED, self::STATE_RETURNED],
            self::STATE_COMMITTED => [self::STATE_IN_PROGRESS, self::STATE_RETURNED],
            self::STATE_IN_PROGRESS => [self::STATE_EVALUATION, self::STATE_RETURNED],
            self::STATE_EVALUATION => [self::STATE_FINAL_APPROVAL, self::STATE_RETURNED],
            self::STATE_RETURNED => [self::STATE_PLANNING_REVIEW],
            self::STATE_FINAL_APPROVAL => [], // Terminal state
            default => [],
        };
    }

    /**
     * Get workflow timeline
     */
    public function getTimelineAttribute(): array
    {
        $timeline = [];

        if ($this->created_at) {
            $timeline[] = [
                'event' => 'Created',
                'description' => 'OPCR workflow created',
                'user' => null,
                'timestamp' => $this->created_at,
                'type' => 'creation',
            ];
        }

        if ($this->committed_at && $this->committedBy) {
            $timeline[] = [
                'event' => 'Submitted',
                'description' => 'OPCR submitted by Department Head to Planning',
                'user' => $this->committedBy,
                'timestamp' => $this->committed_at,
                'type' => 'commitment',
            ];
        }

        if ($this->planning_reviewed_at && $this->planningReviewer) {
            $timeline[] = [
                'event' => 'Planning Review',
                'description' => 'Planning Office review completed',
                'user' => $this->planningReviewer,
                'timestamp' => $this->planning_reviewed_at,
                'type' => 'planning_review',
            ];
        }

        if ($this->pmt_recommended_at && $this->pmtRecommender) {
            $timeline[] = [
                'event' => 'PMT Recommendation',
                'description' => 'PMT endorsed the OPCR',
                'user' => $this->pmtRecommender,
                'timestamp' => $this->pmt_recommended_at,
                'type' => 'pmt_recommendation',
            ];
        }

        if ($this->submitted_at && $this->submittedBy) {
            $timeline[] = [
                'event' => 'Submitted',
                'description' => 'OPCR submitted for evaluation',
                'user' => $this->submittedBy,
                'timestamp' => $this->submitted_at,
                'type' => 'submission',
            ];
        }

        if ($this->assessed_at && $this->assessedBy) {
            $timeline[] = [
                'event' => 'Assessed',
                'description' => 'OPCR assessed by Assessor',
                'user' => $this->assessedBy,
                'timestamp' => $this->assessed_at,
                'type' => 'assessment',
            ];
        }

        if ($this->approved_at && $this->approvedBy) {
            $timeline[] = [
                'event' => 'Approved',
                'description' => 'OPCR finally approved',
                'user' => $this->approvedBy,
                'timestamp' => $this->approved_at,
                'type' => 'approval',
            ];
        }

        if ($this->returned_at && $this->returnedBy) {
            $timeline[] = [
                'event' => 'Returned',
                'description' => 'OPCR returned for revision by ' . ($this->return_source ?? 'approver') . ': ' . $this->return_reason,
                'user' => $this->returnedBy,
                'timestamp' => $this->returned_at,
                'type' => 'return',
            ];
        }

        return collect($timeline)->sortBy('timestamp')->values()->toArray();
    }

    /**
     * Get current step information
     */
    public function getCurrentStepAttribute(): array
    {
        return match ($this->workflow_state) {
            self::STATE_DRAFT => [
                'name' => 'Draft',
                'description' => 'Department Head is preparing the OPCR',
                'actions' => ['edit', 'commit'],
                'responsible_role' => 'Department Head',
            ],
            self::STATE_PLANNING_REVIEW => [
                'name' => 'Planning Review',
                'description' => 'Planning Office validating alignment with LGU plans',
                'actions' => ['approve', 'return'],
                'responsible_role' => 'Planning Reviewer',
            ],
            self::STATE_PMT_REVIEW => [
                'name' => 'PMT Review',
                'description' => 'PMT calibrating and recommending approval',
                'actions' => ['approve', 'return'],
                'responsible_role' => 'PMT',
            ],
            self::STATE_COMMITTED => [
                'name' => 'Committed',
                'description' => 'OPCR targets have been committed',
                'actions' => ['submit', 'return'],
                'responsible_role' => 'Department Head',
            ],
            self::STATE_IN_PROGRESS => [
                'name' => 'In Progress',
                'description' => 'OPCR is under evaluation by Assessor',
                'actions' => ['assess', 'return'],
                'responsible_role' => 'Assessor',
            ],
            self::STATE_EVALUATION => [
                'name' => 'Evaluation',
                'description' => 'OPCR is pending final approval',
                'actions' => ['approve', 'return'],
                'responsible_role' => 'Final Approver',
            ],
            self::STATE_FINAL_APPROVAL => [
                'name' => 'Approved',
                'description' => 'OPCR has been finally approved',
                'actions' => ['view', 'export'],
                'responsible_role' => null,
            ],
            self::STATE_RETURNED => [
                'name' => 'Returned',
                'description' => 'OPCR returned for revision: ' . $this->return_reason,
                'actions' => ['edit', 'commit'],
                'responsible_role' => 'Department Head',
            ],
            default => [
                'name' => 'Unknown',
                'description' => 'Unknown workflow state',
                'actions' => [],
                'responsible_role' => null,
            ],
        };
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): int
    {
        $states = [
            self::STATE_DRAFT => 0,
            self::STATE_PLANNING_REVIEW => 15,
            self::STATE_PMT_REVIEW => 30,
            self::STATE_COMMITTED => 45,
            self::STATE_IN_PROGRESS => 60,
            self::STATE_EVALUATION => 80,
            self::STATE_FINAL_APPROVAL => 100,
            self::STATE_RETURNED => 15, // Reset near planning when returned
        ];

        return $states[$this->workflow_state] ?? 0;
    }

    /**
     * Get workflow statistics
     */
    public function getStatisticsAttribute(): array
    {
        $successIndicators = $this->office->activeMajorFinalOutputs()
            ->with('activeSuccessIndicators')
            ->get()
            ->pluck('activeSuccessIndicators')
            ->flatten();

        $ratedIndicators = $successIndicators->whereNotNull('average_rating');

        return [
            'total_success_indicators' => $successIndicators->count(),
            'rated_success_indicators' => $ratedIndicators->count(),
            'rating_completion_percentage' => $successIndicators->count() > 0
                ? round(($ratedIndicators->count() / $successIndicators->count()) * 100, 2)
                : 0,
            'average_rating' => $ratedIndicators->isNotEmpty()
                ? round($ratedIndicators->avg('average_rating'), 2)
                : null,
            'overall_rating' => $this->overall_rating,
            'workflow_completion_percentage' => $this->completion_percentage,
        ];
    }

    /**
     * Archive the workflow
     */
    public function archive(): bool
    {
        return $this->update([
            'metadata' => array_merge($this->metadata ?? [], [
                'archived_at' => now()->toISOString(),
                'archived_by' => Auth::id(),
                'archive_version' => '1.0',
            ]),
        ]);
    }

    /**
     * Get all available workflow states
     */
    public static function getAvailableStates(): array
    {
        return [
            self::STATE_DRAFT => 'Draft',
            self::STATE_PLANNING_REVIEW => 'Planning Review',
            self::STATE_PMT_REVIEW => 'PMT Review',
            self::STATE_COMMITTED => 'Committed',
            self::STATE_IN_PROGRESS => 'In Progress',
            self::STATE_EVALUATION => 'Under Evaluation',
            self::STATE_FINAL_APPROVAL => 'Approved',
            self::STATE_RETURNED => 'Returned for Revision',
        ];
    }

    /**
     * Create workflow with automatic validation
     */
    public static function createWorkflow(array $data): self
    {
        // Validate required fields
        if (empty($data['office_id'])) {
            throw new \InvalidArgumentException('Office ID is required');
        }

        if (empty($data['period_id'])) {
            throw new \InvalidArgumentException('Period ID is required');
        }

        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Title is required');
        }

        return self::create(array_merge($data, [
            'workflow_state' => self::STATE_DRAFT,
        ]));
    }

    /**
     * Get validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'office_id' => 'required|exists:offices,id',
            'period_id' => 'required|exists:performance_periods,id',
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'justification' => 'nullable|string',
            'return_reason' => 'required_if:workflow_state,returned|string',
            'overall_rating' => 'nullable|numeric|min:1|max:5',
        ];
    }
}
