<?php

namespace App\Models;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCalendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'timezone',
        'work_week',
    ];

    protected $casts = [
        'work_week' => 'array',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
