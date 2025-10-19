<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

class LeaveTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing leave types
        DB::table('leave_types')->delete();

        $leaveTypes = [
            [
                'name' => 'Vacation Leave',
                'code' => 'VL',
                'description' => 'Leave for vacation and personal matters',
                'max_days_per_year' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'description' => 'Leave for medical reasons',
                'max_days_per_year' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'ML',
                'description' => 'Leave for female employees due to childbirth',
                'max_days_per_year' => 105,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PL',
                'description' => 'Leave for male employees due to childbirth',
                'max_days_per_year' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Special Leave Benefits for Women',
                'code' => 'SLBW',
                'description' => 'Leave for female employees for gynecological procedures',
                'max_days_per_year' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Solo Parent Leave',
                'code' => 'SPL',
                'description' => 'Leave for solo parent employees',
                'max_days_per_year' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Emergency Leave',
                'code' => 'EL',
                'description' => 'Leave for emergency cases',
                'max_days_per_year' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Leave Without Pay',
                'code' => 'LWOP',
                'description' => 'Leave without compensation',
                'max_days_per_year' => 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        LeaveType::insert($leaveTypes);
    }
}