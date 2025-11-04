<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find the Department Head role
        $departmentHeadRole = Role::where('name', 'Department Head')->first();

        if ($departmentHeadRole) {
            // Find the leave.create permission
            $leaveCreatePermission = Permission::where('name', 'leave.create')->first();

            if ($leaveCreatePermission) {
                // Assign the permission to Department Head role
                $departmentHeadRole->givePermissionTo($leaveCreatePermission);

                // Also add leave.view-own so they can view their own applications
                $leaveViewOwnPermission = Permission::where('name', 'leave.view-own')->first();
                if ($leaveViewOwnPermission) {
                    $departmentHeadRole->givePermissionTo($leaveViewOwnPermission);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Find the Department Head role
        $departmentHeadRole = Role::where('name', 'Department Head')->first();

        if ($departmentHeadRole) {
            // Remove leave.create permission
            $leaveCreatePermission = Permission::where('name', 'leave.create')->first();
            if ($leaveCreatePermission) {
                $departmentHeadRole->revokePermissionTo($leaveCreatePermission);
            }

            // Remove leave.view-own permission
            $leaveViewOwnPermission = Permission::where('name', 'leave.view-own')->first();
            if ($leaveViewOwnPermission) {
                $departmentHeadRole->revokePermissionTo($leaveViewOwnPermission);
            }
        }
    }
};
