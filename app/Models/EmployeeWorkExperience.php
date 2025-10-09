<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmployeeWorkExperience extends Model
{
    use HasFactory;

    protected $table = 'employee_work_experience';

    protected $fillable = [
        'employee_id',
        // Original fields
        'position',
        'company',
        'from_date',
        'to_date',
        'salary',
        'status',
        // PDS Panel 5: Work Experience fields
        'inclusive_date_from',
        'inclusive_date_to',
        'position_title',
        'department_agency_office',
        'monthly_salary',
        'salary_grade_step',
        'status_of_appointment',
        'is_government_service',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'inclusive_date_from' => 'date',
        'inclusive_date_to' => 'date',
        'salary' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'is_government_service' => 'boolean',
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
     * Get linked work experience proof documents
     */
    public function getProofDocuments(bool $activeOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->targetLinks()->where('link_type', 'work_experience_proof');
        
        if ($activeOnly) {
            $query->active();
        }

        return $query->with('source')->get()->pluck('source')
                    ->filter(function ($document) {
                        return $document instanceof EmployeeDocument;
                    });
    }
}