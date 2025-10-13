<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExportAuditLog extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'export_type',
        'file_size',
        'file_name',
        'export_format',
        'ip_address',
        'user_agent',
        'metadata',
        'user_role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that performed the export.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee that was exported (for single exports).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Configure the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'employee_id', 'export_type', 'file_size', 'file_name', 'export_format'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the description for the activity log.
     */
    public function getDescriptionForActivity(string $eventName): string
    {
        switch ($eventName) {
            case 'created':
                return "Exported {$this->export_type} PDS data in {$this->export_format} format";
            case 'updated':
                return "Updated export log entry #{$this->id}";
            case 'deleted':
                return "Deleted export log entry #{$this->id}";
            default:
                return "Performed {$eventName} on export log entry #{$this->id}";
        }
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the export duration (if available in metadata).
     */
    public function getProcessingTimeAttribute(): ?string
    {
        if (!isset($this->metadata['processing_time'])) {
            return null;
        }

        $seconds = $this->metadata['processing_time'];

        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $remainingSeconds > 0 ?
            "{$minutes} min {$remainingSeconds} sec" :
            "{$minutes} min";
    }

    /**
     * Get the employee count (for batch exports).
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->metadata['employee_count'] ?? 1;
    }

    /**
     * Check if this was a successful export.
     */
    public function isSuccessful(): bool
    {
        return !is_null($this->file_name) && $this->file_size > 0;
    }

    /**
     * Scope a query to only include successful exports.
     */
    public function scopeSuccessful($query)
    {
        return $query->whereNotNull('file_name')->where('file_size', '>', 0);
    }

    /**
     * Scope a query to only include failed exports.
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('file_name')->orWhere('file_size', '<=', 0);
        });
    }

    /**
     * Scope a query to only include exports from the last N days.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include exports of a specific type.
     */
    public function scopeExportType($query, string $exportType)
    {
        return $query->where('export_type', $exportType);
    }

    /**
     * Scope a query to only include exports of a specific format.
     */
    public function scopeFormat($query, string $format)
    {
        return $query->where('export_format', $format);
    }

    /**
     * Create an audit log entry for an export.
     */
    public static function createForExport(
        int $userId,
        ?int $employeeId,
        string $exportType,
        string $format,
        ?string $fileName = null,
        ?int $fileSize = null,
        array $metadata = []
    ): self {
        // Get the user and their role
        $user = \App\Models\User::find($userId);
        $userRole = $user ? $user->roles->pluck('name')->first() : 'unknown';

        return static::create([
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'export_type' => $exportType,
            'export_format' => $format,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata,
            'user_role' => $userRole,
        ]);
    }

    /**
     * Get export statistics for a user.
     */
    public static function getUserStatistics(int $userId, int $days = 30): array
    {
        $logs = static::where('user_id', $userId)
            ->recent($days)
            ->get();

        return [
            'total_exports' => $logs->count(),
            'successful_exports' => $logs->successful()->count(),
            'failed_exports' => $logs->failed()->count(),
            'total_file_size' => $logs->successful()->sum('file_size'),
            'single_exports' => $logs->exportType('single')->count(),
            'batch_exports' => $logs->exportType('batch')->count(),
            'employees_exported' => $logs->sum('employee_count'),
            'average_processing_time' => $logs->whereNotNull('metadata.processing_time')
                ->avg('metadata.processing_time'),
        ];
    }

    /**
     * Get overall export statistics.
     */
    public static function getOverallStatistics(int $days = 30): array
    {
        $logs = static::recent($days)->get();

        return [
            'total_exports' => $logs->count(),
            'unique_users' => $logs->distinct('user_id')->count('user_id'),
            'successful_exports' => $logs->successful()->count(),
            'failed_exports' => $logs->failed()->count(),
            'total_file_size' => $logs->successful()->sum('file_size'),
            'employees_exported' => $logs->sum('employee_count'),
            'most_active_user' => $logs->groupBy('user_id')
                ->map->count()
                ->sortDesc()
                ->first(),
            'export_types' => $logs->groupBy('export_type')
                ->map->count()
                ->toArray(),
            'export_formats' => $logs->groupBy('export_format')
                ->map->count()
                ->toArray(),
        ];
    }
}