<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeReference extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_references';

    protected $fillable = [
        'employee_id',
        'full_name',
        'address',
        'telephone_no',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}