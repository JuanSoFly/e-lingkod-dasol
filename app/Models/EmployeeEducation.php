<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmployeeEducation extends Model
{
    use HasFactory;

    protected $table = 'employee_education';

    protected $fillable = [
        'employee_id',
        'education_level',
        'school_name',
        'course',
        'year_graduated',
        'honors',
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
}