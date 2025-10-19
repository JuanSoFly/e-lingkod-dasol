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
            'leave.view', 'leave.create', 'leave.approve', 'leave.reject',
            'performance.view', 'performance.create', 'performance.evaluate',
            'reports.view', 'reports.generate', 'reports.export',
            'document-approval.view', 'document-approval.create', 'document-approval.edit', 'document-approval.delete', 'document-approval.approve',
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
        ]);

        $deptHeadRole = Role::firstOrCreate(['name' => 'Department Head']);
        $deptHeadRole->syncPermissions([
            'employee.view',
            'leave.view',
            'leave.approve',
            'leave.reject',
            'performance.view',
            'performance.evaluate',
            'reports.view',
            'document-approval.view',
            'document-approval.approve',
        ]);

        $hrAdminRole = Role::firstOrCreate(['name' => 'HR Admin']);
        $hrAdminRole->syncPermissions([
            'user.manage',
            'employee.manage',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete',
            'leave.view', 'leave.approve', 'leave.reject',
            'performance.view', 'performance.create', 'performance.evaluate',
            'reports.view', 'reports.generate', 'reports.export',
            'document-approval.view', 'document-approval.create', 'document-approval.edit', 'document-approval.delete', 'document-approval.approve',
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(Permission::all());
    }
}