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
     * DEPRECATED: Use DocumentLinkingService instead.
     */
    public static function createAutomaticLinks(Model $sourceModel, User $creator): array
    {
        // This method is kept for backward compatibility but should now delegate
        // to the Service via the Container or be removed entirely.
        // For now, we will return empty as the logic has moved.
        return [];
    }

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