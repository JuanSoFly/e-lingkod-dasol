<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeOtherInformation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_other_information';

    protected $fillable = [
        'employee_id',
        'information_type',
        'description',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeSpecialSkills($query)
    {
        return $query->where('information_type', 'special_skills');
    }

    public function scopeDistinctions($query)
    {
        return $query->where('information_type', 'distinctions');
    }

    public function scopeMemberships($query)
    {
        return $query->where('information_type', 'memberships');
    }

    public function getFormattedTypeAttribute(): string
    {
        return match($this->information_type) {
            'special_skills' => 'Special Skills and Hobbies',
            'distinctions' => 'Non-Academic Distinctions/Recognition',
            'memberships' => 'Membership in Association/Organization',
            default => ucfirst(str_replace('_', ' ', $this->information_type)),
        };
    }
}