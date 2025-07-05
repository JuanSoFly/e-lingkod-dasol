<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up()
    {
        // Create base permissions if they don't exist
        $basePermissions = [
            'user.manage',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete',
            'leave.view', 'leave.create', 'leave.approve', 'leave.reject',
            'performance.view', 'performance.create', 'performance.evaluate',
            'reports.view', 'reports.generate', 'reports.export',
            'document-approval.view', 'document-approval.create', 'document-approval.edit', 'document-approval.delete', 'document-approval.approve',
        ];
        
        foreach ($basePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Create new granular permissions
        $granularPermissions = [
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
        
        foreach ($granularPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Update Employee role with granular permissions (create if doesn't exist)
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $employeeRole->syncPermissions([
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
    }
    
    public function down()
    {
        // Remove granular permissions
        $granularPermissions = [
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
        
        foreach ($granularPermissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
        
        // Restore original Employee role permissions
        $employeeRole = Role::where('name', 'Employee')->first();
        if ($employeeRole) {
            $employeeRole->syncPermissions([
                'leave.view',
                'leave.create',
                'performance.view',
                'performance.create',
                'document-approval.create',
            ]);
        }
    }
};