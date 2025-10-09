<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePhoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_photos';

    protected $fillable = [
        'employee_id',
        'photo_path',
        'photo_size',
        'photo_format',
        'thumbmark_path',
        'thumbmark_size',
        'thumbmark_format',
        'photo_taken_date',
        'thumbmark_taken_date',
        'is_active',
    ];

    protected $casts = [
        'photo_taken_date' => 'datetime',
        'thumbmark_taken_date' => 'datetime',
        'photo_size' => 'integer',
        'thumbmark_size' => 'integer',
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // CSC Form No. 212 - Photo requirements
    public static function getPhotoSpecs(): array
    {
        return [
            'width' => 3.5, // cm
            'height' => 4.5, // cm
            'format' => 'jpg',
            'max_size' => 2048, // KB
            'min_dpi' => 300,
        ];
    }

    public static function getThumbmarkSpecs(): array
    {
        return [
            'width' => 2.5, // cm
            'height' => 2.5, // cm
            'format' => 'jpg',
            'max_size' => 1024, // KB
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    public function getThumbmarkUrlAttribute(): ?string
    {
        return $this->thumbmark_path ? asset('storage/' . $this->thumbmark_path) : null;
    }

    public function getPhotoSizeKbAttribute(): float
    {
        return $this->photo_size ? round($this->photo_size / 1024, 2) : 0;
    }

    public function getThumbmarkSizeKbAttribute(): float
    {
        return $this->thumbmark_size ? round($this->thumbmark_size / 1024, 2) : 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithPhoto($query)
    {
        return $query->whereNotNull('photo_path');
    }

    public function scopeWithThumbmark($query)
    {
        return $query->whereNotNull('thumbmark_path');
    }

    public function getFullNameTagAttribute(): string
    {
        return strtoupper($this->employee->full_name);
    }
}