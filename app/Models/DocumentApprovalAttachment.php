<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DocumentApprovalAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'version',
        'uploaded_by',
        'description',
        'is_current_version',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_current_version' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function request(): BelongsTo
    {
        return $this->belongsTo(DocumentApprovalRequest::class, 'request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopeCurrentVersion($query)
    {
        return $query->where('is_current_version', true);
    }

    public function scopeByFileType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    public function scopeByUploader($query, $uploaderId)
    {
        return $query->where('uploaded_by', $uploaderId);
    }

    // Accessors
    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDownloadUrlAttribute()
    {
        return route('document-approvals.attachments.download', $this->id);
    }

    public function getIsImageAttribute()
    {
        return in_array($this->file_type, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg']);
    }

    public function getIsPdfAttribute()
    {
        return $this->file_type === 'pdf';
    }

    public function getIsDocumentAttribute()
    {
        return in_array($this->file_type, ['doc', 'docx', 'txt', 'rtf']);
    }

    public function getIsSpreadsheetAttribute()
    {
        return in_array($this->file_type, ['xls', 'xlsx', 'csv']);
    }

    // Business Logic Methods
    public function canBeDownloaded(): bool
    {
        return Storage::disk('private')->exists($this->file_path);
    }

    public function canBeDeleted(): bool
    {
        return $this->uploaded_by === auth()->id() || 
               auth()->user()->hasRole(['Super Admin', 'HR Admin']);
    }

    public function getFileContents()
    {
        if (!$this->canBeDownloaded()) {
            return null;
        }

        return Storage::disk('private')->get($this->file_path);
    }

    public function deleteFile(): bool
    {
        if (!$this->canBeDeleted()) {
            return false;
        }

        // Delete physical file
        if (Storage::disk('private')->exists($this->file_path)) {
            Storage::disk('private')->delete($this->file_path);
        }

        // Soft delete the record
        return $this->delete();
    }

    public function createNewVersion($file, $uploadedBy, $description = null): self
    {
        // Mark current version as not current
        $this->is_current_version = false;
        $this->save();

        // Create new version
        return self::create([
            'request_id' => $this->request_id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store("document_approvals/{$this->request_id}", 'private'),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'version' => $this->version + 1,
            'uploaded_by' => $uploadedBy,
            'description' => $description,
            'is_current_version' => true,
        ]);
    }

    // Boot method to handle file cleanup
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            // Delete physical file when model is being deleted
            if (Storage::disk('private')->exists($attachment->file_path)) {
                Storage::disk('private')->delete($attachment->file_path);
            }
        });
    }
}