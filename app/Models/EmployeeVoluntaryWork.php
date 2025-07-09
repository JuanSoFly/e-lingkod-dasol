<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeVoluntaryWork extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_voluntary_work';

    protected $fillable = [
        'employee_id',
        'organization_name_address',
        'inclusive_date_from',
        'inclusive_date_to',
        'number_of_hours',
        'position_nature_of_work',
    ];

    protected $casts = [
        'inclusive_date_from' => 'date',
        'inclusive_date_to' => 'date',
        'number_of_hours' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->inclusive_date_from) {
            return null;
        }

        $endDate = $this->inclusive_date_to ?: now();
        $duration = $this->inclusive_date_from->diffInMonths($endDate);
        
        if ($duration < 1) {
            return 'Less than 1 month';
        } elseif ($duration < 12) {
            return $duration . ' month' . ($duration > 1 ? 's' : '');
        } else {
            $years = intval($duration / 12);
            $months = $duration % 12;
            $result = $years . ' year' . ($years > 1 ? 's' : '');
            if ($months > 0) {
                $result .= ', ' . $months . ' month' . ($months > 1 ? 's' : '');
            }
            return $result;
        }
    }

    public function getIsOngoingAttribute(): bool
    {
        return $this->inclusive_date_to === null;
    }
}