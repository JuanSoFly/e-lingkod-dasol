<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateUserService
{
    public function __construct(
        private EmployeeNumberService $employeeNumberService
    ) {}

    /**
     * Create a new employee with associated user account
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            try {
                // Generate unique employee number if not provided
                if (empty($data['employee_number'])) {
                    $data['employee_number'] = $this->employeeNumberService->generateUniqueNumber();
                } else {
                    // Validate provided employee number format
                    if (!$this->employeeNumberService->isValidFormat($data['employee_number'])) {
                        throw ValidationException::withMessages([
                            'employee_number' => 'Invalid employee number format. Expected format: DAS-YYYY-XXXX'
                        ]);
                    }
                }

                // Create employee record
                $employee = Employee::create($data);

                // Generate secure random password
                $temporaryPassword = Str::random(12);

                // Create user account
                $user = User::create([
                    'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                    'email' => $data['email'],
                    'password' => Hash::make($temporaryPassword),
                    'employee_id' => $employee->id,
                    'email_verified_at' => null, // Force email verification
                ]);

                // Assign employee role
                $user->assignRole('Employee');

                // Generate password reset token
                $token = Password::createToken($user);

                // Send notification (with error handling)
                try {
                    $user->notify(new \App\Notifications\NewEmployeeAccountCreated($token));
                    Log::info('Employee account creation notification sent', [
                        'employee_id' => $employee->id,
                        'employee_number' => $employee->employee_number,
                        'email' => $user->email
                    ]);
                } catch (\Exception $notificationError) {
                    Log::warning('Failed to send employee account notification', [
                        'employee_id' => $employee->id,
                        'email' => $user->email,
                        'error' => $notificationError->getMessage()
                    ]);
                    // Continue with user creation even if notification fails
                }

                Log::info('Employee created successfully', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'name' => $user->name,
                    'email' => $user->email
                ]);

                return $user;

            } catch (\Exception $e) {
                Log::error('Failed to create employee', [
                    'data' => array_except($data, ['password', 'ssn', 'tin']), // Exclude sensitive data
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Validate employee data before creation
     */
    public function validateEmployeeData(array $data): array
    {
        $errors = [];

        // Check for duplicate email in users table
        if (isset($data['email']) && User::where('email', $data['email'])->exists()) {
            $errors['email'] = 'This email address is already registered.';
        }

        // Check for duplicate employee number
        if (isset($data['employee_number']) && Employee::where('employee_number', $data['employee_number'])->exists()) {
            $errors['employee_number'] = 'This employee number already exists.';
        }

        // Validate date relationships
        if (isset($data['date_hired']) && isset($data['birth_date'])) {
            if (strtotime($data['date_hired']) <= strtotime($data['birth_date'])) {
                $errors['date_hired'] = 'Date hired must be after birth date.';
            }
        }

        // Validate age requirement (minimum 18 years old)
        if (isset($data['birth_date'])) {
            $age = \Carbon\Carbon::parse($data['birth_date'])->age;
            if ($age < 18) {
                $errors['birth_date'] = 'Employee must be at least 18 years old.';
            }
        }

        // Validate salary grade and step relationship
        if (isset($data['salary_grade']) && isset($data['step_increment'])) {
            if ($data['salary_grade'] < 1 || $data['salary_grade'] > 33) {
                $errors['salary_grade'] = 'Salary grade must be between 1 and 33.';
            }
            if ($data['step_increment'] < 1 || $data['step_increment'] > 8) {
                $errors['step_increment'] = 'Step increment must be between 1 and 8.';
            }
        }

        return $errors;
    }
}