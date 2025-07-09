<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeEducation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_education';

    protected $fillable = [
        'employee_id',
        'education_level',
        'school_name',
        'course',
        'degree_course',
        'period_from',
        'period_to',
        'highest_level_units_earned',
        'year_graduated',
        'year_graduated_pds',
        'honors',
        'scholarship_honors_received',
        'attachment_id',
    ];

    protected $casts = [
        'period_from' => 'integer',
        'period_to' => 'integer',
        'year_graduated' => 'integer',
        'year_graduated_pds' => 'integer',
    ];

    const EDUCATION_LEVELS = [
        'Elementary' => 'Elementary',
        'Secondary' => 'Secondary',
        'Vocational/Trade' => 'Vocational/Trade',
        'College' => 'College',
        'Graduate Studies' => 'Graduate Studies',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
     * Get linked education credential documents
     */
    public function getCredentialDocuments(bool $activeOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->targetLinks()->where('link_type', 'education_credential');
        
        if ($activeOnly) {
            $query->active();
        }

        return $query->with('source')->get()->pluck('source')
                    ->filter(function ($document) {
                        return $document instanceof EmployeeDocument;
                    });
    }

    /**
     * Get education period duration in human readable format
     */
    public function getDurationAttribute(): string
    {
        if ($this->period_from && $this->period_to) {
            return $this->period_from . ' - ' . $this->period_to;
        } elseif ($this->period_from) {
            return $this->period_from . ' - Present';
        }
        
        return 'Not specified';
    }

    /**
     * Get the display name for education level
     */
    public function getEducationLevelDisplayAttribute(): string
    {
        return self::EDUCATION_LEVELS[$this->education_level] ?? $this->education_level;
    }

    /**
     * Check if this education level requires a degree/course
     */
    public function requiresDegree(): bool
    {
        return in_array($this->education_level, ['College', 'Graduate Studies', 'Vocational/Trade']);
    }

    /**
     * Get formatted graduation year (prefer PDS year over legacy year)
     */
    public function getGraduationYearAttribute(): ?int
    {
        return $this->year_graduated_pds ?? $this->year_graduated;
    }

    /**
     * Get all honors and scholarships combined
     */
    public function getAllHonorsAttribute(): string
    {
        $honors = array_filter([
            $this->honors,
            $this->scholarship_honors_received
        ]);
        
        return implode('; ', $honors);
    }
}