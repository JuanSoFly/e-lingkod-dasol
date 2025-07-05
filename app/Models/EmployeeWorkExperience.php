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
        'position',
        'company',
        'from_date',
        'to_date',
        'salary',
        'status',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'salary' => 'decimal:2',
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