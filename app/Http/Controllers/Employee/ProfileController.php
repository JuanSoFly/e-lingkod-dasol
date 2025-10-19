<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get employee profile information
     */
    public function index(): JsonResponse
    {
        $employee = auth()->user()->employee;

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'middle_name' => $employee->middle_name,
                'last_name' => $employee->last_name,
                'suffix' => $employee->suffix,
                'employee_id' => $employee->employee_id,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'address' => $employee->address,
                'position' => $employee->position,
                'department' => $employee->department,
                'employment_type' => $employee->employment_type,
                'date_hired' => $employee->date_hired?->format('Y-m-d'),
                'birth_date' => $employee->birth_date?->format('Y-m-d'),
                'civil_status' => $employee->civil_status,
                'gender' => $employee->gender,
                'blood_type' => $employee->blood_type,
            ],
            'notification_preferences' => $this->getNotificationPreferences($employee),
        ]);
    }

    /**
     * Update employee profile
     */
    public function update(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email,' . $employee->id],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'birth_date' => ['required', 'date', 'before:today'],
            'civil_status' => ['required', 'string', 'in:Single,Married,Widowed,Separated,Legally Separated'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'blood_type' => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $employee->update($validated);

            return response()->json([
                'message' => 'Profile updated successfully',
                'employee' => $employee->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get notification preferences
     */
    public function getNotificationPreferences(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        return response()->json([
            'preferences' => $this->getEmployeeNotificationPreferences($employee),
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        $validated = $request->validate([
            'email_notifications' => ['required', 'boolean'],
            'in_app_notifications' => ['required', 'boolean'],
            'leave_status_updates' => ['required', 'boolean'],
            'approval_notifications' => ['required', 'boolean'],
            'deadline_reminders' => ['required', 'boolean'],
            'policy_updates' => ['required', 'boolean'],
        ]);

        try {
            // Store preferences in a settings table or user meta
            $employee->settings()->updateOrCreate(
                ['key' => 'notification_preferences'],
                ['value' => json_encode($validated)]
            );

            return response()->json([
                'message' => 'Notification preferences updated successfully',
                'preferences' => $validated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        try {
            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect',
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            return response()->json([
                'message' => 'Password changed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change password',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get notification preferences from employee settings
     */
    private function getEmployeeNotificationPreferences(Employee $employee): array
    {
        $defaultPreferences = [
            'email_notifications' => true,
            'in_app_notifications' => true,
            'leave_status_updates' => true,
            'approval_notifications' => true,
            'deadline_reminders' => true,
            'policy_updates' => true,
        ];

        // Try to get from settings table (assuming settings relationship exists)
        $settings = $employee->settings()->where('key', 'notification_preferences')->first();

        if ($settings) {
            return array_merge($defaultPreferences, json_decode($settings->value, true));
        }

        return $defaultPreferences;
    }
}
