<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert OPCR-specific permissions
        $permissions = [
            // OPCR Workflow Management
            ['name' => 'opcr.create', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.edit', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.delete', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.view_own', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.view_assigned', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.view_team', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // OPCR Workflow Actions
            ['name' => 'opcr.commit', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.submit', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.assess', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.approve', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.return', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.escalate', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // OPCR Export and Reports
            ['name' => 'opcr.export', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.export_own', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.export_assigned', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.export_team', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.analytics', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // MFO Management
            ['name' => 'mfo.create', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'mfo.edit', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'mfo.delete', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'mfo.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'mfo.manage_hierarchy', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Success Indicator Management
            ['name' => 'si.create', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'si.edit', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'si.delete', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'si.rate', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'si.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Office Management
            ['name' => 'office.create', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'office.edit', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'office.delete', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'office.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'office.assign_roles', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'office.manage_structure', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Office Assignment Management
            ['name' => 'assignment.create', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'assignment.edit', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'assignment.delete', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'assignment.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'assignment.manage_roles', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Performance Period Management
            ['name' => 'period.create', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'period.edit', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'period.delete', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'period.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'period.manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Audit and Compliance
            ['name' => 'audit.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'audit.export', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'audit.trail', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'compliance.view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'compliance.reports', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // OPCR System Administration
            ['name' => 'opcr.admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.settings', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.migrate', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.backup', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'opcr.restore', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ];

        // Insert OPCR-specific permissions (if they don't exist)
        foreach ($permissions as $permissionData) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permissionData['name'], 'guard_name' => $permissionData['guard_name']],
                $permissionData
            );
        }

        // Create OPCR-specific roles (if they don't exist)
        $roles = [
            ['name' => 'Department Head', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Assessor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Final Approver', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($roles as $roleData) {
            DB::table('roles')->updateOrInsert(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                $roleData
            );
        }

        // Get the role IDs
        $departmentHeadRole = DB::table('roles')->where('name', 'Department Head')->first();
        $assessorRole = DB::table('roles')->where('name', 'Assessor')->first();
        $finalApproverRole = DB::table('roles')->where('name', 'Final Approver')->first();

        // Assign permissions to Department Head role
        if ($departmentHeadRole) {
            $departmentHeadPermissions = [
                'opcr.create', 'opcr.edit', 'opcr.view_own', 'opcr.commit', 'opcr.submit',
                'opcr.export_own', 'mfo.view', 'si.view', 'si.rate', 'office.view',
                'assignment.view', 'period.view', 'audit.view'
            ];

            foreach ($departmentHeadPermissions as $permissionName) {
                $permission = DB::table('permissions')->where('name', $permissionName)->first();
                if ($permission) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $departmentHeadRole->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }

        // Assign permissions to Assessor role
        if ($assessorRole) {
            $assessorPermissions = [
                'opcr.view_assigned', 'opcr.assess', 'opcr.return', 'opcr.export_assigned',
                'mfo.view', 'si.view', 'si.rate', 'office.view', 'assignment.view',
                'period.view', 'audit.view', 'compliance.view'
            ];

            foreach ($assessorPermissions as $permissionName) {
                $permission = DB::table('permissions')->where('name', $permissionName)->first();
                if ($permission) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $assessorRole->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }

        // Assign permissions to Final Approver role
        if ($finalApproverRole) {
            $finalApproverPermissions = [
                'opcr.view_assigned', 'opcr.approve', 'opcr.return', 'opcr.export_assigned',
                'opcr.analytics', 'mfo.view', 'si.view', 'si.rate', 'office.view',
                'assignment.view', 'period.view', 'audit.view', 'audit.export',
                'compliance.view', 'compliance.reports'
            ];

            foreach ($finalApproverPermissions as $permissionName) {
                $permission = DB::table('permissions')->where('name', $permissionName)->first();
                if ($permission) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $finalApproverRole->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove OPCR-specific permissions
        $opcrPermissions = [
            'opcr.create', 'opcr.edit', 'opcr.delete', 'opcr.view', 'opcr.view_own', 'opcr.view_assigned', 'opcr.view_team',
            'opcr.commit', 'opcr.submit', 'opcr.assess', 'opcr.approve', 'opcr.return', 'opcr.escalate',
            'opcr.export', 'opcr.export_own', 'opcr.export_assigned', 'opcr.export_team', 'opcr.analytics',
            'mfo.create', 'mfo.edit', 'mfo.delete', 'mfo.view', 'mfo.manage_hierarchy',
            'si.create', 'si.edit', 'si.delete', 'si.rate', 'si.view',
            'office.create', 'office.edit', 'office.delete', 'office.view', 'office.assign_roles', 'office.manage_structure',
            'assignment.create', 'assignment.edit', 'assignment.delete', 'assignment.view', 'assignment.manage_roles',
            'period.create', 'period.edit', 'period.delete', 'period.view', 'period.manage',
            'audit.view', 'audit.export', 'audit.trail', 'compliance.view', 'compliance.reports',
            'opcr.admin', 'opcr.settings', 'opcr.migrate', 'opcr.backup', 'opcr.restore'
        ];

        DB::table('permissions')
            ->whereIn('name', $opcrPermissions)
            ->delete();

        // Remove OPCR-specific roles
        DB::table('roles')
            ->whereIn('name', ['Department Head', 'Assessor', 'Final Approver'])
            ->delete();
    }
};
