<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CivilServiceEligibility extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'eligibility_type',
        'examination_name', 
        'date_taken',
        'rating',
        'place_of_examination',
        'certificate_number',
        'license_number',
        'valid_until',
        'status',
        'remarks',
        'issuing_authority',
        'date_issued',
        'is_lifetime_valid',
        'is_verified',
        'verified_at',
        'verified_by',
        'certificate_file_path',
        'verification_document_path'
    ];

    protected $casts = [
        'date_taken' => 'date',
        'valid_until' => 'date',
        'date_issued' => 'date',
        'is_lifetime_valid' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'rating' => 'decimal:2'
    ];

    /**
     * Get the employee that owns this eligibility.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who verified this eligibility.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope a query to only include active eligibilities.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope a query to only include expired eligibilities.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'Expired')
                    ->orWhere(function($q) {
                        $q->where('valid_until', '<', now())
                          ->where('is_lifetime_valid', false);
                    });
    }

    /**
     * Scope a query to only include verified eligibilities.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Check if the eligibility is currently valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== 'Active') {
            return false;
        }

        if ($this->is_lifetime_valid) {
            return true;
        }

        return $this->valid_until ? $this->valid_until->isFuture() : true;
    }

    /**
     * Check if the eligibility is expired.
     */
    public function isExpired(): bool
    {
        if ($this->is_lifetime_valid) {
            return false;
        }

        return $this->valid_until ? $this->valid_until->isPast() : false;
    }

    /**
     * Get the days until expiration.
     */
    public function daysUntilExpiration(): ?int
    {
        if ($this->is_lifetime_valid || !$this->valid_until) {
            return null;
        }

        return max(0, now()->diffInDays($this->valid_until, false));
    }

    /**
     * Check if eligibility is expiring soon (within 30 days).
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->is_lifetime_valid || !$this->valid_until) {
            return false;
        }

        $daysUntilExpiration = $this->daysUntilExpiration();
        return $daysUntilExpiration !== null && $daysUntilExpiration <= $days;
    }

    /**
     * Get formatted rating with percentage.
     */
    public function getFormattedRatingAttribute(): ?string
    {
        return $this->rating ? number_format($this->rating, 2) . '%' : null;
    }

    /**
     * Get the eligibility status with additional context.
     */
    public function getStatusWithContextAttribute(): string
    {
        $status = $this->status;
        
        if ($status === 'Active' && $this->isExpired()) {
            $status = 'Expired';
        } elseif ($status === 'Active' && $this->isExpiringSoon()) {
            $status = 'Expiring Soon';
        }

        return $status;
    }

    /**
     * Boot method for model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-update status when eligibility expires
        static::saving(function ($eligibility) {
            if (!$eligibility->is_lifetime_valid && 
                $eligibility->valid_until && 
                $eligibility->valid_until->isPast() && 
                $eligibility->status === 'Active') {
                $eligibility->status = 'Expired';
            }
        });
    }

    /**
     * The Philippine Civil Service eligibility types.
     */
    public static function getEligibilityTypes(): array
    {
        return [
            'Professional' => 'Professional',
            'Sub-professional' => 'Sub-professional',
            'Second Level' => 'Second Level',
            'First Level' => 'First Level',
            'RA 1080' => 'RA 1080 (Board/Bar)',
            'Bar/Board' => 'Bar/Board Examination',
            'CES' => 'Career Executive Service',
            'CESO' => 'Career Executive Service Officer',
            'Career Executive Service' => 'Career Executive Service',
            'Fire Officer' => 'Fire Officer',
            'Penology Officer' => 'Penology Officer',
            'NAPOLCOM' => 'NAPOLCOM',
            'Other' => 'Other'
        ];
    }

    /**
     * The status options for eligibility.
     */
    public static function getStatusOptions(): array
    {
        return [
            'Active' => 'Active',
            'Expired' => 'Expired',
            'Suspended' => 'Suspended',
            'Revoked' => 'Revoked',
            'Pending Verification' => 'Pending Verification'
        ];
    }
}