<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeChangeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'requested_by_user_id',
        'change_type',
        'field_name',
        'current_value',
        'requested_value',
        'justification',
        'status',
        'priority',
        'supporting_documents',
        'document_notes',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
        'implemented_by_user_id',
        'implemented_at',
        'implementation_notes',
        'additional_data',
        'requires_approval',
        'auto_implementable',
        'effective_date',
        'audit_trail',
        'validation_errors',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'implemented_at' => 'datetime',
        'effective_date' => 'date',
        'supporting_documents' => 'array',
        'additional_data' => 'array',
        'audit_trail' => 'array',
        'validation_errors' => 'array',
        'requires_approval' => 'boolean',
        'auto_implementable' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function implementedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'implemented_by_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeImplemented($query)
    {
        return $query->where('status', 'implemented');
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    public function scopeAutoImplementable($query)
    {
        return $query->where('auto_implementable', true);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeRequestedBy($query, $userId)
    {
        return $query->where('requested_by_user_id', $userId);
    }

    public function scopeByChangeType($query, $changeType)
    {
        return $query->where('change_type', $changeType);
    }

    public function scopeByField($query, $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }

    public function scopeEffectiveAfter($query, $date)
    {
        return $query->where('effective_date', '>=', $date);
    }

    public function scopeEffectiveBefore($query, $date)
    {
        return $query->where('effective_date', '<=', $date);
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'warning',
            'under_review' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'implemented' => 'success',
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getPriorityBadgeAttribute(): string
    {
        $badges = [
            'low' => 'success',
            'normal' => 'primary',
            'high' => 'warning',
            'urgent' => 'danger',
        ];

        return $badges[$this->priority] ?? 'primary';
    }

    public function getIsEffectiveAttribute(): bool
    {
        return $this->effective_date && $this->effective_date->isPast();
    }

    public function getDaysUntilEffectiveAttribute(): ?int
    {
        if (!$this->effective_date) {
            return null;
        }

        return now()->diffInDays($this->effective_date, false);
    }

    public function getFormattedFieldNameAttribute(): string
    {
        return str_replace('_', ' ', ucwords($this->field_name, '_'));
    }

    public function getFormattedChangeTypeAttribute(): string
    {
        return str_replace('_', ' ', ucwords($this->change_type, '_'));
    }

    // Helper methods
    public function canBeReviewed(): bool
    {
        return in_array($this->status, ['pending', 'under_review']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'under_review';
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending', 'under_review']);
    }

    public function canBeImplemented(): bool
    {
        return $this->status === 'approved' || 
               ($this->auto_implementable && $this->status === 'pending');
    }

    public function needsApproval(): bool
    {
        return $this->requires_approval;
    }

    public function hasSupportingDocuments(): bool
    {
        return !empty($this->supporting_documents);
    }

    public function isReadyForImplementation(): bool
    {
        return $this->status === 'approved' && 
               ($this->effective_date === null || $this->effective_date->isPast());
    }

    /**
     * Add entry to audit trail
     */
    public function addAuditEntry(string $action, ?string $notes = null, ?int $userId = null): void
    {
        $auditTrail = $this->audit_trail ?? [];
        
        $auditTrail[] = [
            'action' => $action,
            'notes' => $notes,
            'user_id' => $userId ?? auth()->id(),
            'user_name' => auth()->user()?->name,
            'timestamp' => now()->toISOString(),
            'old_status' => $this->getOriginal('status'),
            'new_status' => $this->status,
        ];
        
        $this->audit_trail = $auditTrail;
    }

    /**
     * Validate the change request
     */
    public function validateChange(): array
    {
        $errors = [];
        
        // Basic validations
        if (empty($this->requested_value)) {
            $errors[] = 'Requested value cannot be empty.';
        }
        
        if ($this->current_value === $this->requested_value) {
            $errors[] = 'Requested value is the same as current value.';
        }
        
        // Field-specific validations
        switch ($this->field_name) {
            case 'email':
                if (!filter_var($this->requested_value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format.';
                }
                break;
                
            case 'contact_number':
                if (!preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $this->requested_value)) {
                    $errors[] = 'Invalid contact number format.';
                }
                break;
                
            case 'birth_date':
                $date = \DateTime::createFromFormat('Y-m-d', $this->requested_value);
                if (!$date || $date->format('Y-m-d') !== $this->requested_value) {
                    $errors[] = 'Invalid birth date format. Use YYYY-MM-DD.';
                } elseif ($date > new \DateTime()) {
                    $errors[] = 'Birth date cannot be in the future.';
                }
                break;
        }
        
        $this->validation_errors = $errors;
        return $errors;
    }

    /**
     * Implement the change
     */
    public function implementChange(): bool
    {
        if (!$this->canBeImplemented()) {
            return false;
        }
        
        // Validate first
        $errors = $this->validateChange();
        if (!empty($errors)) {
            return false;
        }
        
        try {
            // Update the employee record
            $updateData = [$this->field_name => $this->requested_value];
            $this->employee->update($updateData);
            
            // Mark as implemented
            $this->update([
                'status' => 'implemented',
                'implemented_by_user_id' => auth()->id(),
                'implemented_at' => now(),
                'implementation_notes' => "Successfully updated {$this->field_name} from '{$this->current_value}' to '{$this->requested_value}'",
            ]);
            
            $this->addAuditEntry('implemented', 'Change successfully implemented');
            
            return true;
        } catch (\Exception $e) {
            $this->update([
                'validation_errors' => ['Implementation error: ' . $e->getMessage()],
            ]);
            
            return false;
        }
    }

    /**
     * Get available change types
     */
    public static function getChangeTypes(): array
    {
        return [
            'personal_info' => 'Personal Information',
            'contact_info' => 'Contact Information',
            'emergency_contact' => 'Emergency Contact',
            'address' => 'Address Information',
            'government_ids' => 'Government IDs',
            'benefits_info' => 'Benefits Information',
            'family_info' => 'Family Information',
            'educational_background' => 'Educational Background',
            'work_experience' => 'Work Experience',
            'other' => 'Other Changes',
        ];
    }

    /**
     * Get editable fields by change type
     */
    public static function getEditableFields(): array
    {
        return [
            'personal_info' => [
                'first_name' => 'First Name',
                'middle_name' => 'Middle Name',
                'last_name' => 'Last Name',
                'birth_date' => 'Birth Date',
                'civil_status' => 'Civil Status',
                'gender' => 'Gender',
                'citizenship' => 'Citizenship',
                'religion' => 'Religion',
                'height' => 'Height',
                'weight' => 'Weight',
                'blood_type' => 'Blood Type',
            ],
            'contact_info' => [
                'email' => 'Email Address',
                'contact_number' => 'Contact Number',
                'address' => 'Address',
                'place_of_birth' => 'Place of Birth',
            ],
            'emergency_contact' => [
                'emergency_contact_name' => 'Emergency Contact Name',
                'emergency_contact_relationship' => 'Emergency Contact Relationship',
                'emergency_contact_number' => 'Emergency Contact Number',
                'emergency_contact_address' => 'Emergency Contact Address',
            ],
            'government_ids' => [
                'tin_number' => 'TIN Number',
                'sss_number' => 'SSS Number',
                'pagibig_number' => 'Pag-IBIG Number',
                'philhealth_number' => 'PhilHealth Number',
                'gsis_number' => 'GSIS Number',
            ],
            'family_info' => [
                'spouse_name' => 'Spouse Name',
                'spouse_occupation' => 'Spouse Occupation',
            ],
        ];
    }

    /**
     * Get fields that require approval
     */
    public static function getFieldsRequiringApproval(): array
    {
        return [
            'first_name',
            'last_name',
            'birth_date',
            'gender',
            'citizenship',
            'tin_number',
            'sss_number',
            'pagibig_number',
            'philhealth_number',
            'gsis_number',
        ];
    }

    /**
     * Get auto-implementable fields
     */
    public static function getAutoImplementableFields(): array
    {
        return [
            'contact_number',
            'address',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_number',
            'emergency_contact_address',
            'email', // if it's not the primary login email
        ];
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->requested_by_user_id) {
                $model->requested_by_user_id = auth()->id();
            }
            
            // Set approval requirements
            $model->requires_approval = in_array($model->field_name, static::getFieldsRequiringApproval());
            $model->auto_implementable = in_array($model->field_name, static::getAutoImplementableFields());
        });

        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                $model->addAuditEntry('status_changed', "Status changed from {$model->getOriginal('status')} to {$model->status}");
            }
        });
    }
}
