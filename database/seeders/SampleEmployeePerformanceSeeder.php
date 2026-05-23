<?php

namespace Database\Seeders;

use App\Models\CalibrationSession;
use App\Models\CalibrationSessionItem;
use App\Models\Employee;
use App\Models\FinalRating;
use App\Models\Ipcr;
use App\Models\IpcrItem;
use App\Models\IpcrRating;
use App\Models\IpcrWorkflowLog;
use App\Models\IpcrProgressUpdate;
use App\Models\IpcrCoachingSession;
use App\Models\IpcrDevelopmentAction;
use App\Models\Office;
use App\Models\OpcrIpcrMapping;
use App\Models\OPCRWorkflow;
use App\Models\PerformanceLink;
use App\Models\PerformancePeriod;
use App\Models\PerformanceEvaluation;
use App\Models\PerformanceTarget;
use App\Models\PerformanceRating;
use App\Models\PmtValidation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SampleEmployeePerformanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Retrieve essential records
        $employee = Employee::where('email', 'employee@example.com')->firstOrFail();
        $supervisor = Employee::where('email', 'supervisor@example.com')->firstOrFail();
        
        // Find suitable reviewers/approvers from already seeded data
        $headOfOffice = Employee::where('email', 'clarissa.villanueva@dasol.gov.ph')->first()
            ?? Employee::where('position', 'like', '%Head%')->first()
            ?? $supervisor;
            
        $pmtMember = Employee::where('email', 'rodel.aquino@dasol.gov.ph')->first()
            ?? Employee::where('position', 'like', '%PMT%')->first()
            ?? $supervisor;
            
        $finalApprover = Employee::where('email', 'lucia.garcia@dasol.gov.ph')->first()
            ?? Employee::where('position', 'like', '%Mayor%')->first()
            ?? $supervisor;

        $office = $employee->office ?? Office::where('code', 'HRMO')->first() ?? Office::first();

        // 1b. Clean up existing seeded records for our test employees & offices to ensure idempotency
        $cleanEmployeeIds = [$employee->id, $supervisor->id, $headOfOffice->id];
        
        $forceDelete = function($query) {
            $model = $query->getModel();
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
                $query->withTrashed()->forceDelete();
            } else {
                $query->delete();
            }
        };

        $forceDelete(PerformanceEvaluation::whereIn('employee_id', $cleanEmployeeIds));
        
        $ipcrIds = Ipcr::withTrashed()->whereIn('employee_id', $cleanEmployeeIds)->pluck('id')->toArray();
        if (!empty($ipcrIds)) {
            $forceDelete(IpcrProgressUpdate::whereIn('ipcr_id', $ipcrIds));
            $forceDelete(IpcrRating::whereIn('ipcr_id', $ipcrIds));
            $forceDelete(IpcrWorkflowLog::whereIn('ipcr_id', $ipcrIds));
            $forceDelete(IpcrDevelopmentAction::whereIn('ipcr_id', $ipcrIds));
            $forceDelete(IpcrCoachingSession::whereIn('ipcr_id', $ipcrIds));
            $forceDelete(OpcrIpcrMapping::whereIn('ipcr_id', $ipcrIds));
            $forceDelete(IpcrItem::whereIn('ipcr_id', $ipcrIds));
            $forceDelete(FinalRating::whereIn('ipcr_id', $ipcrIds));
            $forceDelete(Ipcr::whereIn('id', $ipcrIds));
        }

        // Delete targets and ratings
        $targetIds = PerformanceTarget::whereIn('employee_id', $cleanEmployeeIds)->pluck('id')->toArray();
        if (!empty($targetIds)) {
            $forceDelete(PerformanceRating::whereIn('target_id', $targetIds));
            $forceDelete(PerformanceTarget::whereIn('id', $targetIds));
        }

        // Delete seeded OPCR workflows and targets for our office and other seeded offices in CY 2026/2025
        $period2026 = PerformancePeriod::where('name', 'CY 2026')->first();
        $period2025 = PerformancePeriod::where('name', 'CY 2025')->first();
        $periodIds = collect([$period2025?->id, $period2026?->id])->filter()->toArray();

        if (!empty($periodIds)) {
            $opcrWorkflowIds = OPCRWorkflow::withTrashed()->whereIn('period_id', $periodIds)
                ->pluck('id')
                ->toArray();
            
            if (!empty($opcrWorkflowIds)) {
                $forceDelete(PerformanceRating::whereHas('target', function($q) use ($opcrWorkflowIds) {
                    $q->whereIn('opcr_workflow_id', $opcrWorkflowIds);
                }));
                
                $forceDelete(PerformanceTarget::whereIn('opcr_workflow_id', $opcrWorkflowIds));
                $forceDelete(OPCRWorkflow::whereIn('id', $opcrWorkflowIds));
            }
        }

        // 2. Assign IPCR Roles if not already assigned
        $this->safeAssignRole($employee, 'IPCR Employee');
        $this->safeAssignRole($supervisor, 'IPCR Supervisor');

        // 3. Ensure Performance Periods exist
        $period2025 = PerformancePeriod::where('name', 'CY 2025')->first();
        if (!$period2025) {
            $period2025 = PerformancePeriod::create([
                'name' => 'CY 2025',
                'year' => 2025,
                'semester' => 'Annual',
                'start_date' => Carbon::create(2025, 1, 1),
                'end_date' => Carbon::create(2025, 12, 31),
                'status' => 'active',
                'is_active' => false,
            ]);
        } else {
            $period2025->update(['is_active' => false]);
        }

        $period2026 = PerformancePeriod::firstOrCreate(
            ['name' => 'CY 2026'],
            [
                'year' => 2026,
                'semester' => 'Annual',
                'start_date' => Carbon::create(2026, 1, 1),
                'end_date' => Carbon::create(2026, 12, 31),
                'status' => 'active',
                'is_active' => true,
                'is_opcr_period' => true,
            ]
        );
        // Make sure CY 2026 is the ONLY active period
        PerformancePeriod::where('id', '!=', $period2026->id)->update(['is_active' => false]);

        $opcr2025 = OPCRWorkflow::create([
            'office_id' => $office->id,
            'period_id' => $period2025->id,
            'workflow_state' => 'final_approval',
            'title' => "Annual Performance Commitment Review - {$office->name}",
            'overall_rating' => 4.5,
            'overall_adjectival_rating' => 'Outstanding',
            'committed_by' => $headOfOffice->user->id ?? 1,
            'committed_at' => Carbon::create(2025, 1, 15),
        ]);

        $opcr2026 = OPCRWorkflow::create([
            'office_id' => $office->id,
            'period_id' => $period2026->id,
            'workflow_state' => 'in_progress',
            'title' => "Annual Performance Commitment Review - {$office->name} (CY 2026)",
            'committed_by' => $headOfOffice->user->id ?? 1,
            'committed_at' => Carbon::create(2026, 1, 15),
        ]);

        // Seed MFO targets for OPCR workflows
        $officeTargets2025 = [];
        $officeTargets2026 = [];
        
        $sampleObjectives = [
            'Implement Municipal HR Information System (HRIS) updates',
            'Conduct annual performance validation and calibration sessions',
            'Process employee leave applications and credit accruals',
            'Facilitate capacity building and professional training programs'
        ];

        for ($i = 0; $i < 4; $i++) {
            $officeTargets2025[] = PerformanceTarget::create([
                'employee_id' => $headOfOffice->id,
                'period_id' => $period2025->id,
                'office_id' => $office->id,
                'opcr_workflow_id' => $opcr2025->id,
                'objective' => $sampleObjectives[$i],
                'target' => "100% of target metrics achieved for item " . ($i + 1),
                'weight' => 25.00,
                'success_indicator' => "Successful implementation of item " . ($i + 1),
                'target_quality' => 5.00,
                'target_efficiency' => 5.00,
                'target_timeliness' => 5.00,
            ]);

            $officeTargets2026[] = PerformanceTarget::create([
                'employee_id' => $headOfOffice->id,
                'period_id' => $period2026->id,
                'office_id' => $office->id,
                'opcr_workflow_id' => $opcr2026->id,
                'objective' => $sampleObjectives[$i],
                'target' => "100% of target metrics achieved for item " . ($i + 1),
                'weight' => 25.00,
                'success_indicator' => "Successful implementation of item " . ($i + 1),
                'target_quality' => 5.00,
                'target_efficiency' => 5.00,
                'target_timeliness' => 5.00,
            ]);
        }

        // 4. Update employee direct rating attributes
        $employee->update([
            'latest_performance_rating' => 4.50
        ]);

        // 5. Create finalized PerformanceEvaluation for CY 2025 (ESS Dashboard rating source)
        PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'evaluation_period' => 'CY 2025',
            'evaluation_date' => Carbon::create(2025, 12, 15),
            'period_start' => Carbon::create(2025, 1, 1),
            'period_end' => Carbon::create(2025, 12, 31),
            'overall_rating' => 4.50,
            'quality_rating' => 4.50,
            'efficiency_rating' => 4.40,
            'timeliness_rating' => 4.60,
            'initiative_rating' => 4.50,
            'teamwork_rating' => 4.50,
            'leadership_rating' => 4.50,
            'goal_achievement_percentage' => 100.00,
            'achievements' => 'Maintained accurate HR logs and assisted with digital transformation of leave credits.',
            'areas_for_improvement' => 'Improve speed of document verification.',
            'goals_next_period' => 'Complete documentation requests automation and targets completion.',
            'evaluator_comments' => 'Outstanding contribution and diligence.',
            'employee_comments' => 'Happy to help digitize municipal workflows.',
            'promotion_readiness' => true,
            'leadership_potential' => true,
            'evaluation_status' => 'final',
            'evaluator_id' => $supervisor->id,
            'approved_by' => $headOfOffice->id,
            'submitted_at' => Carbon::create(2025, 12, 10, 9, 0, 0),
            'approved_at' => Carbon::create(2025, 12, 15, 14, 0, 0),
        ]);

        // 6. Create finalized IPCR workflow for CY 2025
        $ipcr2025 = Ipcr::create([
            'employee_id' => $employee->id,
            'office_id' => $office->id,
            'period_id' => $period2025->id,
            'opcr_workflow_id' => $opcr2025->id,
            'supervisor_id' => $supervisor->id,
            'head_of_office_id' => $headOfOffice->id,
            'pmt_validator_id' => $pmtMember->id,
            'final_approver_id' => $finalApprover->id,
            'status' => 'finalized',
            'total_weight' => 100.00,
            'overall_score' => 4.50,
            'adjectival_rating' => 'Outstanding',
            'is_auto_generated' => true,
            'submitted_at' => Carbon::create(2025, 12, 5),
            'supervisor_reviewed_at' => Carbon::create(2025, 12, 8),
            'head_reviewed_at' => Carbon::create(2025, 12, 10),
            'pmt_validated_at' => Carbon::create(2025, 12, 12),
            'finalized_at' => Carbon::create(2025, 12, 15),
            'locked_at' => Carbon::create(2025, 12, 16),
            'remarks' => 'Completed all personal tasks for 2025 successfully.',
        ]);

        // Add 2025 IPCR Items and ratings
        foreach ($officeTargets2025 as $index => $officeTarget) {
            $item = IpcrItem::create([
                'ipcr_id' => $ipcr2025->id,
                'performance_target_id' => $officeTarget->id,
                'title' => $officeTarget->objective,
                'description' => $officeTarget->target,
                'weight' => 25.00,
                'measure' => $officeTarget->success_indicator,
                'target_quality' => 5.00,
                'target_efficiency' => 5.00,
                'target_timeliness' => 5.00,
                'accomplished_quality' => 4.50,
                'accomplished_efficiency' => 4.50,
                'accomplished_timeliness' => 4.50,
                'self_rating' => 4.50,
                'supervisor_rating' => 4.50,
                'head_rating' => 4.50,
                'pmt_rating' => 4.50,
                'final_rating' => 4.50,
                'remarks' => 'Completed 2025 deliverables successfully.',
                'sequence' => $index + 1,
            ]);

            // Add ratings
            IpcrRating::create([
                'ipcr_id' => $ipcr2025->id,
                'ipcr_item_id' => $item->id,
                'rater_user_id' => $employee->user->id,
                'rater_employee_id' => $employee->id,
                'rater_role' => 'employee',
                'rating_type' => 'self',
                'overall_rating' => 4.50,
                'rated_at' => Carbon::create(2025, 12, 5),
            ]);

            IpcrRating::create([
                'ipcr_id' => $ipcr2025->id,
                'ipcr_item_id' => $item->id,
                'rater_user_id' => $supervisor->user->id,
                'rater_employee_id' => $supervisor->id,
                'rater_role' => 'supervisor',
                'rating_type' => 'supervisor',
                'overall_rating' => 4.50,
                'rated_at' => Carbon::create(2025, 12, 8),
            ]);
            
            OpcrIpcrMapping::create([
                'opcr_workflow_id' => $opcr2025->id,
                'performance_target_id' => $officeTarget->id,
                'ipcr_id' => $ipcr2025->id,
                'ipcr_item_id' => $item->id,
                'weight_percentage' => 25.00,
                'cascade_level' => 'office-to-individual',
                'allocation_strategy' => 'proportional',
            ]);

            // Seed Progress Update for this item
            IpcrProgressUpdate::create([
                'ipcr_id' => $ipcr2025->id,
                'ipcr_item_id' => $item->id,
                'reported_by' => $employee->user->id,
                'progress_date' => Carbon::create(2025, 6, 15 + ($index * 10)),
                'status' => 'on_track',
                'accomplishments' => "Successfully resolved initial setup phase and prepared test scenarios for: {$officeTarget->objective}.",
                'challenges' => "Faced minor delay due to staff alignment, resolved through weekly briefings.",
                'next_steps' => "Proceed with the secondary calibration review cycle.",
            ]);
        }

        FinalRating::create([
            'ipcr_id' => $ipcr2025->id,
            'employee_id' => $employee->id,
            'validated_by' => $finalApprover->user->id,
            'final_score' => 4.50,
            'adjectival_rating' => 'Outstanding',
            'performance_level' => 'Level 5',
            'is_locked' => true,
            'locked_at' => Carbon::create(2025, 12, 16),
            'remarks' => 'Outstanding finalized score for CY 2025.',
        ]);

        // Seed Workflow History for CY 2025
        IpcrWorkflowLog::create([
            'ipcr_id' => $ipcr2025->id,
            'user_id' => $employee->user->id,
            'employee_id' => $employee->id,
            'from_state' => null,
            'to_state' => 'draft',
            'action' => 'create',
            'performed_role' => 'employee',
            'remarks' => 'IPCR draft auto-generated.',
            'performed_at' => Carbon::create(2025, 12, 1, 9, 0, 0),
        ]);

        IpcrWorkflowLog::create([
            'ipcr_id' => $ipcr2025->id,
            'user_id' => $employee->user->id,
            'employee_id' => $employee->id,
            'from_state' => 'draft',
            'to_state' => 'for_supervisor_review',
            'action' => 'submit',
            'performed_role' => 'employee',
            'remarks' => 'Submitted my accomplishments for CY 2025.',
            'performed_at' => Carbon::create(2025, 12, 5, 10, 30, 0),
        ]);

        IpcrWorkflowLog::create([
            'ipcr_id' => $ipcr2025->id,
            'user_id' => $supervisor->user->id,
            'employee_id' => $supervisor->id,
            'from_state' => 'for_supervisor_review',
            'to_state' => 'supervisor_approved',
            'action' => 'approve',
            'performed_role' => 'supervisor',
            'remarks' => 'Satisfactory target accomplishments. Recommended for final approval.',
            'performed_at' => Carbon::create(2025, 12, 8, 14, 15, 0),
        ]);

        IpcrWorkflowLog::create([
            'ipcr_id' => $ipcr2025->id,
            'user_id' => $headOfOffice->user->id,
            'employee_id' => $headOfOffice->id,
            'from_state' => 'supervisor_approved',
            'to_state' => 'final_approved',
            'action' => 'finalize',
            'performed_role' => 'head_of_office',
            'remarks' => 'Approved overall rating of 4.50 (Outstanding).',
            'performed_at' => Carbon::create(2025, 12, 15, 16, 0, 0),
        ]);

        // Seed Development Actions for CY 2025
        IpcrDevelopmentAction::create([
            'ipcr_id' => $ipcr2025->id,
            'focus_area' => 'HRIS & Software Systems',
            'action_item' => 'Attend municipal ICT capacity-building workshops and technical systems review',
            'target_date' => Carbon::create(2025, 6, 30),
            'status' => 'completed',
            'support_needed' => 'Sponsorship for Laravel/PHP database administration training',
            'created_by' => $employee->user->id,
        ]);

        IpcrDevelopmentAction::create([
            'ipcr_id' => $ipcr2025->id,
            'focus_area' => 'Leave Credit Calculations & Audit',
            'action_item' => 'Review Civil Service Commission (CSC) Omnishield guidelines for leave accruals',
            'target_date' => Carbon::create(2025, 9, 30),
            'status' => 'completed',
            'support_needed' => 'Mentorship from Senior HR officer',
            'created_by' => $employee->user->id,
        ]);

        // Seed Coaching Sessions for CY 2025
        IpcrCoachingSession::create([
            'ipcr_id' => $ipcr2025->id,
            'coach_user_id' => $supervisor->user->id,
            'participant_user_id' => $employee->user->id,
            'session_date' => Carbon::create(2025, 7, 15),
            'session_type' => 'coaching',
            'focus_area' => 'Technical alignment on HRIS updates',
            'discussion_notes' => 'Discussed progress of the new database integrations. Addressed performance roadblocks and aligned on database schema optimization.',
            'agreements' => 'Juan will draft the API documentation and complete the test cases by next week.',
            'follow_up_date' => Carbon::create(2025, 7, 30),
        ]);

        IpcrCoachingSession::create([
            'ipcr_id' => $ipcr2025->id,
            'coach_user_id' => $supervisor->user->id,
            'participant_user_id' => $employee->user->id,
            'session_date' => Carbon::create(2025, 10, 10),
            'session_type' => 'performance_review',
            'focus_area' => 'Leave processing efficiency',
            'discussion_notes' => 'Reviewed target response times for leave approvals. Overall speed has improved by 20% compared to last quarter.',
            'agreements' => 'Maintain current speed and begin drafting training guides for office staff.',
            'follow_up_date' => Carbon::create(2025, 10, 24),
        ]);

        // 7. Seed active CY 2026 targets and ratings for Juan Dela Cruz (dashboard display source)
        $targets2026 = [];
        for ($i = 0; $i < 4; $i++) {
            $targets2026[] = PerformanceTarget::create([
                'employee_id' => $employee->id, // Assigned directly to Juan Dela Cruz so it shows on his ESS dashboard
                'period_id' => $period2026->id,
                'office_id' => $office->id,
                'opcr_workflow_id' => $opcr2026->id,
                'objective' => $sampleObjectives[$i],
                'target' => "100% of daily tasks processed for " . $sampleObjectives[$i],
                'weight' => 25.00,
                'success_indicator' => "Accurate processing of " . $sampleObjectives[$i],
                'target_quality' => 5.00,
                'target_efficiency' => 5.00,
                'target_timeliness' => 5.00,
                'is_target_met' => ($i < 3) ? true : false, // 3 of 4 targets completed
            ]);
        }

        // To make the targets count as "completed" on the dashboard,
        // they must either have is_target_met = true, or have a matching PerformanceRating.
        // Let's also create PerformanceRating records for the first 3 targets to guarantee they register as completed!
        for ($i = 0; $i < 3; $i++) {
            PerformanceRating::create([
                'target_id' => $targets2026[$i]->id,
                'success_indicator_id' => null,
                'self_rating' => 4.50,
                'supervisor_rating' => 4.50,
                'final_rating' => 4.50,
                'average_qet_rating' => 4.50,
                'remarks' => 'Completed target successfully.',
                'office_id' => $office->id,
                'accomplished_quality' => 4.50,
                'accomplished_efficiency' => 4.50,
                'accomplished_timeliness' => 4.50,
                'assessed_by' => $supervisor->user->id,
                'assessed_at' => Carbon::now()->subDays(5),
                'approved_by' => $headOfOffice->user->id,
                'approved_at' => Carbon::now()->subDays(4),
            ]);
        }

        // 8. Create draft/active IPCR for CY 2026
        $ipcr2026 = Ipcr::create([
            'employee_id' => $employee->id,
            'office_id' => $office->id,
            'period_id' => $period2026->id,
            'opcr_workflow_id' => $opcr2026->id,
            'supervisor_id' => $supervisor->id,
            'head_of_office_id' => $headOfOffice->id,
            'status' => 'draft',
            'total_weight' => 100.00,
            'is_auto_generated' => true,
        ]);

        foreach ($officeTargets2026 as $index => $officeTarget) {
            $item = IpcrItem::create([
                'ipcr_id' => $ipcr2026->id,
                'performance_target_id' => $officeTarget->id,
                'title' => $officeTarget->objective,
                'description' => $officeTarget->target,
                'weight' => 25.00,
                'measure' => $officeTarget->success_indicator,
                'target_quality' => 5.00,
                'target_efficiency' => 5.00,
                'target_timeliness' => 5.00,
                'sequence' => $index + 1,
            ]);

            OpcrIpcrMapping::create([
                'opcr_workflow_id' => $opcr2026->id,
                'performance_target_id' => $officeTarget->id,
                'ipcr_id' => $ipcr2026->id,
                'ipcr_item_id' => $item->id,
                'weight_percentage' => 25.00,
                'cascade_level' => 'office-to-individual',
                'allocation_strategy' => 'proportional',
            ]);

            // Seed a progress update for the active CY 2026 target
            IpcrProgressUpdate::create([
                'ipcr_id' => $ipcr2026->id,
                'ipcr_item_id' => $item->id,
                'reported_by' => $employee->user->id,
                'progress_date' => Carbon::now()->subDays(15 - ($index * 3)),
                'status' => ($index == 3) ? 'at_risk' : 'on_track',
                'accomplishments' => "Initial drafts and requirements gathering completed for: {$officeTarget->objective}.",
                'challenges' => ($index == 3) ? "Awaiting training module feedback from regional department." : "None.",
                'next_steps' => "Begin code review and developer validation phase.",
            ]);
        }

        // Seed Workflow History for CY 2026 (draft)
        IpcrWorkflowLog::create([
            'ipcr_id' => $ipcr2026->id,
            'user_id' => $employee->user->id,
            'employee_id' => $employee->id,
            'from_state' => null,
            'to_state' => 'draft',
            'action' => 'create',
            'performed_role' => 'employee',
            'remarks' => 'IPCR draft auto-generated.',
            'performed_at' => Carbon::now()->subDays(30),
        ]);

        // Seed Development Actions for CY 2026
        IpcrDevelopmentAction::create([
            'ipcr_id' => $ipcr2026->id,
            'focus_area' => 'Advanced Database Administration',
            'action_item' => 'Gain deep knowledge of PostgreSQL performance tuning and migrations',
            'target_date' => Carbon::now()->addMonths(6),
            'status' => 'planned',
            'support_needed' => 'Allocate 2 hours/week for online training resources.',
            'created_by' => $employee->user->id,
        ]);

        // Seed Coaching Sessions for CY 2026
        IpcrCoachingSession::create([
            'ipcr_id' => $ipcr2026->id,
            'coach_user_id' => $supervisor->user->id,
            'participant_user_id' => $employee->user->id,
            'session_date' => Carbon::now()->subDays(10),
            'session_type' => 'coaching',
            'focus_area' => 'CY 2026 IPCR target definition',
            'discussion_notes' => 'Aligned on weights and deliverables for the new calendar year. Supervisor suggested focus on technical calibration.',
            'agreements' => 'Employee will set up local development and test databases before seeding.',
            'follow_up_date' => Carbon::now()->addDays(20),
        ]);

        // 9. Seed additional OPCR workflows for CY 2026 across other offices to populate dashboard metrics
        $otherOffices = Office::where('is_active', true)
            ->where('id', '!=', $office->id)
            ->get();

        $workflowStates = ['draft', 'committed', 'in_progress', 'evaluation', 'final_approval'];
        $adjectivalRatings = ['Outstanding', 'Very Satisfactory', 'Satisfactory'];
        $defaultUser = User::where('email', 'admin@example.com')->value('id') ?? User::first()->id;

        foreach ($otherOffices as $index => $otherOffice) {
            // Seed for 12 offices to keep it balanced
            if ($index >= 12) {
                break;
            }

            // Cycle through workflow states
            $state = $workflowStates[$index % count($workflowStates)];
            $overallRating = null;
            $adjectivalRating = null;

            if ($state === 'final_approval') {
                $overallRating = round(4.0 + fmod($index * 0.15, 0.8), 2); // Ratings between 4.0 and 4.8
                $adjectivalRating = $adjectivalRatings[$index % count($adjectivalRatings)];
            }

            OPCRWorkflow::firstOrCreate(
                [
                    'office_id' => $otherOffice->id,
                    'period_id' => $period2026->id,
                ],
                [
                    'workflow_state' => $state,
                    'title' => "Annual Performance Commitment Review - {$otherOffice->name} (CY 2026)",
                    'overall_rating' => $overallRating,
                    'overall_adjectival_rating' => $adjectivalRating,
                    'committed_by' => $defaultUser,
                    'committed_at' => Carbon::now()->subDays(20 + $index),
                ]
            );
        }

        // 10. Clear application and dashboard cache
        try {
            \Illuminate\Support\Facades\Cache::flush();
        } catch (\Exception $e) {
            // Ignore cache flushing errors
        }
    }

    /**
     * Safely assign roles to an employee's user account with validation
     */
    private function safeAssignRole(Employee $employee, string|array $roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if (!$employee->user) {
                continue;
            }

            if (!\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
                continue;
            }

            if (!$employee->user->hasRole($role)) {
                $employee->user->assignRole($role);
            }
        }
    }
}
