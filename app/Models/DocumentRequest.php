<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'requested_by_user_id',
        'document_type',
        'document_name',
        'purpose',
        'description',
        'status',
        'priority',
        'needed_by',
        'additional_data',
        'processed_by_user_id',
        'processed_at',
        'processing_notes',
        'rejection_reason',
        'delivery_method',
        'delivery_address',
        'delivery_contact',
        'delivered_at',
        'generated_file_path',
        'generated_file_name',
        'file_size',
        'expires_at',
        'audit_trail',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'needed_by' => 'date',
        'processed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'additional_data' => 'array',
        'audit_trail' => 'array',
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

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('needed_by', '<', now()->toDateString())
                    ->whereNotIn('status', ['completed', 'rejected']);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeRequestedBy($query, $userId)
    {
        return $query->where('requested_by_user_id', $userId);
    }

    public function scopeByType($query, $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'warning',
            'processing' => 'info',
            'ready' => 'success',
            'completed' => 'success',
            'rejected' => 'danger',
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

    public function getIsOverdueAttribute(): bool
    {
        return $this->needed_by && 
               $this->needed_by->isPast() && 
               !in_array($this->status, ['completed', 'rejected']);
    }

    public function getDaysUntilNeededAttribute(): ?int
    {
        if (!$this->needed_by) {
            return null;
        }

        return now()->diffInDays($this->needed_by, false);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Helper methods
    public function canBeProcessed(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'processing';
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'ready';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasFile(): bool
    {
        return !empty($this->generated_file_path) && 
               !empty($this->generated_file_name);
    }

    public function getFileUrl(): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }

        return asset('storage/' . $this->generated_file_path);
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
     * Get available document types
     */
    public static function getDocumentTypes(): array
    {
        return [
            'certificate_of_employment' => 'Certificate of Employment',
            'service_record' => 'Service Record',
            'certificate_of_compensation' => 'Certificate of Compensation',
            'clearance' => 'Clearance',
            'id_replacement' => 'ID Replacement',
            'payslip' => 'Payslip',
            'leave_balance_certificate' => 'Leave Balance Certificate',
            'performance_rating_certificate' => 'Performance Rating Certificate',
            'training_certificate' => 'Training Certificate',
            'promotion_order' => 'Promotion Order',
            'appointment_paper' => 'Appointment Paper',
            'resignation_acceptance' => 'Resignation Acceptance',
            'retirement_papers' => 'Retirement Papers',
            'tax_documents' => 'Tax Documents (2316, etc.)',
            'benefits_certificate' => 'Benefits Certificate',
            'disciplinary_records' => 'Disciplinary Records',
            'commendation_records' => 'Commendation Records',
            'other' => 'Other Documents',
        ];
    }

    /**
     * Get delivery methods
     */
    public static function getDeliveryMethods(): array
    {
        return [
            'pickup' => 'Pick-up at HR Office',
            'email' => 'Email Delivery',
            'courier' => 'Courier/Mail Delivery',
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
        });

        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                $model->addAuditEntry('status_changed', "Status changed from {$model->getOriginal('status')} to {$model->status}");
            }
        });
    }
}
