<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'observed_date',
        'name',
        'class',
        'scope',
        'scope_code',
        'is_non_working',
        'year',
        'source',
    ];

    protected $casts = [
        'date' => 'date',
        'observed_date' => 'date',
        'is_non_working' => 'boolean',
        'year' => 'integer',
    ];
}
