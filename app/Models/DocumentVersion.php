<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DocumentVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'original_document_id',
        'parent_version_id',
        'version_number',
        'is_current_version',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_hash',
        'checksum',
        'change_reason',
        'version_notes',
        'comparison_metadata',
        'uploaded_by',
        'uploaded_at',
        'approved_by',
        'approved_at',
        'approval_status',
        'approval_notes',
        'can_rollback',
        'rollback_until',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'rollback_until' => 'datetime',
        'is_current_version' => 'boolean',
        'can_rollback' => 'boolean',
        'comparison_metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function originalDocument(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocument::class, 'original_document_id');
    }

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'parent_version_id');
    }

    public function childVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'parent_version_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current_version', true);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeRollbackable(Builder $query): Builder
    {
        return $query->where('can_rollback', true)
                    ->where(function ($q) {
                        $q->whereNull('rollback_until')
                          ->orWhere('rollback_until', '>', now());
                    });
    }

    public function scopeForDocument(Builder $query, int $documentId): Builder
    {
        return $query->where('original_document_id', $documentId);
    }

    public function scopeLatestVersions(Builder $query): Builder
    {
        return $query->whereIn('id', function ($subquery) {
            $subquery->selectRaw('MAX(id)')
                     ->from('document_versions')
                     ->groupBy('original_document_id');
        });
    }

    /**
     * Helper Methods
     */

    /**
     * Get the next version number for a document
     */
    public static function getNextVersionNumber(int $documentId): int
    {
        return static::forDocument($documentId)->max('version_number') + 1;
    }

    /**
     * Set this version as the current version
     */
    public function setAsCurrent(): bool
    {
        // First, unset all other versions as current for this document
        static::forDocument($this->original_document_id)
              ->update(['is_current_version' => false]);

        // Set this version as current
        return $this->update(['is_current_version' => true]);
    }

    /**
     * Check if this version can be rolled back to
     */
    public function canRollback(): bool
    {
        if (!$this->can_rollback) {
            return false;
        }

        if ($this->rollback_until && $this->rollback_until->isPast()) {
            return false;
        }

        if ($this->is_current_version) {
            return false; // Can't rollback to current version
        }

        return $this->approval_status === 'approved';
    }

    /**
     * Rollback to this version
     */
    public function rollback(User $user, string $reason = null): bool
    {
        if (!$this->canRollback()) {
            return false;
        }

        \DB::transaction(function () use ($user, $reason) {
            // Create a new version based on this one
            $newVersion = $this->replicate();
            $newVersion->version_number = static::getNextVersionNumber($this->original_document_id);
            $newVersion->parent_version_id = $this->id;
            $newVersion->uploaded_by = $user->id;
            $newVersion->uploaded_at = now();
            $newVersion->change_reason = $reason ?? "Rolled back to version {$this->version_number}";
            $newVersion->approval_status = 'approved'; // Auto-approve rollbacks
            $newVersion->approved_by = $user->id;
            $newVersion->approved_at = now();
            $newVersion->save();

            // Set as current version
            $newVersion->setAsCurrent();

            // Update the original document with this version's file info
            $this->originalDocument->update([
                'file_name' => $this->file_name,
                'file_path' => $this->file_path,
                'file_size' => $this->file_size,
                'mime_type' => $this->mime_type,
            ]);
        });

        return true;
    }

    /**
     * Compare this version with another version
     */
    public function compareWith(DocumentVersion $otherVersion): array
    {
        $differences = [];

        // File comparison
        if ($this->file_hash !== $otherVersion->file_hash) {
            $differences['file_content'] = 'Files have different content';
        }

        if ($this->file_size !== $otherVersion->file_size) {
            $differences['file_size'] = [
                'this' => $this->file_size,
                'other' => $otherVersion->file_size,
                'difference' => $this->file_size - $otherVersion->file_size
            ];
        }

        if ($this->file_name !== $otherVersion->file_name) {
            $differences['file_name'] = [
                'this' => $this->file_name,
                'other' => $otherVersion->file_name
            ];
        }

        // Metadata comparison
        $differences['version_info'] = [
            'this_version' => $this->version_number,
            'other_version' => $otherVersion->version_number,
            'this_uploaded' => $this->uploaded_at,
            'other_uploaded' => $otherVersion->uploaded_at,
        ];

        return $differences;
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file exists in storage
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Get file URL
     */
    public function getFileUrl(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get version timeline for the document
     */
    public function getVersionTimeline(): \Illuminate\Database\Eloquent\Collection
    {
        return static::forDocument($this->original_document_id)
                    ->with(['uploader', 'approver'])
                    ->orderBy('version_number')
                    ->get();
    }

    /**
     * Calculate storage space used by all versions of this document
     */
    public static function getStorageUsageForDocument(int $documentId): int
    {
        return static::forDocument($documentId)->sum('file_size');
    }

    /**
     * Clean up old versions based on retention policy
     */
    public static function cleanupOldVersions(int $documentId, int $keepVersions = 10): int
    {
        $versionsToDelete = static::forDocument($documentId)
                                 ->where('is_current_version', false)
                                 ->where('approval_status', '!=', 'pending')
                                 ->orderBy('version_number', 'desc')
                                 ->skip($keepVersions)
                                 ->pluck('id');

        if ($versionsToDelete->isEmpty()) {
            return 0;
        }

        // Soft delete old versions
        return static::whereIn('id', $versionsToDelete)->delete();
    }

    /**
     * Approve this version
     */
    public function approve(User $approver, string $notes = null): bool
    {
        return $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Reject this version
     */
    public function reject(User $approver, string $notes): bool
    {
        return $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }
}