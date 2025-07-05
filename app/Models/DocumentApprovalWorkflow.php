<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentApprovalWorkflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'document_type',
        'description',
        'steps',
        'default_deadline_days',
        'is_active',
        'requires_all_approvers',
        'escalation_rules',
    ];

    protected $casts = [
        'steps' => 'array',
        'escalation_rules' => 'array',
        'is_active' => 'boolean',
        'requires_all_approvers' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function requests(): HasMany
    {
        return $this->hasMany(DocumentApprovalRequest::class, 'workflow_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDocumentType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    // Business Logic Methods
    public function getStepByNumber($stepNumber): ?array
    {
        $steps = $this->steps ?? [];
        return collect($steps)->firstWhere('step_number', $stepNumber);
    }

    public function getTotalSteps(): int
    {
        return count($this->steps ?? []);
    }

    public function getApproversForStep($stepNumber): array
    {
        $step = $this->getStepByNumber($stepNumber);
        return $step['approvers'] ?? [];
    }

    public function canBeDeleted(): bool
    {
        return $this->requests()->count() === 0;
    }

    public function getDefaultDeadlineDate(): \Carbon\Carbon
    {
        return now()->addDays($this->default_deadline_days);
    }
}