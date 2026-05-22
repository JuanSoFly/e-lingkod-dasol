<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeAssignmentSeeder extends Seeder
{
    /**
     * Seed office_assignments table with sample assignments
     */
    public function run(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE office_assignments RESTART IDENTITY CASCADE');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            OfficeAssignment::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Get offices that need assignments
        $hrmoOffice = Office::where('code', 'HRMO')->first();
        $mayorOffice = Office::where('code', 'MAYOR')->first();
        $admOffice = Office::where('code', 'ADM')->first();
        $btoOffice = Office::where('code', 'BTO')->first();
        $mtoOffice = Office::where('code', 'MTO')->first();

        if (!$hrmoOffice || !$mayorOffice || !$admOffice || !$btoOffice || !$mtoOffice) {
            $this->command->error('Required offices not found. Please run OfficeSeeder first.');
            return;
        }

        // Get users from DatabaseSeeder
        $superAdmin = User::where('email', 'admin@example.com')->first();
        $hrAdmin = User::where('email', 'hr@example.com')->first();
        $employee = User::where('email', 'employee@example.com')->first();
        $supervisorUser = User::where('email', 'supervisor@example.com')->first();

        // Get OPCR users
        $mayorDepartmentHead = User::where('email', 'depthead.mayor@dasol.gov.ph')->first();
        $assessor = User::where('email', 'assessor.pmt@dasol.gov.ph')->first();
        // Mayor is the sole Final Approver
        $finalApprover = User::where('email', 'mayor@dasol.gov.ph')->first() ?? User::role('Final Approver')->first();
        $planningReviewer = User::where('email', 'hr@example.com')->first();
        $pmtReviewer = User::where('email', 'hr@example.com')->first();

        // Create office assignments
        $assignments = [];

        // Super Admin has access to all offices (for oversight)
        if ($superAdmin) {
            $allOffices = Office::where('is_active', true)->get();
            foreach ($allOffices as $office) {
                $assignments[] = [
                    'user_id' => $superAdmin->id,
                    'employee_id' => $superAdmin->employee_id,
                    'office_id' => $office->id,
                    'role' => 'Super Admin',
                    'is_active' => true,
                    'assigned_date' => now()->toDateString(),
                    'assigned_by' => 1, // Super Admin assigns themselves
                ];
            }
        }

        // HR Admin gets HRMO and oversight of administrative offices
        if ($hrAdmin) {
            $assignments[] = [
                'user_id' => $hrAdmin->id,
                'employee_id' => $hrAdmin->employee_id,
                'office_id' => $hrmoOffice->id,
                'role' => 'Department Head',
                'is_active' => true,
                'assigned_date' => now()->toDateString(),
                'assigned_by' => $superAdmin->id ?? 1,
                'remarks' => 'HR Manager with full access to HRMO functions',
            ];

            // HR Admin also gets oversight of administrative offices
            $admOffice = Office::where('code', 'ADM')->first();
            $adminOffices = Office::where('parent_id', $admOffice->id)->get();
            foreach ($adminOffices as $office) {
                $assignments[] = [
                    'user_id' => $hrAdmin->id,
                    'employee_id' => $hrAdmin->employee_id,
                    'office_id' => $office->id,
                    'role' => 'Assessor',
                    'is_active' => true,
                    'assigned_date' => now()->toDateString(),
                    'assigned_by' => $superAdmin->id ?? 1,
                    'remarks' => 'HR oversight for administrative offices',
                ];
            }
        }

        // Planning reviewer (HR Admin) assigned to all active offices for planning_review state
        if ($planningReviewer) {
            $allOffices = Office::where('is_active', true)->get();
            foreach ($allOffices as $office) {
                $assignments[] = [
                    'user_id' => $planningReviewer->id,
                    'employee_id' => $planningReviewer->employee_id,
                    'office_id' => $office->id,
                    'role' => 'Assessor', // reuse evaluator role for access; permission handles planning review
                    'is_active' => true,
                    'assigned_date' => now()->toDateString(),
                    'assigned_by' => $superAdmin->id ?? 1,
                    'remarks' => 'Planning reviewer access for OPCR planning stage',
                ];
            }
        }

        // PMT reviewer (HR Admin) cross-office
        if ($pmtReviewer) {
            $allOffices = Office::where('is_active', true)->get();
            foreach ($allOffices as $office) {
                $assignments[] = [
                    'user_id' => $pmtReviewer->id,
                    'employee_id' => $pmtReviewer->employee_id,
                    'office_id' => $office->id,
                    'role' => 'Assessor',
                    'is_active' => true,
                    'assigned_date' => now()->toDateString(),
                    'assigned_by' => $superAdmin->id ?? 1,
                    'remarks' => 'PMT reviewer access for OPCR PMT stage',
                ];
            }
        }

        // Employee gets assignment to HRMO (for demo purposes)
        if ($employee) {
            $assignments[] = [
                'user_id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'office_id' => $hrmoOffice->id,
                'role' => 'Employee',
                'is_active' => true,
                'assigned_date' => now()->toDateString(),
                'assigned_by' => $hrAdmin->id ?? 1,
                'remarks' => 'Regular employee assignment to HRMO',
            ];
        }

        // Supervisor for HRMO as immediate approver
        if ($supervisorUser) {
            $assignments[] = [
                'user_id' => $supervisorUser->id,
                'employee_id' => $supervisorUser->employee_id,
                'office_id' => $hrmoOffice->id,
                'role' => 'Supervisor',
                'is_active' => true,
                'assigned_date' => now()->toDateString(),
                'assigned_by' => $hrAdmin->id ?? $superAdmin->id ?? 1,
                'remarks' => 'Immediate supervisor for HRMO leave workflows',
            ];
        }

        // Create sample Department Heads for major offices
        $departmentHeadAssignments = [
            [
                'office_id' => $mayorOffice->id,
                'role' => 'Department Head',
                'remarks' => 'Mayor\'s Office Department Head',
            ],
            [
                'office_id' => $admOffice->id,
                'role' => 'Department Head',
                'remarks' => 'Administrator\'s Office Department Head',
            ],
            [
                'office_id' => $btoOffice->id,
                'role' => 'Department Head',
                'remarks' => 'Budget and Treasury Office Department Head',
            ],
            [
                'office_id' => $mtoOffice->id,
                'role' => 'Department Head',
                'remarks' => 'Engineering Office Department Head',
            ],
        ];

        // Assign specific OPCR users to their roles

        // Department Head for Mayor's Office (exclusive)
        if ($mayorDepartmentHead && $mayorOffice) {
            $assignments[] = [
                'user_id' => $mayorDepartmentHead->id,
                'employee_id' => $mayorDepartmentHead->employee_id,
                'office_id' => $mayorOffice->id,
                'role' => 'Department Head',
                'is_active' => true,
                'assigned_date' => now()->toDateString(),
                'assigned_by' => $superAdmin->id ?? 1,
                'remarks' => 'Department Head for Office of the Municipal Mayor - Exclusive assignment',
            ];
        }

        // Assessor with cross-office evaluation authority
        if ($assessor) {
            $allOffices = Office::where('is_active', true)->get();
            foreach ($allOffices as $office) {
                // Check if this user already has an assignment to this office
                $hasExistingAssignment = collect($assignments)->contains(function ($existing) use ($assessor, $office) {
                    return $existing['user_id'] === $assessor->id &&
                           $existing['office_id'] === $office->id &&
                           $existing['is_active'] === true;
                });
                
                // Only add Assessor assignment if no existing assignment exists
                if (!$hasExistingAssignment) {
                    $assignments[] = [
                        'user_id' => $assessor->id,
                        'employee_id' => $assessor->employee_id,
                        'office_id' => $office->id,
                        'role' => 'Assessor',
                        'is_active' => true,
                        'assigned_date' => now()->toDateString(),
                        'assigned_by' => $superAdmin->id ?? 1,
                        'remarks' => 'Assessor with cross-office evaluation authority',
                    ];
                }
            }
        }

        // Final Approver with organization-wide approval authority
        if ($finalApprover) {
            $allOffices = Office::where('is_active', true)->get();
            foreach ($allOffices as $office) {
                // Check if this user already has an assignment to this office
                $hasExistingAssignment = collect($assignments)->contains(function ($existing) use ($finalApprover, $office) {
                    return $existing['user_id'] === $finalApprover->id &&
                           $existing['office_id'] === $office->id &&
                           $existing['is_active'] === true;
                });
                
                // Only add Final Approver assignment if no existing assignment exists
                if (!$hasExistingAssignment) {
                    $assignments[] = [
                        'user_id' => $finalApprover->id,
                        'employee_id' => $finalApprover->employee_id,
                        'office_id' => $office->id,
                        'role' => 'Final Approver',
                        'is_active' => true,
                        'assigned_date' => now()->toDateString(),
                        'assigned_by' => $superAdmin->id ?? 1,
                        'remarks' => 'Final Approver with organization-wide approval authority',
                    ];
                }
            }
        }

        // For remaining department head positions (other than Mayor's Office),
        // assign Super Admin as placeholder since we only want exclusive Department Head for Mayor's Office
        foreach ($departmentHeadAssignments as $assignment) {
            // Skip Mayor's Office since we already assigned the specific Department Head
            if ($assignment['office_id'] === $mayorOffice->id) {
                continue;
            }

            if ($superAdmin) {
                // Check if Super Admin already has an assignment to this office
                $existingAssignment = collect($assignments)->first(function ($existing) use ($superAdmin, $assignment) {
                    return $existing['user_id'] === $superAdmin->id &&
                           $existing['office_id'] === $assignment['office_id'];
                });

                // Only add if no existing assignment
                if (!$existingAssignment) {
                    $assignments[] = array_merge($assignment, [
                        'user_id' => $superAdmin->id,
                        'employee_id' => $superAdmin->employee_id,
                        'is_active' => true,
                        'assigned_date' => now()->toDateString(),
                        'assigned_by' => 1, // Self-assigned for system setup
                        'remarks' => 'Placeholder Department Head - to be assigned to specific user',
                    ]);
                }
            }
        }

        // Insert all assignments with duplicate prevention
        $createdCount = 0;
        foreach ($assignments as $assignment) {
            // Check if an assignment already exists for this user and office
            $existingAssignment = OfficeAssignment::where('user_id', $assignment['user_id'])
                ->where('office_id', $assignment['office_id'])
                ->where('is_active', $assignment['is_active'])
                ->first();
            
            if (!$existingAssignment) {
                // Only create if no existing active assignment exists
                $created = OfficeAssignment::create($assignment);
                if ($created->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        }

        $this->command->info('Office assignments created successfully.');
        $this->command->info('Total assignments processed: ' . count($assignments));
        $this->command->info('New assignments created: ' . $createdCount);

        // Log summary
        $this->command->info('Assignment Summary:');
        $this->command->info('- Super Admin: Access to all offices');
        $this->command->info('- HR Admin: Department Head of HRMO, Assessor of admin offices');
        $this->command->info('- Employee: Assigned to HRMO');
        $this->command->info('- Department Head (Mayor Office): Exclusive assignment to Office of the Municipal Mayor');
        $this->command->info('- Assessor: Cross-office evaluation authority for all offices');
        $this->command->info('- Final Approver: Organization-wide approval authority for all offices');
        $this->command->info('- Department Heads: Super Admin as placeholder for remaining offices');
    }
}
