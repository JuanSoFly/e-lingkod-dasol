<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EmployeeTraining extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'training_program_id',
        'training_requirement_id',
        'enrollment_date',
        'enrollment_status',
        'enrollment_reference',
        'enrollment_notes',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'venue',
        'batch_number',
        'cohort_name',
        'completion_status',
        'attendance_percentage',
        'hours_attended',
        'sessions_attended',
        'total_sessions',
        'grade',
        'pre_assessment_score',
        'post_assessment_score',
        'practical_exam_score',
        'final_score',
        'passing_status',
        'certificate_number',
        'certificate_date',
        'certificate_expiry',
        'certificate_file_path',
        'certificate_verified',
        'verified_by',
        'verified_at',
        'trainer_name',
        'trainer_credentials',
        'delivery_mode',
        'platform_used',
        'training_cost',
        'cost_center',
        'funding_source',
        'allowance_received',
        'transportation_allowance',
        'evaluation_rating',
        'feedback',
        'training_recommendations',
        'would_recommend',
        'improvement_suggestions',
        'learning_application_plan',
        'skills_acquired',
        'knowledge_gained',
        'knowledge_applied',
        'application_examples',
        'application_assessment_date',
        'mandatory_compliance',
        'compliance_deadline',
        'counts_towards_promotion',
        'promotion_points_earned',
        'cme_credits_earned',
        'cpe_credits_earned',
        'approved_by',
        'approved_at',
        'hr_officer',
        'hr_notes',
        'record_status',
        'online_progress_percentage',
        'modules_completed',
        'total_modules',
        'last_activity_at',
        'learning_analytics',
        // PDS Panel 7 specific fields
        'training_title',
        'inclusive_date_from',
        'inclusive_date_to',
        'number_of_hours',
        'type_of_ld',
        'conducted_sponsored_by',
        'attachment_id',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'certificate_date' => 'date',
        'certificate_expiry' => 'date',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'application_assessment_date' => 'date',
        'compliance_deadline' => 'date',
        'last_activity_at' => 'datetime',
        'attendance_percentage' => 'decimal:2',
        'grade' => 'decimal:2',
        'pre_assessment_score' => 'decimal:2',
        'post_assessment_score' => 'decimal:2',
        'practical_exam_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'training_cost' => 'decimal:2',
        'allowance_received' => 'decimal:2',
        'transportation_allowance' => 'decimal:2',
        'cme_credits_earned' => 'decimal:2',
        'cpe_credits_earned' => 'decimal:2',
        'online_progress_percentage' => 'decimal:2',
        'certificate_verified' => 'boolean',
        'would_recommend' => 'boolean',
        'knowledge_applied' => 'boolean',
        'mandatory_compliance' => 'boolean',
        'counts_towards_promotion' => 'boolean',
        'learning_analytics' => 'array',
        'deleted_at' => 'datetime',
        // PDS Panel 7 specific casts
        'inclusive_date_from' => 'date',
        'inclusive_date_to' => 'date',
        'number_of_hours' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function trainingRequirement(): BelongsTo
    {
        return $this->belongsTo(TrainingRequirement::class);
    }

    public function documentLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'source');
    }

    /**
     * Scopes
     */
    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByTrainingProgram(Builder $query, int $programId): Builder
    {
        return $query->where('training_program_id', $programId);
    }

    public function scopeByEnrollmentStatus(Builder $query, string $status): Builder
    {
        return $query->where('enrollment_status', $status);
    }

    public function scopeByCompletionStatus(Builder $query, string $status): Builder
    {
        return $query->where('completion_status', $status);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('completion_status', 'Completed');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('completion_status', 'In Progress');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('completion_status', 'Failed');
    }

    public function scopePassed(Builder $query): Builder
    {
        return $query->where('passing_status', 'Passed');
    }

    public function scopeMandatory(Builder $query): Builder
    {
        return $query->where('mandatory_compliance', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>', now()->toDateString());
    }

    public function scopeCurrentlyRunning(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('compliance_deadline')
            ->where('compliance_deadline', '<', now()->toDateString())
            ->whereNotIn('completion_status', ['Completed', 'Cancelled']);
    }

    public function scopeCertified(Builder $query): Builder
    {
        return $query->whereNotNull('certificate_number');
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        $cutoffDate = now()->addDays($days)->toDateString();
        return $query->whereNotNull('certificate_expiry')
            ->where('certificate_expiry', '<=', $cutoffDate)
            ->where('certificate_expiry', '>=', now()->toDateString());
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if training is currently in progress
     */
    public function isInProgress(): bool
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    /**
     * Check if training is completed
     */
    public function isCompleted(): bool
    {
        return $this->completion_status === 'Completed';
    }

    /**
     * Check if training is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->start_date > now()->toDateString();
    }

    /**
     * Check if employee passed the training
     */
    public function hasPassed(): bool
    {
        return $this->passing_status === 'Passed';
    }

    /**
     * Check if certificate is valid (not expired)
     */
    public function isCertificateValid(): bool
    {
        if (!$this->certificate_number || !$this->certificate_expiry) {
            return false;
        }

        return $this->certificate_expiry >= now()->toDateString();
    }

    /**
     * Check if certificate is expiring soon
     */
    public function isCertificateExpiringSoon(int $days = 30): bool
    {
        if (!$this->certificate_expiry) {
            return false;
        }

        $warningDate = now()->addDays($days);
        return $this->certificate_expiry <= $warningDate && $this->certificate_expiry >= now();
    }

    /**
     * Check if training is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->compliance_deadline) {
            return false;
        }

        return $this->compliance_deadline < now() && 
               !in_array($this->completion_status, ['Completed', 'Cancelled']);
    }

    /**
     * Calculate days until compliance deadline
     */
    public function getDaysUntilDeadline(): ?int
    {
        if (!$this->compliance_deadline) {
            return null;
        }

        return now()->diffInDays($this->compliance_deadline, false);
    }

    /**
     * Calculate training duration in days
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Calculate training progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->isCompleted()) {
            return 100.0;
        }

        if ($this->isUpcoming()) {
            return 0.0;
        }

        if ($this->online_progress_percentage !== null) {
            return $this->online_progress_percentage;
        }

        // For face-to-face training, calculate based on sessions or time elapsed
        if ($this->total_sessions && $this->sessions_attended) {
            return round(($this->sessions_attended / $this->total_sessions) * 100, 2);
        }

        // Calculate based on time elapsed
        if ($this->isInProgress()) {
            $totalDays = $this->getDurationInDays();
            $elapsedDays = $this->start_date->diffInDays(now()) + 1;
            return round(($elapsedDays / $totalDays) * 100, 2);
        }

        return 0.0;
    }

    /**
     * Get training performance summary
     */
    public function getPerformanceSummary(): array
    {
        return [
            'completion_status' => $this->completion_status,
            'passing_status' => $this->passing_status,
            'attendance_percentage' => $this->attendance_percentage,
            'final_score' => $this->final_score,
            'grade' => $this->grade,
            'is_certified' => !empty($this->certificate_number),
            'certificate_valid' => $this->isCertificateValid(),
            'progress_percentage' => $this->getProgressPercentage(),
            'credits_earned' => [
                'cme' => $this->cme_credits_earned,
                'cpe' => $this->cpe_credits_earned,
                'promotion_points' => $this->promotion_points_earned,
            ],
        ];
    }

    /**
     * Get training compliance status
     */
    public function getComplianceStatus(): array
    {
        $status = 'Compliant';
        $issues = [];

        // Check completion
        if (!$this->isCompleted()) {
            $status = 'Non-Compliant';
            $issues[] = 'Training not completed';
        }

        // Check passing status
        if ($this->passing_status === 'Failed') {
            $status = 'Non-Compliant';
            $issues[] = 'Did not pass training requirements';
        }

        // Check attendance
        if ($this->attendance_percentage < 100) {
            $attendanceRequirement = $this->trainingRequirement?->min_attendance_percentage ?? 100;
            if ($this->attendance_percentage < $attendanceRequirement) {
                $status = 'Non-Compliant';
                $issues[] = "Insufficient attendance ({$this->attendance_percentage}% < {$attendanceRequirement}%)";
            }
        }

        // Check certification requirement
        if ($this->trainingRequirement?->requires_certification && !$this->certificate_number) {
            $status = 'Non-Compliant';
            $issues[] = 'Certificate required but not obtained';
        }

        // Check certificate validity
        if ($this->certificate_number && !$this->isCertificateValid()) {
            $status = 'Expired';
            $issues[] = 'Certificate has expired';
        }

        // Check overdue status
        if ($this->isOverdue()) {
            $status = 'Overdue';
            $issues[] = 'Training completion is overdue';
        }

        return [
            'status' => $status,
            'is_compliant' => empty($issues) && $this->isCompleted() && $this->hasPassed(),
            'issues' => $issues,
            'deadline' => $this->compliance_deadline,
            'days_until_deadline' => $this->getDaysUntilDeadline(),
        ];
    }

    /**
     * Get related documents
     */
    public function getRelatedDocuments()
    {
        // Get documents linked to this training
        $linkedDocuments = $this->documentLinks()->with('target')->get()
            ->pluck('target')
            ->filter();

        // Get certificate file if exists
        $certificateFile = null;
        if ($this->certificate_file_path) {
            $certificateFile = (object) [
                'type' => 'certificate',
                'file_path' => $this->certificate_file_path,
                'name' => 'Training Certificate',
            ];
        }

        return [
            'linked_documents' => $linkedDocuments,
            'certificate_file' => $certificateFile,
            'total_count' => $linkedDocuments->count() + ($certificateFile ? 1 : 0),
        ];
    }

    /**
     * Calculate ROI (Return on Investment) for training
     */
    public function calculateROI(): array
    {
        $totalCost = $this->training_cost + $this->allowance_received + $this->transportation_allowance;

        // This is a simplified ROI calculation
        // In practice, you would need to define how to measure training benefits
        $estimatedBenefits = 0;

        if ($this->isCompleted() && $this->hasPassed()) {
            // Base benefit on performance improvement, promotion points, etc.
            $performanceImprovement = $this->post_assessment_score - $this->pre_assessment_score;
            $estimatedBenefits = $performanceImprovement * 1000; // Arbitrary multiplier

            if ($this->promotion_points_earned) {
                $estimatedBenefits += $this->promotion_points_earned * 5000; // Arbitrary value per point
            }
        }

        $roi = $totalCost > 0 ? (($estimatedBenefits - $totalCost) / $totalCost) * 100 : 0;

        return [
            'total_cost' => $totalCost,
            'estimated_benefits' => $estimatedBenefits,
            'roi_percentage' => round($roi, 2),
            'cost_breakdown' => [
                'training_cost' => $this->training_cost,
                'allowances' => $this->allowance_received,
                'transportation' => $this->transportation_allowance,
            ],
        ];
    }

    /**
     * Get training timeline
     */
    public function getTrainingTimeline(): array
    {
        $timeline = [];

        // Enrollment
        $timeline[] = [
            'date' => $this->enrollment_date,
            'event' => 'Enrolled',
            'status' => $this->enrollment_status,
            'description' => 'Employee enrolled in training program',
        ];

        // Training start
        if ($this->start_date) {
            $timeline[] = [
                'date' => $this->start_date,
                'event' => 'Training Started',
                'status' => $this->completion_status,
                'description' => 'Training program commenced',
            ];
        }

        // Assessments
        if ($this->pre_assessment_score) {
            $timeline[] = [
                'date' => $this->start_date, // Assuming pre-assessment on start date
                'event' => 'Pre-Assessment',
                'status' => 'Completed',
                'description' => "Score: {$this->pre_assessment_score}",
            ];
        }

        if ($this->post_assessment_score) {
            $timeline[] = [
                'date' => $this->end_date, // Assuming post-assessment on end date
                'event' => 'Post-Assessment',
                'status' => 'Completed',
                'description' => "Score: {$this->post_assessment_score}",
            ];
        }

        // Training end
        if ($this->end_date) {
            $timeline[] = [
                'date' => $this->end_date,
                'event' => 'Training Completed',
                'status' => $this->completion_status,
                'description' => "Final Status: {$this->completion_status}",
            ];
        }

        // Certification
        if ($this->certificate_date) {
            $timeline[] = [
                'date' => $this->certificate_date,
                'event' => 'Certificate Issued',
                'status' => 'Certified',
                'description' => "Certificate No: {$this->certificate_number}",
            ];
        }

        // Sort by date
        usort($timeline, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return $timeline;
    }

    /**
     * Update training progress
     */
    public function updateProgress(array $progressData): bool
    {
        $updatedFields = [];

        // Update attendance
        if (isset($progressData['sessions_attended'])) {
            $this->sessions_attended = $progressData['sessions_attended'];
            $updatedFields[] = 'sessions_attended';

            if ($this->total_sessions) {
                $this->attendance_percentage = ($this->sessions_attended / $this->total_sessions) * 100;
                $updatedFields[] = 'attendance_percentage';
            }
        }

        // Update online progress
        if (isset($progressData['online_progress_percentage'])) {
            $this->online_progress_percentage = $progressData['online_progress_percentage'];
            $this->last_activity_at = now();
            $updatedFields[] = 'online_progress_percentage';
            $updatedFields[] = 'last_activity_at';
        }

        // Update modules completed
        if (isset($progressData['modules_completed'])) {
            $this->modules_completed = $progressData['modules_completed'];
            $updatedFields[] = 'modules_completed';
        }

        // Update learning analytics
        if (isset($progressData['learning_analytics'])) {
            $this->learning_analytics = array_merge(
                $this->learning_analytics ?? [],
                $progressData['learning_analytics']
            );
            $updatedFields[] = 'learning_analytics';
        }

        // Auto-update completion status based on progress
        if ($this->getProgressPercentage() >= 100) {
            $this->completion_status = 'Completed';
            $updatedFields[] = 'completion_status';
        } elseif ($this->getProgressPercentage() > 0) {
            $this->completion_status = 'In Progress';
            $updatedFields[] = 'completion_status';
        }

        return $this->save();
    }

    /**
     * PDS Panel 7 specific accessor methods
     */
    
    /**
     * Get training title (PDS compatible)
     * Falls back to training program name if training_title is not set
     */
    public function getPDSTrainingTitleAttribute(): string
    {
        return $this->training_title ?? $this->trainingProgram?->name ?? 'N/A';
    }
    
    /**
     * Get PDS inclusive date from (formatted)
     */
    public function getPDSInclusiveDateFromAttribute(): ?string
    {
        if ($this->inclusive_date_from) {
            return $this->inclusive_date_from->format('m/d/Y');
        }
        
        // Fallback to start_date if inclusive_date_from is not set
        return $this->start_date ? $this->start_date->format('m/d/Y') : null;
    }
    
    /**
     * Get PDS inclusive date to (formatted)
     */
    public function getPDSInclusiveDateToAttribute(): ?string
    {
        if ($this->inclusive_date_to) {
            return $this->inclusive_date_to->format('m/d/Y');
        }
        
        // Fallback to end_date if inclusive_date_to is not set
        return $this->end_date ? $this->end_date->format('m/d/Y') : null;
    }
    
    /**
     * Get PDS number of hours
     * Falls back to calculated hours if not set
     */
    public function getPDSNumberOfHoursAttribute(): ?float
    {
        return $this->number_of_hours ?? $this->hours_attended ?? null;
    }
    
    /**
     * Get PDS type of L&D
     * Maps from delivery_mode if type_of_ld is not set
     */
    public function getPDSTypeOfLDAttribute(): ?string
    {
        if ($this->type_of_ld) {
            return $this->type_of_ld;
        }
        
        // Map from existing fields
        if ($this->delivery_mode) {
            $mapping = [
                'Management' => 'Managerial',
                'Supervision' => 'Supervisory',
                'Technical' => 'Technical',
                'Leadership' => 'Managerial',
                'Skills' => 'Technical',
            ];
            
            foreach ($mapping as $key => $value) {
                if (stripos($this->delivery_mode, $key) !== false) {
                    return $value;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get PDS conducted/sponsored by
     * Falls back to trainer_name if conducted_sponsored_by is not set
     */
    public function getPDSConductedSponsoredByAttribute(): ?string
    {
        return $this->conducted_sponsored_by ?? $this->trainer_name ?? null;
    }
    
    /**
     * Get PDS attachment ID
     * Falls back to certificate_file_path if attachment_id is not set
     */
    public function getPDSAttachmentIDAttribute(): ?string
    {
        return $this->attachment_id ?? $this->certificate_file_path ?? null;
    }
    
    /**
     * Get PDS formatted data for export
     */
    public function getPDSDataAttribute(): array
    {
        return [
            'training_title' => $this->pds_training_title,
            'inclusive_date_from' => $this->pds_inclusive_date_from,
            'inclusive_date_to' => $this->pds_inclusive_date_to,
            'number_of_hours' => $this->pds_number_of_hours,
            'type_of_ld' => $this->pds_type_of_ld,
            'conducted_sponsored_by' => $this->pds_conducted_sponsored_by,
            'attachment_id' => $this->pds_attachment_id,
        ];
    }
}