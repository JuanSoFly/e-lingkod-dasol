<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeCivilServiceEligibility extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_civil_service_eligibilities';

    protected $fillable = [
        'employee_id',
        'eligibility_name',
        'rating',
        'date_of_examination',
        'place_of_examination',
        'license_number',
        'date_of_validity',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'date_of_examination' => 'date',
        'date_of_validity' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getIsValidAttribute(): bool
    {
        if (!$this->date_of_validity) {
            return true; // No expiration date means permanently valid
        }
        
        return $this->date_of_validity->isFuture();
    }

    public function getFormattedRatingAttribute(): ?string
    {
        return $this->rating ? number_format($this->rating, 2) . '%' : null;
    }
}