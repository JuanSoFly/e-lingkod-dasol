<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'document_type',
        'file_name',
        'file_path',
        'storage_disk',
        'uploaded_by',
        'uploaded_at',
        'file_size',
        'mime_type',
        'extracted_content',
        'content_indexed_at',
        'content_hash',
        'search_metadata',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'content_indexed_at' => 'datetime',
        'search_metadata' => 'array',
        'storage_disk' => 'string',
    ];

    protected $attributes = [
        'storage_disk' => 'local',
    ];

    protected $appends = [
        'display_file_name',
        'human_file_size',
        'file_exists',
        'is_previewable',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Document version tracking relationships
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'original_document_id');
    }

    public function currentVersion(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'original_document_id')
                   ->where('is_current_version', true);
    }

    public function approvedVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'original_document_id')
                   ->where('approval_status', 'approved');
    }

    /**
     * Document linking relationships
     */
    public function sourceLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'source');
    }

    public function targetLinks(): MorphMany
    {
        return $this->morphMany(DocumentLink::class, 'target');
    }

    /**
     * Get all links (both as source and target)
     */
    public function allLinks(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->sourceLinks->merge($this->targetLinks);
    }

    /**
     * Helper methods for version management
     */

    /**
     * Create a new version of this document
     */
    public function createVersion(array $versionData, User $uploader): DocumentVersion
    {
        $versionNumber = DocumentVersion::getNextVersionNumber($this->id);
        
        $version = $this->versions()->create(array_merge($versionData, [
            'version_number' => $versionNumber,
            'uploaded_by' => $uploader->id,
            'uploaded_at' => now(),
        ]));

        // If this is an approved version, make it current
        if ($version->approval_status === 'approved') {
            $version->setAsCurrent();
            
            // Update the main document with the new version's file info
            $this->update([
                'file_name' => $version->file_name,
                'file_path' => $version->file_path,
                'file_size' => $version->file_size,
                'mime_type' => $version->mime_type,
            ]);
        }

        return $version;
    }

    /**
     * Get the current version of this document
     */
    public function getCurrentVersion(): ?DocumentVersion
    {
        return $this->versions()->where('is_current_version', true)->first();
    }

    /**
     * Auto-link this document to related records
     */
    public function createAutoLinks(User $creator): array
    {
        return DocumentLink::createAutomaticLinks($this, $creator);
    }

    /**
     * Get linked records of a specific type
     */
    public function getLinkedRecords(string $linkType, bool $activeOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->sourceLinks()->forLinkType($linkType);
        
        if ($activeOnly) {
            $query->active();
        }

        return $query->with('target')->get()->pluck('target');
    }

    /**
     * Link this document to another record
     */
    public function linkTo(Model $target, string $linkType, User $creator, array $options = []): ?DocumentLink
    {
        return DocumentLink::createLink($this, $target, $linkType, $creator, $options);
    }

    /**
     * Accessors for UI helpers
     */
    public function getDisplayFileNameAttribute(): string
    {
        if (!empty($this->attributes['file_name'])) {
            return $this->attributes['file_name'];
        }

        return $this->file_path ? basename($this->file_path) : 'document';
    }

    public function getHumanFileSizeAttribute(): ?string
    {
        if (empty($this->file_size)) {
            return null;
        }

        $size = (int) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        $precision = $index === 0 ? 0 : 2;

        return number_format($size, $precision) . ' ' . $units[$index];
    }

    public function getFileExistsAttribute(): bool
    {
        $path = $this->currentStoragePath();

        if (!$path) {
            return false;
        }

        return $this->storageDisk()->exists($path);
    }

    public function getIsPreviewableAttribute(): bool
    {
        $mime = $this->mime_type;

        if (!$mime && $this->file_path) {
            $extension = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
                default => null,
            };
        }

        return $mime && (str_starts_with($mime, 'image/') || $mime === 'application/pdf');
    }

    public function resolvedStoragePath(): ?string
    {
        if (!$this->usesLocalDisk()) {
            return $this->file_path;
        }

        $path = $this->file_path ? ltrim($this->file_path, '/\\') : null;

        if (!$path) {
            return null;
        }

        $strippedPrivate = str_starts_with($path, 'private/')
            ? substr($path, strlen('private/'))
            : $path;

        $candidates = array_unique(array_filter([
            $path,
            ltrim($path, '/'),
            $strippedPrivate,
            "private/{$strippedPrivate}",
            "private/{$path}",
        ]));

        foreach ($candidates as $candidate) {
            if (Storage::disk('local')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function storageDiskName(): string
    {
        return $this->storage_disk ?: 'local';
    }

    public function storageDisk(): FilesystemAdapter
    {
        return Storage::disk($this->storageDiskName());
    }

    public function usesLocalDisk(): bool
    {
        return $this->storageDiskName() === 'local';
    }

    public function currentStoragePath(): ?string
    {
        return $this->usesLocalDisk()
            ? $this->resolvedStoragePath()
            : $this->file_path;
    }
}
