<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeFamilyBackground extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_family_background';

    protected $fillable = [
        'employee_id',
        'spouse_surname',
        'spouse_first_name',
        'spouse_middle_name',
        'spouse_occupation',
        'spouse_employer',
        'spouse_business_address',
        'spouse_telephone_no',
        'father_surname',
        'father_first_name',
        'father_middle_name',
        'mother_maiden_name',
        'mother_surname',
        'mother_first_name',
        'mother_middle_name',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getSpouseFullNameAttribute(): ?string
    {
        if (!$this->spouse_first_name) {
            return null;
        }
        
        return trim($this->spouse_first_name . ' ' . $this->spouse_middle_name . ' ' . $this->spouse_surname);
    }

    public function getFatherFullNameAttribute(): ?string
    {
        if (!$this->father_first_name) {
            return null;
        }
        
        return trim($this->father_first_name . ' ' . $this->father_middle_name . ' ' . $this->father_surname);
    }

    public function getMotherFullNameAttribute(): ?string
    {
        if (!$this->mother_first_name) {
            return null;
        }
        
        return trim($this->mother_first_name . ' ' . $this->mother_middle_name . ' ' . $this->mother_surname);
    }
}