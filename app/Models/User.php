<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the employee associated with the user.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get office assignments for the user.
     */
    public function officeAssignments(): HasMany
    {
        return $this->hasMany(\App\Models\OfficeAssignment::class);
    }

    /**
     * Get active office assignments for the user.
     */
    public function activeOfficeAssignments(): HasMany
    {
        return $this->officeAssignments()->where('is_active', true);
    }

    /**
     * Get current office assignment for the user.
     */
    public function currentOfficeAssignment(): HasMany
    {
        return $this->activeOfficeAssignments()
                    ->where('assigned_date', '<=', now())
                    ->where(function ($query) {
                        $query->whereNull('ended_date')
                              ->orWhere('ended_date', '>=', now());
                    });
    }

    /**
     * Get employees archived by this user.
     */
    public function archivedEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'archived_by');
    }
}