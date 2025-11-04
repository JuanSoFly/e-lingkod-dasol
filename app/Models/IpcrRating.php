<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpcrRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id',
        'ipcr_item_id',
        'rater_user_id',
        'rater_employee_id',
        'rater_role',
        'rating_type',
        'quality_rating',
        'efficiency_rating',
        'timeliness_rating',
        'overall_rating',
        'rating_details',
        'comments',
        'rated_at',
    ];

    protected $casts = [
        'quality_rating' => 'decimal:2',
        'efficiency_rating' => 'decimal:2',
        'timeliness_rating' => 'decimal:2',
        'overall_rating' => 'decimal:2',
        'rating_details' => 'array',
        'rated_at' => 'datetime',
    ];

    public function ipcr(): BelongsTo
    {
        return $this->belongsTo(Ipcr::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(IpcrItem::class, 'ipcr_item_id');
    }

    public function raterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    public function raterEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'rater_employee_id');
    }
}
