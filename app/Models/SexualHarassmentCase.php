<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SexualHarassmentCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_number',
        'complainant_id',
        'complainant_name',
        'respondent_id',
        'respondent_name',
        'incident_date',
        'filed_date',
        'incident_description',
        'complainant_statement',
        'respondent_statement',
        'case_status',
        'investigation_status',
        'investigation_start_date',
        'investigation_end_date',
        'investigating_officer_id',
        'investigation_findings',
        'recommendations',
        'resolution_type',
        'resolution_details',
        'resolution_date',
        'administrative_action',
        'forwarded_to_court',
        'court_filing_date',
        'court_case_number',
        'court_status',
        'appeal_filed',
        'appeal_date',
        'appeal_status',
        'witnesses',
        'evidence_files',
        'remarks',
        'hr_officer_id',
        'legal_officer_id',
        'is_confidential',
        'department_involved',
        'office_location',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'filed_date' => 'date',
        'investigation_start_date' => 'date',
        'investigation_end_date' => 'date',
        'resolution_date' => 'date',
        'court_filing_date' => 'date',
        'appeal_date' => 'date',
        'forwarded_to_court' => 'boolean',
        'appeal_filed' => 'boolean',
        'is_confidential' => 'boolean',
        'witnesses' => 'array',
        'evidence_files' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Case status constants
     */
    const STATUS_FILED = 'filed';
    const STATUS_UNDER_INVESTIGATION = 'under_investigation';
    const STATUS_DISMISSED = 'dismissed';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_FORWARDED_TO_COURT = 'forwarded_to_court';
    const STATUS_PENDING_APPEAL = 'pending_appeal';

    /**
     * Resolution type constants
     */
    const RESOLUTION_ADMINISTRATIVE_SANCTION = 'administrative_sanction';
    const RESOLUTION_DISMISSAL = 'dismissal';
    const RESOLUTION_NO_VIOLATION = 'no_violation';
    const RESOLUTION_MEDIATION = 'mediation';
    const RESOLUTION_SETTLEMENT = 'settlement';

    /**
     * Get all possible case statuses
     */
    public static function getCaseStatuses(): array
    {
        return [
            self::STATUS_FILED => 'Filed',
            self::STATUS_UNDER_INVESTIGATION => 'Under Investigation',
            self::STATUS_DISMISSED => 'Dismissed',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_FORWARDED_TO_COURT => 'Forwarded to Court',
            self::STATUS_PENDING_APPEAL => 'Pending Appeal',
        ];
    }

    /**
     * Get all possible resolution types
     */
    public static function getResolutionTypes(): array
    {
        return [
            self::RESOLUTION_ADMINISTRATIVE_SANCTION => 'Administrative Sanction',
            self::RESOLUTION_DISMISSAL => 'Dismissal',
            self::RESOLUTION_NO_VIOLATION => 'No Violation Found',
            self::RESOLUTION_MEDIATION => 'Mediation',
            self::RESOLUTION_SETTLEMENT => 'Settlement',
        ];
    }

    /**
     * Relationships
     */
    public function complainantEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'complainant_id');
    }

    public function respondentEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'respondent_id');
    }

    public function investigatingOfficer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'investigating_officer_id');
    }

    public function hrOfficer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'hr_officer_id');
    }

    public function legalOfficer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'legal_officer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('case_status', $status);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department_involved', $department);
    }

    public function scopeFiledInMonth($query, int $year, int $month)
    {
        return $query->whereYear('filed_date', $year)
                    ->whereMonth('filed_date', $month);
    }

    public function scopeResolvedInMonth($query, int $year, int $month)
    {
        return $query->whereYear('resolution_date', $year)
                    ->whereMonth('resolution_date', $month);
    }

    public function scopeUnderInvestigation($query)
    {
        return $query->where('case_status', self::STATUS_UNDER_INVESTIGATION);
    }

    public function scopePending($query)
    {
        return $query->whereIn('case_status', [
            self::STATUS_FILED,
            self::STATUS_UNDER_INVESTIGATION,
            self::STATUS_PENDING_APPEAL,
        ]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('case_status', [
            self::STATUS_DISMISSED,
            self::STATUS_RESOLVED,
            self::STATUS_FORWARDED_TO_COURT,
        ]);
    }

    /**
     * Mutators and Accessors
     */
    public function getCaseStatusLabelAttribute(): string
    {
        return self::getCaseStatuses()[$this->case_status] ?? $this->case_status;
    }

    public function getResolutionTypeLabelAttribute(): string
    {
        return self::getResolutionTypes()[$this->resolution_type] ?? $this->resolution_type;
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->case_status, [
            self::STATUS_FILED,
            self::STATUS_UNDER_INVESTIGATION,
            self::STATUS_PENDING_APPEAL,
        ]);
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->case_status, [
            self::STATUS_DISMISSED,
            self::STATUS_RESOLVED,
            self::STATUS_FORWARDED_TO_COURT,
        ]);
    }

    public function getDaysOpenAttribute(): int
    {
        $endDate = $this->resolution_date ?? now();
        return $this->filed_date->diffInDays($endDate);
    }

    /**
     * Helper methods
     */
    public function canBeEdited(): bool
    {
        return in_array($this->case_status, [
            self::STATUS_FILED,
            self::STATUS_UNDER_INVESTIGATION,
        ]);
    }

    public function canBeClosed(): bool
    {
        return $this->case_status === self::STATUS_UNDER_INVESTIGATION;
    }

    public function canBeAppealed(): bool
    {
        return in_array($this->case_status, [
            self::STATUS_DISMISSED,
            self::STATUS_RESOLVED,
        ]) && !$this->appeal_filed;
    }

    /**
     * Generate unique case number
     */
    public static function generateCaseNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        
        // Get the last case number for this month
        $lastCase = self::whereYear('filed_date', $year)
                       ->whereMonth('filed_date', now()->month)
                       ->orderBy('case_number', 'desc')
                       ->first();

        if ($lastCase) {
            // Extract the sequence number from the last case
            $parts = explode('-', $lastCase->case_number);
            $sequence = intval(end($parts)) + 1;
        } else {
            $sequence = 1;
        }

        return "SH-{$year}-{$month}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}