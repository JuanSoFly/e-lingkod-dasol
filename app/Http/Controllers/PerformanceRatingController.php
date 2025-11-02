<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelfRatingRequest;
use App\Http\Requests\StoreSupervisorRatingRequest;
use App\Models\PerformanceRating;
use App\Models\PerformanceTarget;
use App\Models\OPCRWorkflow;
use App\Models\Office;
use App\Services\QETRatingCalculationService;
use App\Services\OPCRWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceRatingController extends Controller
{
    protected $qetRatingService;
    protected $opcrWorkflowService;

    public function __construct(
        QETRatingCalculationService $qetRatingService,
        OPCRWorkflowService $opcrWorkflowService
    ) {
        $this->qetRatingService = $qetRatingService;
        $this->opcrWorkflowService = $opcrWorkflowService;

        // Performance rating permissions
        $this->middleware('permission:performance.rating.view')->only(['index', 'show', 'analytics']);
        $this->middleware('permission:performance.rating.rate')->only(['storeSelfRating', 'storeQETRating']);
        $this->middleware('permission:performance.rating.evaluate')->only(['storeSupervisorRating', 'storeAssessment']);
        $this->middleware('permission:performance.rating.manage')->only(['bulkRating', 'ratingTemplates']);
    }

    /**
     * Display performance ratings with office-based filtering
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'office_id' => 'nullable|exists:offices,id',
            'period_id' => 'nullable|exists:performance_periods,id',
            'rating_type' => 'nullable|in:self,supervisor,final,adjectival',
            'min_rating' => 'nullable|numeric|min:1|max:5',
            'max_rating' => 'nullable|numeric|min:1|max:5',
            'per_page' => 'nullable|integer|min:10|max:100'
        ]);

        $ratings = $this->qetRatingService->getFilteredRatings($filters);

        return Inertia::render('Admin/PerformanceRatings/Index', [
            'ratings' => $ratings,
            'filters' => $filters,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'ratingScales' => $this->qetRatingService->getRatingScales(),
            'qetCategories' => ['quantity', 'efficiency', 'timeliness']
        ]);
    }

    /**
     * Store self-rating for performance target
     */
    public function storeSelfRating(StoreSelfRatingRequest $request, PerformanceTarget $target)
    {
        $this->authorize('rate', $target);

        // For OPCR workflow, check if user has office assignment
        $officeId = $this->getUserOfficeId($request->user());

        PerformanceRating::updateOrCreate(
            ['target_id' => $target->id],
            [
                'self_rating' => $request->validated('self_rating'),
                'office_id' => $officeId,
                'rated_by' => $request->user()->id
            ]
        );

        return back()->with('success', 'Self-rating submitted successfully.');
    }

    /**
     * Store QET-based ratings for OPCR targets
     */
    public function storeQETRating(Request $request, PerformanceTarget $target): JsonResponse
    {
        $validated = $request->validate([
            'quantity_rating' => 'required|integer|min:1|max:5',
            'efficiency_rating' => 'required|integer|min:1|max:5',
            'timeliness_rating' => 'required|integer|min:1|max:5',
            'quantity_remarks' => 'nullable|string|max:1000',
            'efficiency_remarks' => 'nullable|string|max:1000',
            'timeliness_remarks' => 'nullable|string|max:1000',
            'evidence_attachments' => 'nullable|array',
            'evidence_attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048'
        ]);

        $this->authorize('rate', $target);

        try {
            $ratingData = array_merge($validated, [
                'target_id' => $target->id,
                'office_id' => $this->getUserOfficeId($request->user()),
                'rated_by' => $request->user()->id
            ]);

            $result = $this->qetRatingService->storeQETRating($ratingData);

            return response()->json([
                'success' => true,
                'message' => 'QET rating submitted successfully',
                'adjectival_rating' => $result['adjectival_rating'],
                'rating_id' => $result['rating_id']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit QET rating: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store supervisor/assessor evaluation
     */
    public function storeSupervisorRating(StoreSupervisorRatingRequest $request, PerformanceTarget $target)
    {
        $this->authorize('evaluate', $target);

        // For OPCR, use QET calculation service
        if ($target->opcr_workflow_id) {
            return $this->storeOPCREvaluation($request, $target);
        }

        // Legacy rating calculation
        $selfRating = $target->rating->self_rating;
        $supervisorRating = $request->validated('supervisor_rating');
        $finalRating = round(($selfRating + $supervisorRating) / 2);

        PerformanceRating::updateOrCreate(
            ['target_id' => $target->id],
            [
                'supervisor_rating' => $supervisorRating,
                'final_rating' => $finalRating,
                'office_id' => $this->getUserOfficeId($request->user()),
                'evaluated_by' => $request->user()->id
            ]
        );

        return back()->with('success', 'Supervisor rating submitted successfully.');
    }

    /**
     * Store OPCR evaluation with QET breakdown
     */
    public function storeOPCREvaluation(Request $request, PerformanceTarget $target): JsonResponse
    {
        $validated = $request->validate([
            'quantity_rating' => 'required|integer|min:1|max:5',
            'efficiency_rating' => 'required|integer|min:1|max:5',
            'timeliness_rating' => 'required|integer|min:1|max:5',
            'quantity_comments' => 'nullable|string|max:1000',
            'efficiency_comments' => 'nullable|string|max:1000',
            'timeliness_comments' => 'nullable|string|max:1000',
            'overall_comments' => 'nullable|string|max:2000',
            'strengths' => 'nullable|array',
            'strengths.*' => 'string|max:500',
            'areas_for_improvement' => 'nullable|array',
            'areas_for_improvement.*' => 'string|max:500',
            'recommendations' => 'nullable|string|max:1000',
            'evidence_files' => 'nullable|array',
            'evidence_files.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120'
        ]);

        try {
            $evaluationData = array_merge($validated, [
                'target_id' => $target->id,
                'office_id' => $this->getUserOfficeId($request->user()),
                'evaluator_id' => $request->user()->id,
                'opcr_workflow_id' => $target->opcr_workflow_id
            ]);

            $result = $this->qetRatingService->storeOPCREvaluation($evaluationData);

            // Update workflow state if evaluation is complete
            if ($result['evaluation_complete']) {
                $this->opcrWorkflowService->advanceWorkflowState($target->opcr_workflow, 'evaluation_complete');
            }

            return response()->json([
                'success' => true,
                'message' => 'OPCR evaluation submitted successfully',
                'adjectival_rating' => $result['adjectival_rating'],
                'workflow_state' => $result['workflow_state'],
                'next_actions' => $result['next_actions']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit evaluation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store assessment from PMT/Assessor
     */
    public function storeAssessment(Request $request, PerformanceTarget $target): JsonResponse
    {
        $validated = $request->validate([
            'assessment_type' => 'required|in:intermediate,final',
            'quantity_assessment' => 'required|integer|min:1|max:5',
            'efficiency_assessment' => 'required|integer|min:1|max:5',
            'timeliness_assessment' => 'required|integer|min:1|max:5',
            'assessment_comments' => 'required|string|max:3000',
            'validation_score' => 'nullable|integer|min:1|max:100',
            'recommendations' => 'nullable|string|max:1000',
            'requires_revision' => 'boolean',
            'revision_deadline' => 'nullable|date|after:today'
        ]);

        $this->authorize('assess', $target);

        try {
            $assessmentData = array_merge($validated, [
                'target_id' => $target->id,
                'assessor_id' => $request->user()->id,
                'office_id' => $this->getUserOfficeId($request->user())
            ]);

            $result = $this->qetRatingService->storeAssessorAssessment($assessmentData);

            // Update OPCR workflow based on assessment
            if ($target->opcr_workflow_id) {
                $workflow = OPCRWorkflow::find($target->opcr_workflow_id);
                if ($validated['requires_revision']) {
                    $this->opcrWorkflowService->returnForRevision($workflow, $assessmentData);
                } else {
                    $this->opcrWorkflowService->advanceWorkflowState($workflow, 'assessment_complete');
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Assessment submitted successfully',
                'assessment_id' => $result['assessment_id'],
                'workflow_updates' => $result['workflow_updates']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit assessment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk rating operations for multiple targets
     */
    public function bulkRating(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_ids' => 'required|array',
            'target_ids.*' => 'exists:performance_targets,id',
            'rating_operation' => 'required|in:self_rating,qet_rating,assessment',
            'rating_data' => 'required|array',
            'apply_to_all' => 'boolean'
        ]);

        $this->authorize('bulkRate', PerformanceTarget::class);

        try {
            $result = $this->qetRatingService->performBulkRating($validated);

            return response()->json([
                'success' => true,
                'message' => 'Bulk rating operation completed successfully',
                'processed_count' => $result['processed_count'],
                'failed_count' => $result['failed_count'],
                'errors' => $result['errors']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk rating failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rating analytics and insights
     */
    public function analytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'office_id' => 'nullable|exists:offices,id',
            'period_id' => 'nullable|exists:performance_periods,id',
            'analysis_type' => 'in:distribution,trends,comparisons,quality_metrics',
            'rating_category' => 'in:quantity,efficiency,timeliness,adjectival'
        ]);

        $analytics = $this->qetRatingService->getRatingAnalytics($validated);

        return response()->json([
            'success' => true,
            'analytics' => $analytics
        ]);
    }

    /**
     * Get user's office ID for office-based filtering
     */
    private function getUserOfficeId($user): ?int
    {
        $officeAssignment = $user->officeAssignments()->first();
        return $officeAssignment ? $officeAssignment->office_id : null;
    }

    /**
     * Show rating details with QET breakdown
     */
    public function show(PerformanceRating $rating): Response
    {
        $this->authorize('view', $rating);

        $rating->load([
            'target',
            'target.employee',
            'target.performancePeriod',
            'evaluator',
            'rater'
        ]);

        return Inertia::render('Admin/PerformanceRatings/Show', [
            'rating' => $rating,
            'qetBreakdown' => $this->qetRatingService->getQETBreakdown($rating),
            'ratingHistory' => $this->qetRatingService->getRatingHistory($rating),
            'comparativeData' => $this->qetRatingService->getComparativeRatings($rating)
        ]);
    }
}