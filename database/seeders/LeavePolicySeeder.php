<?php

namespace Database\Seeders;

use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LeavePolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get leave types (assuming they exist)
        $vacationLeave = LeaveType::where('name', 'Vacation Leave')->first();
        $sickLeave = LeaveType::where('name', 'Sick Leave')->first();
        $maternityLeave = LeaveType::where('name', 'Maternity Leave')->first();
        $paternityLeave = LeaveType::where('name', 'Paternity Leave')->first();
        $emergencyLeave = LeaveType::where('name', 'Special Emergency (Calamity) Leave')->first();

        // If leave types don't exist, create them first
        if (!$vacationLeave) {
            $vacationLeave = LeaveType::create([
                'name' => 'Vacation Leave',
                'description' => 'Annual vacation leave for rest and recreation',
                'max_days_per_year' => 15,
                'is_active' => true
            ]);
        }

        if (!$sickLeave) {
            $sickLeave = LeaveType::create([
                'name' => 'Sick Leave',
                'description' => 'Leave for medical treatment and recovery',
                'max_days_per_year' => 15,
                'is_active' => true
            ]);
        }

        if (!$maternityLeave) {
            $maternityLeave = LeaveType::create([
                'name' => 'Maternity Leave',
                'description' => 'Leave for childbirth and maternal care',
                'max_days_per_year' => 105,
                'is_active' => true
            ]);
        }

        if (!$paternityLeave) {
            $paternityLeave = LeaveType::create([
                'name' => 'Paternity Leave',
                'description' => 'Leave for fathers for childbirth support',
                'max_days_per_year' => 7,
                'is_active' => true
            ]);
        }

        if (!$emergencyLeave) {
            $emergencyLeave = LeaveType::create([
                'name' => 'Special Emergency (Calamity) Leave',
                'description' => 'Leave during declared calamities per LGU policy',
                'max_days_per_year' => 5,
                'is_active' => true
            ]);
        }

        // Regular Employee Vacation Leave Policy
        LeavePolicy::create([
            'name' => 'Regular Employee Vacation Leave',
            'description' => 'Standard vacation leave policy for regular government employees',
            'is_active' => true,
            'employment_statuses' => ['regular', 'permanent', 'temporary', 'casual', 'probationary'],
            'positions' => null, // applies to all positions
            'employee_type' => 'government',
            'leave_type_id' => $vacationLeave->id,
            'max_days_per_year' => 15,
            'max_days_per_month' => null,
            'max_consecutive_days' => null,
            'min_days_per_application' => 0.5,
            'minimum_tenure_months' => 6, // 6 months probation
            'requires_medical_certificate' => false,
            'medical_cert_required_days' => null,
            'accrual_method' => 'monthly',
            'monthly_accrual_rate' => 1.25, // 15 days / 12 months
            'allow_prorated_first_year' => true,
            'allow_negative_balance' => false,
            'allow_carryover' => true,
            'max_carryover_days' => null,
            'carryover_expiry_date' => null,
            'min_advance_notice_days' => 3,
            'max_advance_notice_days' => null,
            'blocked_dates' => [
                ['start' => Carbon::now()->year . '-12-20', 'end' => Carbon::now()->year . '-12-31'], // Holiday season
                ['start' => Carbon::now()->year . '-04-01', 'end' => Carbon::now()->year . '-04-15'], // Holy Week period
            ],
            'required_documents' => [],
            'requires_approval' => true,
            'approval_hierarchy' => ['immediate_supervisor', 'department_head'],
            'auto_approve_threshold' => true,
            'auto_approve_days' => 1,
            'is_government_policy' => true,
            'legal_basis' => 'CSC MC No. 41, s. 1998',
            'csc_reportable' => true,
            'gender_restriction' => 'none',
            'effective_start_date' => Carbon::now()->startOfYear(),
            'effective_end_date' => null,
        ]);

        // Contractual Employee Vacation Leave Policy
        LeavePolicy::create([
            'name' => 'Contractual Employee Vacation Leave',
            'description' => 'Limited vacation leave policy for contractual employees',
            'is_active' => true,
            'employment_statuses' => ['contractual'],
            'positions' => null,
            'employee_type' => 'government',
            'leave_type_id' => $vacationLeave->id,
            'max_days_per_year' => 5,
            'max_days_per_month' => null,
            'max_consecutive_days' => 3,
            'min_days_per_application' => 1,
            'minimum_tenure_months' => 3,
            'requires_medical_certificate' => false,
            'medical_cert_required_days' => null,
            'accrual_method' => 'quarterly',
            'monthly_accrual_rate' => null,
            'allow_prorated_first_year' => true,
            'allow_negative_balance' => false,
            'allow_carryover' => false,
            'max_carryover_days' => null,
            'carryover_expiry_date' => null,
            'min_advance_notice_days' => 7,
            'max_advance_notice_days' => null,
            'blocked_dates' => null,
            'required_documents' => [],
            'requires_approval' => true,
            'approval_hierarchy' => ['immediate_supervisor', 'hr'],
            'auto_approve_threshold' => false,
            'auto_approve_days' => null,
            'is_government_policy' => true,
            'legal_basis' => 'Contract Terms',
            'csc_reportable' => false,
            'gender_restriction' => 'none',
            'effective_start_date' => Carbon::now()->startOfYear(),
            'effective_end_date' => null,
        ]);

        // Regular Employee Sick Leave Policy
        LeavePolicy::create([
            'name' => 'Regular Employee Sick Leave',
            'description' => 'Standard sick leave policy for regular government employees',
            'is_active' => true,
            'employment_statuses' => ['regular', 'permanent', 'temporary', 'casual', 'probationary'],
            'positions' => null,
            'employee_type' => 'government',
            'leave_type_id' => $sickLeave->id,
            'max_days_per_year' => 15,
            'max_days_per_month' => null,
            'max_consecutive_days' => null,
            'min_days_per_application' => 0.5,
            'minimum_tenure_months' => 6,
            'requires_medical_certificate' => true,
            'medical_cert_required_days' => 3,
            'accrual_method' => 'monthly',
            'monthly_accrual_rate' => 1.25,
            'allow_prorated_first_year' => true,
            'allow_negative_balance' => false,
            'allow_carryover' => true,
            'max_carryover_days' => null,
            'carryover_expiry_date' => null,
            'min_advance_notice_days' => 0, // can be immediate for emergencies
            'max_advance_notice_days' => null,
            'blocked_dates' => null,
            'required_documents' => ['medical_certificate'],
            'requires_approval' => true,
            'approval_hierarchy' => ['immediate_supervisor'],
            'auto_approve_threshold' => true,
            'auto_approve_days' => 1,
            'is_government_policy' => true,
            'legal_basis' => 'CSC MC No. 41, s. 1998',
            'csc_reportable' => true,
            'gender_restriction' => 'none',
            'effective_start_date' => Carbon::now()->startOfYear(),
            'effective_end_date' => null,
        ]);

        // Maternity Leave Policy
        LeavePolicy::create([
            'name' => 'Maternity Leave',
            'description' => 'Maternity leave for female employees under RA 11210',
            'is_active' => true,
            'employment_statuses' => ['regular', 'contractual'],
            'positions' => null,
            'employee_type' => 'government',
            'leave_type_id' => $maternityLeave->id,
            'max_days_per_year' => 105, // 15 weeks
            'max_days_per_month' => null,
            'max_consecutive_days' => null,
            'min_days_per_application' => 105,
            'minimum_tenure_months' => 0,
            'requires_medical_certificate' => true,
            'medical_cert_required_days' => 1,
            'accrual_method' => 'on_hire',
            'monthly_accrual_rate' => null,
            'allow_prorated_first_year' => false,
            'allow_negative_balance' => false,
            'allow_carryover' => false,
            'max_carryover_days' => null,
            'carryover_expiry_date' => null,
            'min_advance_notice_days' => 30,
            'max_advance_notice_days' => null,
            'blocked_dates' => null,
            'required_documents' => ['medical_certificate', 'pregnancy_test'],
            'requires_approval' => true,
            'approval_hierarchy' => ['immediate_supervisor', 'hr'],
            'auto_approve_threshold' => false,
            'auto_approve_days' => null,
            'is_government_policy' => true,
            'legal_basis' => 'RA 11210 (105-Day Expanded Maternity Leave)',
            'csc_reportable' => true,
            'gender_restriction' => 'female',
            'effective_start_date' => Carbon::now()->startOfYear(),
            'effective_end_date' => null,
        ]);

        // Paternity Leave Policy
        LeavePolicy::create([
            'name' => 'Paternity Leave',
            'description' => 'Paternity leave for male employees under RA 8972',
            'is_active' => true,
            'employment_statuses' => ['regular', 'contractual'],
            'positions' => null,
            'employee_type' => 'government',
            'leave_type_id' => $paternityLeave->id,
            'max_days_per_year' => 7,
            'max_days_per_month' => null,
            'max_consecutive_days' => 7,
            'min_days_per_application' => 7,
            'minimum_tenure_months' => 0,
            'requires_medical_certificate' => false,
            'medical_cert_required_days' => null,
            'accrual_method' => 'on_hire',
            'monthly_accrual_rate' => null,
            'allow_prorated_first_year' => false,
            'allow_negative_balance' => false,
            'allow_carryover' => false,
            'max_carryover_days' => null,
            'carryover_expiry_date' => null,
            'min_advance_notice_days' => 7,
            'max_advance_notice_days' => null,
            'blocked_dates' => null,
            'required_documents' => ['birth_certificate'],
            'requires_approval' => true,
            'approval_hierarchy' => ['immediate_supervisor', 'hr'],
            'auto_approve_threshold' => false,
            'auto_approve_days' => null,
            'is_government_policy' => true,
            'legal_basis' => 'RA 8972 (Solo Parents Welfare Act)',
            'csc_reportable' => true,
            'gender_restriction' => 'male',
            'effective_start_date' => Carbon::now()->startOfYear(),
            'effective_end_date' => null,
        ]);

        // Special Emergency (Calamity) Leave Policy
        LeavePolicy::create([
            'name' => 'Special Emergency (Calamity) Leave',
            'description' => 'Leave during declared calamities and special emergency situations',
            'is_active' => true,
            'employment_statuses' => ['regular', 'contractual', 'probationary'],
            'positions' => null,
            'employee_type' => 'government',
            'leave_type_id' => $emergencyLeave->id,
            'max_days_per_year' => 5,
            'max_days_per_month' => null,
            'max_consecutive_days' => 5,
            'min_days_per_application' => 0.5,
            'minimum_tenure_months' => 0,
            'requires_medical_certificate' => false,
            'medical_cert_required_days' => null,
            'accrual_method' => 'annually',
            'monthly_accrual_rate' => null,
            'allow_prorated_first_year' => true,
            'allow_negative_balance' => false,
            'allow_carryover' => false,
            'max_carryover_days' => null,
            'carryover_expiry_date' => null,
            'min_advance_notice_days' => 0, // emergency = no advance notice
            'max_advance_notice_days' => null,
            'blocked_dates' => null,
            'required_documents' => ['calamity_declaration'],
            'requires_approval' => true,
            'approval_hierarchy' => ['immediate_supervisor'],
            'auto_approve_threshold' => false,
            'auto_approve_days' => null,
            'is_government_policy' => true,
            'legal_basis' => 'LGU Policy & CSC Guidelines',
            'csc_reportable' => false,
            'gender_restriction' => 'none',
            'effective_start_date' => Carbon::now()->startOfYear(),
            'effective_end_date' => null,
        ]);
    }
}