<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueuedExport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_id',
        'user_id',
        'employee_ids',
        'status',
        'file_path',
        'error_message',
        'progress',
        'estimated_completion',
        'completed_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'employee_ids' => 'array',
        'estimated_completion' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array
     */
    protected $attributes = [
        'progress' => 0,
        'status' => 'pending',
    ];

    /**
     * Get the user that requested the export.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the formatted status.
     */
    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get the estimated completion time in human readable format.
     */
    public function getHumanReadableEstimatedCompletionAttribute(): string
    {
        if (!$this->estimated_completion) {
            return 'Calculating...';
        }

        return $this->estimated_completion->diffForHumans();
    }

    /**
     * Get the processing duration.
     */
    public function getProcessingDurationAttribute(): ?string
    {
        if (!$this->completed_at) {
            return null;
        }

        $duration = $this->created_at->diff($this->completed_at);

        if ($duration->h > 0) {
            return $duration->format('%h hr %i min %s sec');
        } elseif ($duration->i > 0) {
            return $duration->format('%i min %s sec');
        } else {
            return $duration->format('%s sec');
        }
    }

    /**
     * Get the employee count.
     */
    public function getEmployeeCountAttribute(): int
    {
        return count($this->employee_ids ?? []);
    }

    /**
     * Check if the export is still processing.
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if the export is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the export failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the export is ready for download.
     */
    public function isReadyForDownload(): bool
    {
        return $this->isCompleted() && $this->file_path && \Storage::exists($this->file_path);
    }

    /**
     * Scope a query to only include pending exports.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include processing exports.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope a query to only include completed exports.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed exports.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include exports for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include exports older than a certain time.
     */
    public function scopeOlderThan($query, int $hours)
    {
        return $query->where('created_at', '<', now()->subHours($hours));
    }

    /**
     * Scope a query to only include exports that can be cleaned up.
     */
    public function scopeReadyForCleanup($query, int $hoursAfterCompletion = 24)
    {
        return $query->completed()
            ->where('completed_at', '<', now()->subHours($hoursAfterCompletion));
    }

    /**
     * Clean up old exports and their files.
     */
    public static function cleanupOldExports(int $hoursAfterCompletion = 24): int
    {
        $oldExports = static::readyForCleanup($hoursAfterCompletion)->get();
        $cleanedCount = 0;

        foreach ($oldExports as $export) {
            try {
                // Delete the file if it exists
                if ($export->file_path && \Storage::exists($export->file_path)) {
                    \Storage::delete($export->file_path);
                }

                // Delete the database record
                $export->delete();
                $cleanedCount++;

            } catch (\Exception $e) {
                \Log::warning('Failed to cleanup old export', [
                    'export_id' => $export->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $cleanedCount;
    }

    /**
     * Get statistics for queued exports.
     */
    public static function getQueueStatistics(): array
    {
        return [
            'pending' => static::pending()->count(),
            'processing' => static::processing()->count(),
            'completed' => static::completed()->count(),
            'failed' => static::failed()->count(),
            'total_today' => static::whereDate('created_at', today())->count(),
            'average_processing_time' => static::completed()
                ->whereNotNull('completed_at')
                ->get()
                ->avg(function ($export) {
                    return $export->created_at->diffInSeconds($export->completed_at);
                }),
        ];
    }
}