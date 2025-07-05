<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class DocumentLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'link_type',
        'relationship_strength',
        'is_automatic',
        'is_bidirectional',
        'is_primary',
        'link_metadata',
        'link_reason',
        'confidence_score',
        'validation_rules',
        'status',
        'validated_at',
        'validated_by',
        'validation_notes',
        'matching_criteria',
        'requires_manual_approval',
        'last_verified_at',
        'verification_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_automatic' => 'boolean',
        'is_bidirectional' => 'boolean',
        'is_primary' => 'boolean',
        'requires_manual_approval' => 'boolean',
        'link_metadata' => 'array',
        'validation_rules' => 'array',
        'matching_criteria' => 'array',
        'validated_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'confidence_score' => 'decimal:4',
    ];

    /**
     * Available link types and their descriptions
     */
    public const LINK_TYPES = [
        'appointment_document' => 'Links documents to appointments',
        'training_certificate' => 'Links training certificates to training records',
        'leave_supporting_doc' => 'Links supporting documents to leave applications',
        'performance_evidence' => 'Links evidence documents to performance reviews',
        'education_credential' => 'Links educational documents to education records',
        'work_experience_proof' => 'Links documents to work experience records',
        'medical_certificate' => 'Links medical docs to leave/health records',
        'disciplinary_document' => 'Links documents to disciplinary actions',
        'promotion_document' => 'Links documents to promotion records',
        'separation_document' => 'Links documents to separation records',
        'custom_link' => 'For custom relationships',
    ];

    /**
     * Relationships
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending_validation');
    }

    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('is_automatic', true);
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('is_automatic', false);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeForLinkType(Builder $query, string $linkType): Builder
    {
        return $query->where('link_type', $linkType);
    }

    public function scopeHighConfidence(Builder $query, float $threshold = 0.8): Builder
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeForSource(Builder $query, string $type, int $id): Builder
    {
        return $query->where('source_type', $type)->where('source_id', $id);
    }

    public function scopeForTarget(Builder $query, string $type, int $id): Builder
    {
        return $query->where('target_type', $type)->where('target_id', $id);
    }

    public function scopeNeedsValidation(Builder $query): Builder
    {
        return $query->where('status', 'pending_validation')
                    ->orWhere(function ($q) {
                        $q->where('last_verified_at', '<', now()->subMonths(3))
                          ->orWhereNull('last_verified_at');
                    });
    }

    /**
     * Static Methods for Auto-linking
     */

    /**
     * Create automatic links based on patterns and criteria
     */
    public static function createAutomaticLinks(Model $sourceModel, User $creator): array
    {
        $linksCreated = [];
        $linkTypes = static::getApplicableLinkTypes($sourceModel);

        foreach ($linkTypes as $linkType) {
            $potentialTargets = static::findPotentialTargets($sourceModel, $linkType);
            
            foreach ($potentialTargets as $targetInfo) {
                $link = static::createLink(
                    $sourceModel,
                    $targetInfo['model'],
                    $linkType,
                    $creator,
                    [
                        'is_automatic' => true,
                        'confidence_score' => $targetInfo['confidence'],
                        'matching_criteria' => $targetInfo['criteria'],
                        'requires_manual_approval' => $targetInfo['confidence'] < 0.9,
                        'status' => $targetInfo['confidence'] >= 0.9 ? 'active' : 'pending_validation'
                    ]
                );

                if ($link) {
                    $linksCreated[] = $link;
                }
            }
        }

        return $linksCreated;
    }

    /**
     * Create a link between two models
     */
    public static function createLink(
        Model $source, 
        Model $target, 
        string $linkType, 
        User $creator, 
        array $options = []
    ): ?DocumentLink {
        // Check if link already exists
        $existing = static::where('source_type', get_class($source))
                         ->where('source_id', $source->id)
                         ->where('target_type', get_class($target))
                         ->where('target_id', $target->id)
                         ->where('link_type', $linkType)
                         ->first();

        if ($existing) {
            return $existing; // Return existing link
        }

        $linkData = array_merge([
            'source_type' => get_class($source),
            'source_id' => $source->id,
            'target_type' => get_class($target),
            'target_id' => $target->id,
            'link_type' => $linkType,
            'created_by' => $creator->id,
            'status' => 'active',
            'relationship_strength' => 'medium',
            'is_bidirectional' => true,
        ], $options);

        return static::create($linkData);
    }

    /**
     * Get applicable link types for a model
     */
    private static function getApplicableLinkTypes(Model $model): array
    {
        $modelClass = get_class($model);
        
        $applicableTypes = [
            EmployeeDocument::class => [
                'appointment_document',
                'training_certificate',
                'leave_supporting_doc',
                'performance_evidence',
                'education_credential',
                'work_experience_proof',
                'medical_certificate',
                'disciplinary_document',
                'promotion_document',
                'separation_document'
            ],
            LeaveApplication::class => ['leave_supporting_doc', 'medical_certificate'],
            PerformanceReview::class => ['performance_evidence'],
            EmployeeEducation::class => ['education_credential'],
            EmployeeWorkExperience::class => ['work_experience_proof'],
        ];

        return $applicableTypes[$modelClass] ?? ['custom_link'];
    }

    /**
     * Find potential targets for auto-linking
     */
    private static function findPotentialTargets(Model $source, string $linkType): array
    {
        $targets = [];

        switch ($linkType) {
            case 'leave_supporting_doc':
                if ($source instanceof EmployeeDocument) {
                    $targets = static::findLeaveApplicationTargets($source);
                }
                break;

            case 'training_certificate':
                if ($source instanceof EmployeeDocument) {
                    $targets = static::findTrainingTargets($source);
                }
                break;

            case 'performance_evidence':
                if ($source instanceof EmployeeDocument) {
                    $targets = static::findPerformanceTargets($source);
                }
                break;

            case 'education_credential':
                if ($source instanceof EmployeeDocument) {
                    $targets = static::findEducationTargets($source);
                }
                break;

            case 'medical_certificate':
                if ($source instanceof EmployeeDocument) {
                    $targets = static::findMedicalTargets($source);
                }
                break;
        }

        return $targets;
    }

    /**
     * Find leave application targets for document linking
     */
    private static function findLeaveApplicationTargets(EmployeeDocument $document): array
    {
        $targets = [];
        $confidence = 0.0;
        $criteria = [];

        // Check if document name suggests it's leave-related
        $leaveKeywords = ['leave', 'sick', 'vacation', 'emergency', 'maternity', 'paternity'];
        $documentName = strtolower($document->file_name);
        
        foreach ($leaveKeywords as $keyword) {
            if (strpos($documentName, $keyword) !== false) {
                $confidence += 0.3;
                $criteria[] = "filename_contains_{$keyword}";
            }
        }

        // Find leave applications around the document upload date
        $uploadDate = $document->uploaded_at ?? $document->created_at;
        $leaveApplications = LeaveApplication::where('employee_id', $document->employee_id)
            ->whereBetween('start_date', [
                $uploadDate->subDays(30),
                $uploadDate->addDays(30)
            ])
            ->get();

        foreach ($leaveApplications as $application) {
            $dateConfidence = 1.0 - (abs($uploadDate->diffInDays($application->start_date)) / 30);
            $totalConfidence = ($confidence + $dateConfidence) / 2;

            if ($totalConfidence >= 0.5) {
                $targets[] = [
                    'model' => $application,
                    'confidence' => $totalConfidence,
                    'criteria' => array_merge($criteria, ["date_proximity_{$dateConfidence}"])
                ];
            }
        }

        return $targets;
    }

    /**
     * Find training targets for document linking
     */
    private static function findTrainingTargets(EmployeeDocument $document): array
    {
        $targets = [];
        $trainingKeywords = ['training', 'certificate', 'seminar', 'workshop', 'course', 'certification'];
        $documentName = strtolower($document->file_name);
        
        foreach ($trainingKeywords as $keyword) {
            if (strpos($documentName, $keyword) !== false) {
                // This is likely a training document
                // In a full implementation, you would link to training records
                // For now, we'll return empty as training table doesn't exist yet
                break;
            }
        }

        return $targets;
    }

    /**
     * Find performance targets for document linking
     */
    private static function findPerformanceTargets(EmployeeDocument $document): array
    {
        $targets = [];
        $performanceKeywords = ['performance', 'evaluation', 'review', 'rating', 'assessment'];
        $documentName = strtolower($document->file_name);
        
        $confidence = 0.0;
        $criteria = [];

        foreach ($performanceKeywords as $keyword) {
            if (strpos($documentName, $keyword) !== false) {
                $confidence += 0.4;
                $criteria[] = "filename_contains_{$keyword}";
            }
        }

        if ($confidence > 0) {
            $uploadDate = $document->uploaded_at ?? $document->created_at;
            $reviews = PerformanceReview::where('employee_id', $document->employee_id)
                ->whereBetween('review_date', [
                    $uploadDate->subDays(60),
                    $uploadDate->addDays(60)
                ])
                ->get();

            foreach ($reviews as $review) {
                $dateConfidence = 1.0 - (abs($uploadDate->diffInDays($review->review_date)) / 60);
                $totalConfidence = ($confidence + $dateConfidence) / 2;

                if ($totalConfidence >= 0.5) {
                    $targets[] = [
                        'model' => $review,
                        'confidence' => $totalConfidence,
                        'criteria' => array_merge($criteria, ["date_proximity_{$dateConfidence}"])
                    ];
                }
            }
        }

        return $targets;
    }

    /**
     * Find education targets for document linking
     */
    private static function findEducationTargets(EmployeeDocument $document): array
    {
        $targets = [];
        $educationKeywords = ['diploma', 'degree', 'transcript', 'certificate', 'graduation', 'academic'];
        $documentName = strtolower($document->file_name);
        
        $confidence = 0.0;
        $criteria = [];

        foreach ($educationKeywords as $keyword) {
            if (strpos($documentName, $keyword) !== false) {
                $confidence += 0.3;
                $criteria[] = "filename_contains_{$keyword}";
            }
        }

        if ($confidence > 0) {
            $educationRecords = EmployeeEducation::where('employee_id', $document->employee_id)->get();

            foreach ($educationRecords as $education) {
                $targets[] = [
                    'model' => $education,
                    'confidence' => $confidence,
                    'criteria' => $criteria
                ];
            }
        }

        return $targets;
    }

    /**
     * Find medical targets for document linking
     */
    private static function findMedicalTargets(EmployeeDocument $document): array
    {
        $targets = [];
        $medicalKeywords = ['medical', 'certificate', 'sick', 'doctor', 'hospital', 'clinic', 'health'];
        $documentName = strtolower($document->file_name);
        
        $confidence = 0.0;
        $criteria = [];

        foreach ($medicalKeywords as $keyword) {
            if (strpos($documentName, $keyword) !== false) {
                $confidence += 0.3;
                $criteria[] = "filename_contains_{$keyword}";
            }
        }

        if ($confidence > 0.5) {
            // Find sick leave applications
            $uploadDate = $document->uploaded_at ?? $document->created_at;
            $sickLeaves = LeaveApplication::where('employee_id', $document->employee_id)
                ->whereHas('leaveType', function ($query) {
                    $query->where('name', 'like', '%sick%');
                })
                ->whereBetween('start_date', [
                    $uploadDate->subDays(15),
                    $uploadDate->addDays(15)
                ])
                ->get();

            foreach ($sickLeaves as $leave) {
                $dateConfidence = 1.0 - (abs($uploadDate->diffInDays($leave->start_date)) / 15);
                $totalConfidence = ($confidence + $dateConfidence) / 2;

                if ($totalConfidence >= 0.6) {
                    $targets[] = [
                        'model' => $leave,
                        'confidence' => $totalConfidence,
                        'criteria' => array_merge($criteria, ["medical_leave_proximity_{$dateConfidence}"])
                    ];
                }
            }
        }

        return $targets;
    }

    /**
     * Instance Methods
     */

    /**
     * Validate this link
     */
    public function validate(User $validator, bool $isValid, string $notes = null): bool
    {
        return $this->update([
            'status' => $isValid ? 'active' : 'broken',
            'validated_at' => now(),
            'validated_by' => $validator->id,
            'validation_notes' => $notes,
            'verification_count' => $this->verification_count + 1,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Check if this link is still valid
     */
    public function checkConsistency(): bool
    {
        // Check if both source and target still exist
        if (!$this->source || !$this->target) {
            $this->update(['status' => 'broken']);
            return false;
        }

        // Apply validation rules if they exist
        if ($this->validation_rules) {
            foreach ($this->validation_rules as $rule) {
                if (!$this->applyValidationRule($rule)) {
                    $this->update(['status' => 'broken']);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Apply a validation rule
     */
    private function applyValidationRule(array $rule): bool
    {
        // This is a placeholder for complex validation logic
        // Each rule could check different aspects of the relationship
        switch ($rule['type'] ?? '') {
            case 'date_range':
                return $this->validateDateRange($rule);
            case 'employee_match':
                return $this->validateEmployeeMatch($rule);
            default:
                return true;
        }
    }

    /**
     * Validate date range rule
     */
    private function validateDateRange(array $rule): bool
    {
        // Implementation depends on the specific rule structure
        return true;
    }

    /**
     * Validate employee match rule
     */
    private function validateEmployeeMatch(array $rule): bool
    {
        // Ensure both source and target belong to the same employee
        $sourceEmployeeId = $this->getEmployeeId($this->source);
        $targetEmployeeId = $this->getEmployeeId($this->target);

        return $sourceEmployeeId && $targetEmployeeId && $sourceEmployeeId === $targetEmployeeId;
    }

    /**
     * Get employee ID from a model
     */
    private function getEmployeeId(Model $model): ?int
    {
        if ($model instanceof Employee) {
            return $model->id;
        }

        if (isset($model->employee_id)) {
            return $model->employee_id;
        }

        return null;
    }

    /**
     * Get the bidirectional link (if it exists)
     */
    public function getBidirectionalLink(): ?DocumentLink
    {
        if (!$this->is_bidirectional) {
            return null;
        }

        return static::where('source_type', $this->target_type)
                    ->where('source_id', $this->target_id)
                    ->where('target_type', $this->source_type)
                    ->where('target_id', $this->source_id)
                    ->where('link_type', $this->link_type)
                    ->first();
    }

    /**
     * Create bidirectional link if needed
     */
    public function createBidirectionalLink(): ?DocumentLink
    {
        if (!$this->is_bidirectional || $this->getBidirectionalLink()) {
            return null;
        }

        return static::create([
            'source_type' => $this->target_type,
            'source_id' => $this->target_id,
            'target_type' => $this->source_type,
            'target_id' => $this->source_id,
            'link_type' => $this->link_type,
            'relationship_strength' => $this->relationship_strength,
            'is_automatic' => $this->is_automatic,
            'is_bidirectional' => true,
            'is_primary' => false, // Bidirectional links are not primary
            'link_metadata' => $this->link_metadata,
            'link_reason' => "Bidirectional link for #{$this->id}",
            'confidence_score' => $this->confidence_score,
            'status' => $this->status,
            'created_by' => $this->created_by,
        ]);
    }
}