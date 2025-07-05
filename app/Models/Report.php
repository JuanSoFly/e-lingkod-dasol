<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'report_number',
        'report_type',
        'title',
        'description',
        'report_year',
        'report_month',
        'period_start',
        'period_end',
        'department',
        'filters',
        'status',
        'file_format',
        'pdf_file_path',
        'excel_file_path',
        'file_size',
        'file_hash',
        'report_data',
        'summary_statistics',
        'total_records',
        'submitted_at',
        'submission_method',
        'submission_reference',
        'submission_notes',
        'acknowledged_at',
        'generated_by',
        'reviewed_by',
        'approved_by',
        'submitted_by',
        'generation_started_at',
        'generation_completed_at',
        'generation_duration_seconds',
        'generation_log',
        'error_message',
        'version',
        'parent_report_id',
        'is_current_version',
        'contains_confidential_data',
        'confidentiality_level',
        'retention_until',
        'legal_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'filters' => 'array',
        'report_data' => 'array',
        'summary_statistics' => 'array',
        'submitted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'generation_started_at' => 'datetime',
        'generation_completed_at' => 'datetime',
        'retention_until' => 'date',
        'contains_confidential_data' => 'boolean',
        'is_current_version' => 'boolean',
    ];

    /**
     * Report type constants matching CSCReportingService
     */
    public const TYPE_ACCESSION = 'accession';
    public const TYPE_SEPARATION = 'separation';
    public const TYPE_DIBAR = 'dibar';
    public const TYPE_HARASSMENT = 'harassment';
    public const TYPE_IGHR = 'ighr';

    /**
     * Report status constants
     */
    public const STATUS_GENERATING = 'generating';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * File format constants
     */
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_EXCEL = 'excel';
    public const FORMAT_BOTH = 'both';

    /**
     * Confidentiality level constants
     */
    public const CONFIDENTIALITY_PUBLIC = 'public';
    public const CONFIDENTIALITY_INTERNAL = 'internal';
    public const CONFIDENTIALITY_CONFIDENTIAL = 'confidential';
    public const CONFIDENTIALITY_RESTRICTED = 'restricted';

    /**
     * Submission method constants
     */
    public const SUBMISSION_ONLINE = 'online';
    public const SUBMISSION_EMAIL = 'email';
    public const SUBMISSION_PHYSICAL = 'physical';

    /**
     * Get the user who generated the report.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the user who reviewed the report.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the user who approved the report.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who submitted the report.
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the parent report (for versioning).
     */
    public function parentReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'parent_report_id');
    }

    /**
     * Get the child reports (versions).
     */
    public function childReports(): HasMany
    {
        return $this->hasMany(Report::class, 'parent_report_id');
    }

    /**
     * Scope a query to only include reports of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope a query to only include reports with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include reports for a specific period.
     */
    public function scopeForPeriod($query, int $year, ?int $month = null)
    {
        $query->where('report_year', $year);
        
        if ($month !== null) {
            $query->where('report_month', $month);
        }
        
        return $query;
    }

    /**
     * Scope a query to only include reports for a specific department.
     */
    public function scopeForDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope a query to only include current versions.
     */
    public function scopeCurrentVersions($query)
    {
        return $query->where('is_current_version', true);
    }

    /**
     * Scope a query to only include submitted reports.
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope a query to only include pending reports.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_GENERATING,
            self::STATUS_GENERATED,
            self::STATUS_REVIEWED,
        ]);
    }

    /**
     * Get the formatted report period.
     */
    public function getFormattedPeriodAttribute(): string
    {
        if ($this->report_month) {
            return Carbon::create($this->report_year, $this->report_month, 1)->format('F Y');
        }
        
        return (string) $this->report_year;
    }

    /**
     * Get the formatted report type.
     */
    public function getFormattedTypeAttribute(): string
    {
        return match ($this->report_type) {
            self::TYPE_ACCESSION => 'Monthly Accession Report',
            self::TYPE_SEPARATION => 'Monthly Separation Report',
            self::TYPE_DIBAR => 'Monthly DIBAR Report',
            self::TYPE_HARASSMENT => 'Monthly Sexual Harassment Cases Report',
            self::TYPE_IGHR => 'Annual IGHR Report',
            default => ucfirst($this->report_type) . ' Report',
        };
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_GENERATING => 'blue',
            self::STATUS_GENERATED => 'yellow',
            self::STATUS_REVIEWED => 'purple',
            self::STATUS_APPROVED => 'green',
            self::STATUS_SUBMITTED => 'indigo',
            self::STATUS_ACKNOWLEDGED => 'emerald',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if the report is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_GENERATING,
            self::STATUS_GENERATED,
            self::STATUS_FAILED,
        ]);
    }

    /**
     * Check if the report can be submitted.
     */
    public function canBeSubmitted(): bool
    {
        return in_array($this->status, [
            self::STATUS_GENERATED,
            self::STATUS_REVIEWED,
            self::STATUS_APPROVED,
        ]);
    }

    /**
     * Check if the report has files.
     */
    public function hasFiles(): bool
    {
        return !empty($this->pdf_file_path) || !empty($this->excel_file_path);
    }

    /**
     * Get the PDF file URL.
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_file_path) {
            return null;
        }
        
        return Storage::url($this->pdf_file_path);
    }

    /**
     * Get the Excel file URL.
     */
    public function getExcelUrlAttribute(): ?string
    {
        if (!$this->excel_file_path) {
            return null;
        }
        
        return Storage::url($this->excel_file_path);
    }

    /**
     * Generate a unique report number.
     */
    public static function generateReportNumber(string $type, int $year): string
    {
        $typeCode = match ($type) {
            self::TYPE_ACCESSION => 'ACC',
            self::TYPE_SEPARATION => 'SEP',
            self::TYPE_DIBAR => 'DIB',
            self::TYPE_HARASSMENT => 'HAR',
            self::TYPE_IGHR => 'IGH',
            default => 'RPT',
        };
        
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            $attempt++;
            
            // Get the current count and add attempt number to avoid duplicates
            $count = self::where('report_type', $type)
                ->where('report_year', $year)
                ->count() + $attempt;
            
            $reportNumber = sprintf('CSC-%s-%d-%03d', $typeCode, $year, $count);
            
            // Check if this number already exists
            $exists = self::where('report_number', $reportNumber)->exists();
            
            if (!$exists) {
                return $reportNumber;
            }
            
        } while ($attempt < $maxAttempts);
        
        // Fallback: use microseconds to ensure uniqueness
        $microtime = (int)(microtime(true) * 1000000);
        $uniqueId = substr($microtime, -6); // Last 6 digits
        return sprintf('CSC-%s-%d-%s', $typeCode, $year, $uniqueId);
    }

    /**
     * Mark the report as current version and update others.
     */
    public function markAsCurrentVersion(): void
    {
        // Mark all other versions as non-current
        if ($this->parent_report_id) {
            self::where('parent_report_id', $this->parent_report_id)
                ->update(['is_current_version' => false]);
            
            self::where('id', $this->parent_report_id)
                ->update(['is_current_version' => false]);
        } else {
            self::where('report_type', $this->report_type)
                ->where('report_year', $this->report_year)
                ->where('report_month', $this->report_month)
                ->where('department', $this->department)
                ->where('id', '!=', $this->id)
                ->update(['is_current_version' => false]);
        }
        
        // Mark this as current
        $this->update(['is_current_version' => true]);
    }

    /**
     * Calculate generation duration.
     */
    public function calculateGenerationDuration(): ?int
    {
        if (!$this->generation_started_at || !$this->generation_completed_at) {
            return null;
        }
        
        return $this->generation_completed_at->diffInSeconds($this->generation_started_at);
    }

    /**
     * Boot method to set up model events.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($report) {
            if (empty($report->report_number)) {
                $report->report_number = self::generateReportNumber(
                    $report->report_type,
                    $report->report_year
                );
            }
        });
        
        static::updating(function ($report) {
            if ($report->isDirty(['generation_started_at', 'generation_completed_at'])) {
                $report->generation_duration_seconds = $report->calculateGenerationDuration();
            }
        });
    }
}