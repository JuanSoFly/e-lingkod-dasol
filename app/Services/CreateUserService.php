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
        // Pre-validation before transaction
        $validationErrors = $this->validateEmployeeData($data);
        if (!empty($validationErrors)) {
            throw ValidationException::withMessages($validationErrors);
        }

        return DB::transaction(function () use ($data) {
            try {
                // Always generate a unique employee number to avoid race conditions
                // Ignore any employee_number provided by frontend to ensure uniqueness
                $data['employee_number'] = $this->employeeNumberService->generateUniqueNumber();

                // Normalize data for consistent TitleCase storage
                $normalizedData = $this->normalizeEmployeeData($data);

                // Handle office assignment and populate department for backward compatibility
                if (isset($normalizedData['office_id']) && $normalizedData['office_id']) {
                    $office = \App\Models\Office::find($normalizedData['office_id']);
                    if ($office) {
                        $normalizedData['department'] = $office->name;
                        $normalizedData['office_code'] = $office->code;
                    }
                }

                // Create employee record
                $employee = Employee::create($normalizedData);

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
                    'data' => collect($data)->except(['password', 'ssn', 'tin'])->all(), // Exclude sensitive data
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

        // Check for duplicate email in employees table (excluding soft-deleted)
        if (isset($data['email']) && Employee::where('email', $data['email'])->whereNull('deleted_at')->exists()) {
            $errors['email'] = 'This email address is already registered to an active employee.';
        }

        // No need to check for duplicate employee number since we always generate it in the transaction
        // The unique database constraint will handle any unexpected duplicates

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

    /**
     * Normalize employee data for consistent storage
     */
    private function normalizeEmployeeData(array $data): array
    {
        // Normalize gender to TitleCase
        if (isset($data['gender'])) {
            $data['gender'] = ucfirst(strtolower($data['gender']));
        }

        // Normalize civil status to TitleCase
        if (isset($data['civil_status'])) {
            $civilStatusMap = [
                'single' => 'Single',
                'married' => 'Married',
                'divorced' => 'Divorced',
                'widowed' => 'Widowed',
                'separated' => 'Separated'
            ];
            $data['civil_status'] = $civilStatusMap[strtolower($data['civil_status'])] ?? $data['civil_status'];
        }

        return $data;
    }

    /**
     * Create user account for existing orphaned employee
     */
    public function createUserForEmployee(Employee $employee, string $email = null): User
    {
        return DB::transaction(function () use ($employee, $email) {
            try {
                // Check if employee already has user
                if ($employee->user) {
                    throw new \Exception('Employee already has associated user account');
                }

                // Generate email if not provided
                $userEmail = $email ?? strtolower(str_replace(' ', '.', $employee->first_name . '.' . $employee->last_name)) . '@dasol.gov.ph';

                // Generate secure random password
                $temporaryPassword = Str::random(12);

                // Create user account
                $user = User::create([
                    'name' => trim($employee->first_name . ' ' . $employee->last_name),
                    'email' => $userEmail,
                    'password' => Hash::make($temporaryPassword),
                    'employee_id' => $employee->id,
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]);

                // Assign employee role
                $user->assignRole('Employee');

                // Generate password reset token
                $token = Password::createToken($user);

                // Send notification with error handling
                try {
                    $user->notify(new \App\Notifications\NewEmployeeAccountCreated($token));
                    Log::info('User account created for existing employee', [
                        'employee_id' => $employee->id,
                        'employee_number' => $employee->employee_number,
                        'email' => $user->email
                    ]);
                } catch (\Exception $notificationError) {
                    Log::warning('Failed to send account creation notification', [
                        'employee_id' => $employee->id,
                        'email' => $user->email,
                        'error' => $notificationError->getMessage()
                    ]);
                }

                return $user;

            } catch (\Exception $e) {
                Log::error('Failed to create user for existing employee', [
                    'employee_id' => $employee->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Link existing user to existing employee
     */
    public function linkUserToEmployee(User $user, Employee $employee): void
    {
        DB::transaction(function () use ($user, $employee) {
            try {
                // Check if user is already linked
                if ($user->employee_id) {
                    throw new \Exception('User is already linked to an employee');
                }

                // Check if employee already has user
                if ($employee->user) {
                    throw new \Exception('Employee already has an associated user account');
                }

                // Update user with employee link
                $user->update([
                    'employee_id' => $employee->id,
                    'updated_at' => now(),
                ]);

                Log::info('User linked to employee successfully', [
                    'user_id' => $user->id,
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to link user to employee', [
                    'user_id' => $user->id,
                    'employee_id' => $employee->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Validate user-employee relationship consistency
     */
    public function validateUserEmployeeRelationship(User $user, Employee $employee): array
    {
        $errors = [];

        // Check email synchronization
        if ($user->email !== $employee->email) {
            $errors['email_mismatch'] = 'User email does not match employee email';
        }

        // Check name similarity
        $userFullName = strtolower(trim($user->name));
        $employeeFullName = strtolower(trim($employee->first_name . ' ' . $employee->last_name));

        if ($userFullName !== $employeeFullName) {
            $errors['name_mismatch'] = 'User name does not match employee name';
        }

        return $errors;
    }
}