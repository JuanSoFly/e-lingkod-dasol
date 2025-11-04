<?php

namespace Database\Seeders;

use App\Models\OfficeAssignment;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfficeAssignmentDataCleanupSeeder extends Seeder
{
    /**
     * Clean up duplicate and inconsistent office assignment data
     */
    public function run(): void
    {
        $this->command->info('Starting office assignment data cleanup...');

        try {
            DB::beginTransaction();

            // Fix 1: Remove duplicate Juan Dela Cruz assignment (ID 31 - employee_id is NULL)
            $this->command->info('Fixing Juan Dela Cruz duplicate assignments...');

            $juanDuplicate = OfficeAssignment::where('id', 31)
                ->where('user_id', 2) // Juan Dela Cruz user
                ->where('employee_id', null) // Missing employee data
                ->where('office_id', 5) // HRMO office
                ->where('role', 'Employee') // Duplicate role
                ->first();

            if ($juanDuplicate) {
                $this->command->info("Removing duplicate assignment ID: {$juanDuplicate->id}");
                $juanDuplicate->delete();
            } else {
                $this->command->warn('Juan Dela Cruz duplicate assignment not found - may have been already cleaned');
            }

            // Fix 2: Link existing assignments to proper employee records
            $this->command->info('Linking assignments to employee records...');

            // Fix Assessor assignment (ID 37)
            $assessorAssignment = OfficeAssignment::find(37);
            $assessorUser = User::where('email', 'assessor.pmt@dasol.gov.ph')->first();

            if ($assessorAssignment && $assessorUser && !$assessorAssignment->employee_id) {
                // Look for existing employee with matching email
                $assessorEmployee = Employee::where('email', $assessorUser->email)->first();

                if ($assessorEmployee) {
                    $assessorAssignment->update(['employee_id' => $assessorEmployee->id]);
                    $this->command->info("Linked Assessor assignment to employee ID: {$assessorEmployee->id}");
                } else {
                    $this->command->warn('Assessor employee record not found - keeping assignment without employee link');
                }
            }

            // Fix Final Approver assignment (ID 66)
            $finalApproverAssignment = OfficeAssignment::find(66);
            $finalApproverUser = User::where('email', 'administrator@dasol.gov.ph')->first();

            if ($finalApproverAssignment && $finalApproverUser && !$finalApproverAssignment->employee_id) {
                // Look for existing employee with matching email or create minimal record
                $finalApproverEmployee = Employee::where('email', $finalApproverUser->email)
                    ->first();

                if ($finalApproverEmployee) {
                    $finalApproverAssignment->update(['employee_id' => $finalApproverEmployee->id]);
                    $this->command->info("Linked Final Approver assignment to employee ID: {$finalApproverEmployee->id}");
                } else {
                    $this->command->warn('Final Approver employee record not found - keeping assignment without employee link');
                }
            }

            // Fix 3: Ensure Juan Dela Cruz remaining assignment (ID 92) is correctly linked
            $this->command->info('Verifying Juan Dela Cruz remaining assignment...');

            $juanAssignment = OfficeAssignment::find(92);
            if ($juanAssignment && $juanAssignment->user_id == 2) {
                $juanUser = User::find(2);
                if ($juanUser && $juanUser->employee && $juanAssignment->employee_id != $juanUser->employee->id) {
                    $juanAssignment->update(['employee_id' => $juanUser->employee->id]);
                    $this->command->info("Updated Juan Dela Cruz assignment with correct employee ID: {$juanUser->employee->id}");
                }
            }

            // Fix 4: Verify Super Admin assignment (ID 5) is correctly marked as system account
            $this->command->info('Verifying Super Admin system assignment...');

            $superAdminAssignment = OfficeAssignment::find(5);
            if ($superAdminAssignment && $superAdminAssignment->employee_id === null) {
                $this->command->info('Super Admin assignment correctly has no employee_id (system account)');
            }

            DB::commit();

            $this->command->info('✅ Office assignment data cleanup completed successfully!');

            // Summary of changes
            $this->displayCleanupSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Data cleanup failed: ' . $e->getMessage());
            Log::error('OfficeAssignmentDataCleanupSeeder failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Display summary of cleanup operations
     */
    private function displayCleanupSummary(): void
    {
        $this->command->info("\n📊 Cleanup Summary:");

        // Check current state of office 5 assignments
        $assignments = OfficeAssignment::where('office_id', 5)
            ->with(['user', 'employee'])
            ->orderBy('role')
            ->get();

        $this->command->info("Office 5 (HRMO) assignments after cleanup: {$assignments->count()} total");

        foreach ($assignments as $assignment) {
            $employeeName = $assignment->employee ? $assignment->employee->full_name : 'No Employee';
            $userName = $assignment->user->name;
            $employeeId = $assignment->employee_id ?? 'NULL';
            $systemFlag = $assignment->employee_id === null ? ' (System Account)' : '';

            $this->command->info("  - {$userName} → {$employeeName} (ID: {$employeeId}) - Role: {$assignment->role}{$systemFlag}");
        }

        // Check for any remaining data quality issues
        $nullEmployeeAssignments = OfficeAssignment::whereNull('employee_id')
            ->whereHas('user', function($query) {
                $query->where('email', '!=', 'admin@example.com'); // Exclude legitimate Super Admin
            })
            ->count();

        if ($nullEmployeeAssignments > 0) {
            $this->command->warn("⚠️  Warning: {$nullEmployeeAssignments} assignments still have NULL employee_id");
        } else {
            $this->command->info("✅ No assignments with missing employee data (excluding system accounts)");
        }

        // Check for duplicate user assignments
        $duplicateUsers = OfficeAssignment::select('user_id', 'office_id')
            ->where('is_active', true)
            ->whereNotNull('employee_id')
            ->groupBy('user_id', 'office_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateUsers > 0) {
            $this->command->warn("⚠️  Warning: {$duplicateUsers} users have multiple active assignments in the same office");
        } else {
            $this->command->info("✅ No duplicate user assignments found");
        }
    }
}