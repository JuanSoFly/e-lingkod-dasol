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
        'reference_order',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // CSC Form No. 212 - Reference validation methods
    public static function getMaxReferences(): int
    {
        return 3; // CSC Form requires exactly 3 references
    }

    public static function getReferenceOrderOptions(): array
    {
        return [1, 2, 3]; // CSC Form has 3 reference fields
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('reference_order', 'asc');
    }

    public function getCscFormattedNameAttribute(): string
    {
        return strtoupper($this->full_name);
    }

    public function getCscFormattedAddressAttribute(): string
    {
        return strtoupper($this->address);
    }
}