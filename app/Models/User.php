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
        'office_id',
        'office_role',
        'position',
        'department',
        'is_department_head',
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

    /**
     * Get the full name with extension from the associated employee.
     * Falls back to the user's name field if no employee relationship exists.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->employee && $this->employee->exists) {
            return $this->employee->full_name;
        }

        return $this->name;
    }

    /**
     * Get avatar initials including name extensions.
     * Returns first and last name initials, prioritizing first and last letters.
     */
    public function getAvatarInitialsAttribute(): string
    {
        $fullName = $this->full_name;

        // Split full name into words
        $words = array_filter(explode(' ', $fullName));

        if (count($words) === 0) {
            return 'U';
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }

        // Get first letter of first word and first letter of last significant word
        $firstWord = $words[0];
        $lastWord = end($words);

        // Skip common name extensions for initials (Jr, Sr, II, III, IV)
        $extensions = ['Jr', 'Sr', 'II', 'III', 'IV', 'V', 'VI'];
        if (in_array($lastWord, $extensions)) {
            // Find the last word that's not an extension
            $tempWords = array_filter($words, function($word) use ($extensions) {
                return !in_array($word, $extensions);
            });
            if (count($tempWords) > 1) {
                $lastWord = end($tempWords);
            } else {
                $lastWord = $firstWord;
            }
        }

        return strtoupper(substr($firstWord, 0, 1) . substr($lastWord, 0, 1));
    }
}