<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeChildren extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_children';

    protected $fillable = [
        'employee_id',
        'full_name',
        'date_of_birth',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->diffInYears(now());
    }
}