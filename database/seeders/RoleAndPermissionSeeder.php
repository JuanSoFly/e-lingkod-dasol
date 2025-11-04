<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'user.manage',
            'employee.manage',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete',
            'employee.restore', 'employee.force-delete', 'employee.manage-archive',
            'leave.view', 'leave.create', 'leave.approve', 'leave.reject',
            'performance.view', 'performance.create', 'performance.evaluate',
            'performance-period.view', 'performance-period.create', 'performance-period.edit', 'performance-period.delete', 'performance-period.manage',
            'reports.view', 'reports.generate', 'reports.export',
            'document-approval.view', 'document-approval.create', 'document-approval.edit', 'document-approval.delete', 'document-approval.approve',
            'opcr.view', 'opcr.create', 'opcr.edit', 'opcr.commit', 'opcr.submit', 'opcr.assess', 'opcr.approve', 'opcr.manage', 'opcr.return',
            'opcr.export', 'opcr.analytics', 'opcr.settings', 'opcr.admin',
            // Office Assignment permissions
            'opcr.assignments.view', 'opcr.assignments.create', 'opcr.assignments.edit', 'opcr.assignments.delete',
            'opcr.assignments.manage', 'opcr.assignments.view-own', 'opcr.assignments.edit-own',
            'mfo.view', 'mfo.create', 'mfo.edit', 'mfo.delete',
            'si.view', 'si.create', 'si.edit', 'si.delete',
            // IPCR permissions
            'ipcr.view', 'ipcr.view-own', 'ipcr.create', 'ipcr.edit', 'ipcr.submit',
            'ipcr.review', 'ipcr.approve', 'ipcr.validate', 'ipcr.finalize',
            'ipcr.manage', 'ipcr.cascade', 'ipcr.analytics',
            'ipcr.attachments.manage', 'ipcr.adjustments.manage',
            // Granular permissions for employees
            'employee.view-own',
            'leave.view-own',
            'performance.view-own',
            'document-approval.view-own',
            'profile.edit-own',
            'documents.upload-own',
            'notifications.manage-own',
            'reports.view-own',
            'attendance.view-own',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign permissions
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $employeeRole->syncPermissions([
            // Granular employee permissions - can only access own data
            'employee.view-own',
            'leave.view-own',
            'leave.create',
            'performance.view-own',
            'performance.create',
            'document-approval.view-own',
            'document-approval.create',
            'profile.edit-own',
            'documents.upload-own',
            'notifications.manage-own',
            'ipcr.view-own',
            'ipcr.create',
            'ipcr.edit',
            'ipcr.submit',
            'ipcr.attachments.manage',
        ]);

        $deptHeadRole = Role::firstOrCreate(['name' => 'Department Head']);
        $deptHeadRole->syncPermissions([
            'employee.view',
            'leave.view',
            'leave.create',
            'leave.view-own',
            'leave.approve',
            'leave.reject',
            'performance.view',
            'performance.evaluate',
            'performance-period.view',
            'reports.view',
            'document-approval.view',
            'document-approval.approve',
            'opcr.view',
            'opcr.create',
            'opcr.edit',
            'opcr.commit',
            'opcr.submit',
            'opcr.settings',
            'mfo.view',
            'mfo.create',
            'mfo.edit',
            'si.view',
            'si.create',
            'si.edit',
            'ipcr.view',
            'ipcr.approve',
            'ipcr.review',
            'ipcr.attachments.manage',
            'ipcr.adjustments.manage',
            // Office Assignment permissions for Department Heads
            'opcr.assignments.view-own',
            'opcr.assignments.edit-own',
        ]);

        $hrAdminRole = Role::firstOrCreate(['name' => 'HR Admin']);
        $hrAdminRole->syncPermissions([
            'user.manage',
            'employee.manage',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete',
            'employee.restore', 'employee.manage-archive',
            'leave.view', 'leave.approve', 'leave.reject',
            'performance.view', 'performance.create', 'performance.evaluate',
            'performance-period.view', 'performance-period.create', 'performance-period.edit', 'performance-period.delete', 'performance-period.manage',
            'reports.view', 'reports.generate', 'reports.export',
            'document-approval.view', 'document-approval.create', 'document-approval.edit', 'document-approval.delete', 'document-approval.approve',
            'opcr.view', 'opcr.create', 'opcr.edit', 'opcr.submit', 'opcr.assess', 'opcr.approve', 'opcr.manage',
            'opcr.export', 'opcr.analytics', 'opcr.settings', 'opcr.admin',
            'si.view', 'si.create', 'si.edit', 'si.delete',
            'ipcr.view', 'ipcr.create', 'ipcr.edit', 'ipcr.submit', 'ipcr.review', 'ipcr.approve', 'ipcr.validate',
            'ipcr.finalize', 'ipcr.manage', 'ipcr.cascade', 'ipcr.analytics',
            'ipcr.attachments.manage', 'ipcr.adjustments.manage',
            // Full Office Assignment permissions for HR Admin
            'opcr.assignments.view', 'opcr.assignments.create', 'opcr.assignments.edit', 'opcr.assignments.delete',
            'opcr.assignments.manage',
        ]);

        $assessorRole = Role::firstOrCreate(['name' => 'Assessor']);
        $assessorRole->syncPermissions([
            'opcr.view',
            'opcr.assess',
            'opcr.return',
            'ipcr.view',
            'ipcr.validate',
            'ipcr.analytics',
        ]);

        $finalApproverRole = Role::firstOrCreate(['name' => 'Final Approver']);
        $finalApproverRole->syncPermissions([
            'opcr.view',
            'opcr.approve',
            'opcr.return',
            'ipcr.view',
            'ipcr.finalize',
            'ipcr.manage',
        ]);

        $ipcrEmployeeRole = Role::firstOrCreate(['name' => 'IPCR Employee']);
        $ipcrEmployeeRole->syncPermissions([
            'ipcr.view-own',
            'ipcr.create',
            'ipcr.edit',
            'ipcr.submit',
            'ipcr.attachments.manage',
        ]);

        $ipcrSupervisorRole = Role::firstOrCreate(['name' => 'IPCR Supervisor']);
        $ipcrSupervisorRole->syncPermissions([
            'ipcr.view',
            'ipcr.review',
            'ipcr.attachments.manage',
            'ipcr.adjustments.manage',
        ]);

        $headOfOfficeRole = Role::firstOrCreate(['name' => 'Head of Office']);
        $headOfOfficeRole->syncPermissions([
            'ipcr.view',
            'ipcr.review',
            'ipcr.approve',
            'ipcr.attachments.manage',
            'ipcr.adjustments.manage',
        ]);

        $pmtMemberRole = Role::firstOrCreate(['name' => 'PMT Member']);
        $pmtMemberRole->syncPermissions([
            'ipcr.view',
            'ipcr.validate',
            'ipcr.analytics',
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(Permission::all());
    }
}
