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
            // EXISTING LEAVE TYPES (Unchanged)
            [
                'name' => 'Vacation Leave',
                'code' => 'VL',
                'description' => 'Leave for vacation and personal matters (CSC MC No. 41, s. 1998)',
                'max_days_per_year' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'description' => 'Leave for medical reasons (CSC MC No. 41, s. 1998)',
                'max_days_per_year' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Solo Parent Leave',
                'code' => 'SPL',
                'description' => 'Leave for solo parent employees (RA 8972)',
                'max_days_per_year' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'ML',
                'description' => 'Leave for female employees due to childbirth (RA 11210)',
                'max_days_per_year' => 105,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PL',
                'description' => 'Leave for male employees due to childbirth (RA 8187)',
                'max_days_per_year' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // NEW LEAVE TYPES (Philippine Government Standard)
            [
                'name' => 'Special Privilege Leave',
                'code' => 'SPLV',
                'description' => 'Leave for special privileges as per LGU policy and CSC guidelines',
                'max_days_per_year' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mandatory/Forced Leave',
                'code' => 'MFL',
                'description' => 'Required leave before monetization per CSC rules',
                'max_days_per_year' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '10-Day VAWC Leave',
                'code' => 'VAWC',
                'description' => 'Leave under Republic Act 9262 for VAWC victims',
                'max_days_per_year' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Compensatory Time Off',
                'code' => 'CTO',
                'description' => 'Time off in lieu of overtime work rendered',
                'max_days_per_year' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Special Emergency (Calamity) Leave',
                'code' => 'CALAM',
                'description' => 'Leave during declared calamities per LGU policy',
                'max_days_per_year' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        LeaveType::insert($leaveTypes);
    }
}