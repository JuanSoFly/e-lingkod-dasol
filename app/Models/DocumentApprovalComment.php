<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentApprovalComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_id',
        'step_id',
        'user_id',
        'comment',
        'is_internal',
        'is_system_generated',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_internal' => 'boolean',
        'is_system_generated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function request(): BelongsTo
    {
        return $this->belongsTo(DocumentApprovalRequest::class, 'request_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(DocumentApprovalStep::class, 'step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeUserGenerated($query)
    {
        return $query->where('is_system_generated', false);
    }

    public function scopeSystemGenerated($query)
    {
        return $query->where('is_system_generated', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStep($query, $stepId)
    {
        return $query->where('step_id', $stepId);
    }

    // Accessors
    public function getCommentTypeAttribute()
    {
        if ($this->is_system_generated) {
            return 'System';
        }
        
        return $this->is_internal ? 'Internal' : 'Public';
    }

    public function getCanBeEditedAttribute()
    {
        return $this->user_id === auth()->id() && 
               !$this->is_system_generated &&
               $this->created_at->diffInMinutes(now()) <= 15; // 15 minutes edit window
    }

    public function getCanBeDeletedAttribute()
    {
        return $this->user_id === auth()->id() && 
               !$this->is_system_generated &&
               auth()->user()->hasRole(['Super Admin', 'HR Admin']);
    }

    // Business Logic Methods
    public function edit($newComment): bool
    {
        if (!$this->can_be_edited) {
            return false;
        }

        $this->comment = $newComment;
        $this->metadata = array_merge($this->metadata ?? [], [
            'edited_at' => now()->toISOString(),
            'original_comment' => $this->getOriginal('comment'),
        ]);

        return $this->save();
    }

    public function markAsRead($userId): void
    {
        $readers = $this->metadata['readers'] ?? [];
        if (!in_array($userId, $readers)) {
            $readers[] = $userId;
            $this->metadata = array_merge($this->metadata ?? [], ['readers' => $readers]);
            $this->save();
        }
    }

    public function isReadBy($userId): bool
    {
        $readers = $this->metadata['readers'] ?? [];
        return in_array($userId, $readers);
    }

    // Static methods for creating specific comment types
    public static function createSystemComment($requestId, $stepId, $comment, $metadata = []): self
    {
        return self::create([
            'request_id' => $requestId,
            'step_id' => $stepId,
            'user_id' => auth()->id(),
            'comment' => $comment,
            'is_internal' => true,
            'is_system_generated' => true,
            'metadata' => $metadata,
        ]);
    }

    public static function createUserComment($requestId, $stepId, $userId, $comment, $isInternal = false): self
    {
        return self::create([
            'request_id' => $requestId,
            'step_id' => $stepId,
            'user_id' => $userId,
            'comment' => $comment,
            'is_internal' => $isInternal,
            'is_system_generated' => false,
        ]);
    }

    public static function createApprovalComment($requestId, $stepId, $userId, $action, $comments = null): self
    {
        $comment = $action === 'approved' ? 
            "Request has been approved" : 
            "Request has been rejected";
            
        if ($comments) {
            $comment .= ": {$comments}";
        }

        return self::createSystemComment($requestId, $stepId, $comment, [
            'action' => $action,
            'user_comments' => $comments,
        ]);
    }
}