<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewEmployeeAccountCreated;

class CreateUserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::create($data);

            // Generate secure random password
            $temporaryPassword = Str::random(12);
            
            $user = User::create([
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($temporaryPassword),
                'employee_id' => $employee->id,
                'email_verified_at' => null, // Force email verification
            ]);

            $user->assignRole('Employee');

            // Send password reset token instead of password
            $token = Password::createToken($user);
            
            // Create notification for new account
            $user->notify(new \App\Notifications\NewEmployeeAccountCreated($token));

            return $user;
        });
    }
}
