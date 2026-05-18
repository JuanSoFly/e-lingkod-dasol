<?php

namespace App\Models;

use App\Support\DatabaseExpression;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Report extends Model
{
    use HasFactory, SoftDeletes;

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
        'report_data',
        'summary_statistics',
        'total_records',
        'file_format',
        'pdf_file_path',
        'excel_file_path',
        'file_size',
        'file_hash',
        'status',
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

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'submitted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'generation_started_at' => 'datetime',
        'generation_completed_at' => 'datetime',
        'retention_until' => 'date',
        'filters' => 'array',
        'report_data' => 'array',
        'summary_statistics' => 'array',
        'contains_confidential_data' => 'boolean',
        'is_current_version' => 'boolean',
        'total_records' => 'integer',
        'file_size' => 'integer',
        'generation_duration_seconds' => 'integer',
        'version' => 'integer',
    ];

    // Report Types
    const TYPE_ACCESSION = 'accession';
    const TYPE_SEPARATION = 'separation';
    const TYPE_DIBAR = 'dibar';
    const TYPE_HARASSMENT = 'harassment';
    const TYPE_IGHR = 'ighr';

    // Status Options
    const STATUS_GENERATING = 'generating';
    const STATUS_GENERATED = 'generated';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_APPROVED = 'approved';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_FAILED = 'failed';

    // File Formats
    const FORMAT_PDF = 'pdf';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_BOTH = 'both';

    // Submission Methods
    const SUBMISSION_ONLINE = 'online';
    const SUBMISSION_EMAIL = 'email';
    const SUBMISSION_PHYSICAL = 'physical';

    // Confidentiality Levels
    const CONFIDENTIALITY_PUBLIC = 'public';
    const CONFIDENTIALITY_INTERNAL = 'internal';
    const CONFIDENTIALITY_CONFIDENTIAL = 'confidential';
    const CONFIDENTIALITY_RESTRICTED = 'restricted';

    // Relationships
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function parentReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'parent_report_id');
    }

    public function childReports(): HasMany
    {
        return $this->hasMany(Report::class, 'parent_report_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Report::class, 'parent_report_id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('report_year', $year);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->where('report_month', $month);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current_version', true);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_GENERATING,
            self::STATUS_GENERATED,
            self::STATUS_REVIEWED,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_ACKNOWLEDGED,
        ]);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeConfidential($query)
    {
        return $query->where('contains_confidential_data', true);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_GENERATING => 'Generating',
            self::STATUS_GENERATED => 'Generated',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            self::STATUS_FAILED => 'Failed',
            default => 'Unknown',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->report_type) {
            self::TYPE_ACCESSION => 'Monthly Accession Report',
            self::TYPE_SEPARATION => 'Monthly Separation Report',
            self::TYPE_DIBAR => 'Monthly DIBAR Report',
            self::TYPE_HARASSMENT => 'Monthly Sexual Harassment Cases Report',
            self::TYPE_IGHR => 'Annual IGHR Report',
            default => 'CSC Report',
        };
    }

    public function getPeriodAttribute(): string
    {
        if ($this->report_month) {
            return Carbon::create($this->report_year, $this->report_month, 1)->format('F Y');
        }

        return (string) $this->report_year;
    }

    public function getSubmissionStatusLabelAttribute(): string
    {
        return match ($this->submission_method) {
            self::SUBMISSION_ONLINE => 'Online Portal',
            self::SUBMISSION_EMAIL => 'Email',
            self::SUBMISSION_PHYSICAL => 'Physical Submission',
            default => 'Not Submitted',
        };
    }

    public function getConfidentialityLabelAttribute(): string
    {
        return match ($this->confidentiality_level) {
            self::CONFIDENTIALITY_PUBLIC => 'Public',
            self::CONFIDENTIALITY_INTERNAL => 'Internal',
            self::CONFIDENTIALITY_CONFIDENTIAL => 'Confidential',
            self::CONFIDENTIALITY_RESTRICTED => 'Restricted',
            default => 'Unknown',
        };
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

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->submitted_at) {
            // Monthly reports are due by the 10th of the following month
            if ($this->report_month) {
                $dueDate = Carbon::create($this->report_year, $this->report_month + 1, 10);
                return Carbon::now()->greaterThan($dueDate);
            }

            // Annual reports are due by January 31st of the following year
            $dueDate = Carbon::create($this->report_year + 1, 1, 31);
            return Carbon::now()->greaterThan($dueDate);
        }

        return false;
    }

    // Static Methods
    public static function generateReportNumber(string $type, int $year): string
    {
        $prefix = match ($type) {
            self::TYPE_ACCESSION => 'ACC',
            self::TYPE_SEPARATION => 'SEP',
            self::TYPE_DIBAR => 'DIB',
            self::TYPE_HARASSMENT => 'HAR',
            self::TYPE_IGHR => 'IGH',
            default => 'RPT',
        };

        // Use max to get the highest sequence number for this type and year
        $sequenceExpression = DatabaseExpression::numericCast("SUBSTRING(report_number, CHAR_LENGTH('$prefix-$year-') + 1)");

        $maxSequence = DB::table('reports')
            ->where('report_type', $type)
            ->where('report_year', $year)
            ->whereNotNull('report_number')
            ->where('report_number', 'like', $prefix . '-' . $year . '-%')
            ->max(DB::raw($sequenceExpression));

        $sequence = ($maxSequence ?? 0) + 1;

        return sprintf("%s-%d-%04d", $prefix, $year, $sequence);
    }

    public static function getReportTypes(): array
    {
        return [
            self::TYPE_ACCESSION => 'Monthly Accession Report',
            self::TYPE_SEPARATION => 'Monthly Separation Report',
            self::TYPE_DIBAR => 'Monthly DIBAR Report',
            self::TYPE_HARASSMENT => 'Monthly Sexual Harassment Cases Report',
            self::TYPE_IGHR => 'Annual IGHR Report',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_GENERATING => 'Generating',
            self::STATUS_GENERATED => 'Generated',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public static function getSubmissionMethods(): array
    {
        return [
            self::SUBMISSION_ONLINE => 'Online Portal',
            self::SUBMISSION_EMAIL => 'Email',
            self::SUBMISSION_PHYSICAL => 'Physical Submission',
        ];
    }

    public static function getConfidentialityLevels(): array
    {
        return [
            self::CONFIDENTIALITY_PUBLIC => 'Public',
            self::CONFIDENTIALITY_INTERNAL => 'Internal Use Only',
            self::CONFIDENTIALITY_CONFIDENTIAL => 'Confidential',
            self::CONFIDENTIALITY_RESTRICTED => 'Restricted Access',
        ];
    }

    // Business Logic Methods
    public function canBeReviewed(): bool
    {
        return $this->status === self::STATUS_GENERATED;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_REVIEWED;
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeAcknowledged(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function markAsReviewed(User $reviewer): void
    {
        $this->update([
            'status' => self::STATUS_REVIEWED,
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public function markAsApproved(User $approver): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
        ]);
    }

    public function markAsSubmitted(User $submitter, string $method, ?string $reference = null): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_by' => $submitter->id,
            'submitted_at' => now(),
            'submission_method' => $method,
            'submission_reference' => $reference,
        ]);
    }

    public function markAsAcknowledged(): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'generation_completed_at' => now(),
        ]);
    }

    public function createNewVersion(): self
    {
        // Mark current version as not current
        $this->update(['is_current_version' => false]);

        // Create new version
        $newVersion = $this->replicate([
            'id',
            'report_number',
            'created_at',
            'updated_at',
        ]);

        $newVersion->version = $this->version + 1;
        $newVersion->parent_report_id = $this->id;
        $newVersion->is_current_version = true;
        $newVersion->status = self::STATUS_GENERATING;
        $newVersion->submitted_at = null;
        $newVersion->acknowledged_at = null;
        $newVersion->reviewed_by = null;
        $newVersion->approved_by = null;
        $newVersion->submitted_by = null;
        $newVersion->generation_started_at = now();
        $newVersion->generation_completed_at = null;
        $newVersion->generation_duration_seconds = null;
        $newVersion->error_message = null;

        $newVersion->save();

        return $newVersion;
    }

    protected static function booted()
    {
        static::creating(function ($report) {
            if (!$report->report_number) {
                $report->report_number = self::generateReportNumber(
                    $report->report_type,
                    $report->report_year
                );
            }

            if (!$report->generation_started_at) {
                $report->generation_started_at = now();
            }
        });

        static::updating(function ($report) {
            if ($report->isDirty(['generated_by', 'reviewed_by', 'approved_by', 'submitted_by'])) {
                // Ensure assigned users are different
                $assignedUsers = [
                    $report->generated_by,
                    $report->reviewed_by,
                    $report->approved_by,
                    $report->submitted_by,
                ];

                $assignedUsers = array_filter($assignedUsers);
                if (count($assignedUsers) !== count(array_unique($assignedUsers))) {
                    throw new \InvalidArgumentException('Same user cannot be assigned to multiple workflow steps');
                }
            }
        });
    }
}
