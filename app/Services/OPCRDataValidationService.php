<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\PerformanceTarget;
use App\Models\PerformanceRating;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OPCRDataValidationService
{
    /**
     * Validate workflow state transition
     */
    public function validateWorkflowStateTransition(OPCRWorkflow $workflow, string $newState): array
    {
        $validTransitions = [
            'draft' => ['committed', 'returned'],
            'committed' => ['in_progress', 'returned'],
            'in_progress' => ['evaluation', 'returned'],
            'evaluation' => ['final_approval', 'returned'],
            'final_approval' => ['approved', 'returned'],
            'returned' => ['draft', 'committed', 'in_progress', 'evaluation'],
            'approved' => [], // Terminal state
        ];

        $currentState = $workflow->workflow_state;

        if (!isset($validTransitions[$currentState])) {
            throw new \InvalidArgumentException("Invalid current workflow state: {$currentState}");
        }

        if (!in_array($newState, $validTransitions[$currentState])) {
            throw new ValidationException(
                Validator::make([], [], [
                    'transition' => "Invalid state transition from {$currentState} to {$newState}. " .
                    "Valid transitions from {$currentState} are: " . implode(', ', $validTransitions[$currentState])
                ])
            );
        }

        return [
            'valid' => true,
            'current_state' => $currentState,
            'new_state' => $newState,
            'message' => "Valid transition from {$currentState} to {$newState}"
        ];
    }

    /**
     * Validate QET rating calculation
     */
    public function validateQETRating(float $quantity, float $efficiency, float $timeliness): array
    {
        $errors = [];

        // Check individual rating bounds
        if ($quantity < 0 || $quantity > 5) {
            $errors[] = 'Quantity rating must be between 0 and 5';
        }

        if ($efficiency < 0 || $efficiency > 5) {
            $errors[] = 'Efficiency rating must be between 0 and 5';
        }

        if ($timeliness < 0 || $timeliness > 5) {
            $errors[] = 'Timeliness rating must be between 0 and 5';
        }

        // Check for rating format
        if (!$this->isValidRatingFormat($quantity)) {
            $errors[] = 'Quantity rating must have maximum 2 decimal places';
        }

        if (!$this->isValidRatingFormat($efficiency)) {
            $errors[] = 'Efficiency rating must have maximum 2 decimal places';
        }

        if (!$this->isValidRatingFormat($timeliness)) {
            $errors[] = 'Timeliness rating must have maximum 2 decimal places';
        }

        // Calculate and validate final rating
        $finalRating = ($quantity + $efficiency + $timeliness) / 3;

        if (!empty($errors)) {
            throw new ValidationException(
                Validator::make([], [], [
                    'rating_validation' => implode('; ', $errors)
                ])
            );
        }

        return [
            'valid' => true,
            'quantity_rating' => $quantity,
            'efficiency_rating' => $efficiency,
            'timeliness_rating' => $timeliness,
            'final_rating' => round($finalRating, 2),
            'adjectival_rating' => $this->getAdjectivalRating($finalRating),
        ];
    }

    /**
     * Validate target completion logic
     */
    public function validateTargetCompletion(PerformanceTarget $target, array $accomplishment): array
    {
        $errors = [];

        // Validate accomplished quantity against target
        if (isset($target->target_quantity) && isset($accomplishment['quantity'])) {
            if ($accomplishment['quantity'] < 0) {
                $errors[] = 'Accomplished quantity cannot be negative';
            }

            // Check for unreasonable over-achievement (more than 500% of target)
            if ($target->target_quantity > 0 && $accomplishment['quantity'] > ($target->target_quantity * 5)) {
                $errors[] = 'Accomplished quantity is unreasonably high compared to target';
            }
        }

        // Validate efficiency description
        if (isset($accomplishment['efficiency']) && strlen(trim($accomplishment['efficiency'])) < 5) {
            $errors[] = 'Efficiency description must be at least 5 characters long';
        }

        // Validate timeliness description
        if (isset($accomplishment['timeliness']) && strlen(trim($accomplishment['timeliness'])) < 5) {
            $errors[] = 'Timeliness description must be at least 5 characters long';
        }

        // Validate ratings if provided
        if (isset($accomplishment['ratings'])) {
            try {
                $this->validateQETRating(
                    $accomplishment['ratings']['quantity'] ?? 0,
                    $accomplishment['ratings']['efficiency'] ?? 0,
                    $accomplishment['ratings']['timeliness'] ?? 0
                );
            } catch (ValidationException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                Validator::make([], [], [
                    'target_completion' => implode('; ', $errors)
                ])
            );
        }

        return [
            'valid' => true,
            'target_id' => $target->id,
            'accomplishment' => $accomplishment,
            'completion_percentage' => $this->calculateCompletionPercentage($target, $accomplishment),
        ];
    }

    /**
     * Validate office assignment constraints
     */
    public function validateOfficeAssignment(array $assignmentData): array
    {
        $errors = [];

        // Validate required fields
        if (empty($assignmentData['user_id'])) {
            $errors[] = 'User ID is required';
        }

        if (empty($assignmentData['office_id'])) {
            $errors[] = 'Office ID is required';
        }

        if (empty($assignmentData['role'])) {
            $errors[] = 'Role is required';
        }

        // Validate role
        $validRoles = ['Department Head', 'Assessor', 'Final Approver'];
        if (!empty($assignmentData['role']) && !in_array($assignmentData['role'], $validRoles)) {
            $errors[] = 'Invalid role. Must be one of: ' . implode(', ', $validRoles);
        }

        // Check for duplicate assignments
        if (!empty($assignmentData['user_id']) && !empty($assignmentData['office_id']) && !empty($assignmentData['role'])) {
            $existingAssignment = \App\Models\OfficeAssignment::where('user_id', $assignmentData['user_id'])
                ->where('office_id', $assignmentData['office_id'])
                ->where('role', $assignmentData['role'])
                ->where('is_active', true)
                ->first();

            if ($existingAssignment) {
                $errors[] = 'User already has an active assignment for this office and role';
            }
        }

        // Validate that user exists and is active
        if (!empty($assignmentData['user_id'])) {
            $user = \App\Models\User::find($assignmentData['user_id']);
            if (!$user) {
                $errors[] = 'User not found';
            } elseif (!$user->isActive()) {
                $errors[] = 'User account is not active';
            }
        }

        // Validate that office exists and is active
        if (!empty($assignmentData['office_id'])) {
            $office = \App\Models\Office::find($assignmentData['office_id']);
            if (!$office) {
                $errors[] = 'Office not found';
            } elseif (!$office->is_active) {
                $errors[] = 'Office is not active';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                Validator::make([], [], [
                    'office_assignment' => implode('; ', $errors)
                ])
            );
        }

        return [
            'valid' => true,
            'assignment_data' => $assignmentData,
            'message' => 'Office assignment is valid',
        ];
    }

    /**
     * Validate performance period constraints
     */
    public function validatePerformancePeriod(array $periodData): array
    {
        $errors = [];

        // Validate required fields
        if (empty($periodData['name'])) {
            $errors[] = 'Period name is required';
        }

        if (empty($periodData['start_date'])) {
            $errors[] = 'Start date is required';
        }

        if (empty($periodData['end_date'])) {
            $errors[] = 'End date is required';
        }

        // Validate date logic
        if (!empty($periodData['start_date']) && !empty($periodData['end_date'])) {
            $startDate = \Carbon\Carbon::parse($periodData['start_date']);
            $endDate = \Carbon\Carbon::parse($periodData['end_date']);

            if ($startDate >= $endDate) {
                $errors[] = 'Start date must be before end date';
            }

            // Check for period duration (minimum 30 days, maximum 365 days)
            $duration = $startDate->diffInDays($endDate);
            if ($duration < 30) {
                $errors[] = 'Performance period must be at least 30 days long';
            }
            if ($duration > 365) {
                $errors[] = 'Performance period cannot exceed 365 days';
            }

            // Check for overlapping periods
            $overlappingPeriod = \App\Models\PerformancePeriod::where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })->when(!empty($periodData['id']), function ($query) use ($periodData) {
                $query->where('id', '!=', $periodData['id']);
            })->first();

            if ($overlappingPeriod) {
                $errors[] = 'Performance period overlaps with existing period: ' . $overlappingPeriod->name;
            }
        }

        // Validate only one active period
        if (!empty($periodData['is_active']) && $periodData['is_active']) {
            $existingActivePeriod = \App\Models\PerformancePeriod::where('is_active', true)
                ->when(!empty($periodData['id']), function ($query) use ($periodData) {
                    $query->where('id', '!=', $periodData['id']);
                })->first();

            if ($existingActivePeriod) {
                $errors[] = 'Only one performance period can be active at a time';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                Validator::make([], [], [
                    'performance_period' => implode('; ', $errors)
                ])
            );
        }

        return [
            'valid' => true,
            'period_data' => $periodData,
            'message' => 'Performance period is valid',
        ];
    }

    /**
     * Validate MFO and Success Indicator relationship
     */
    public function validateMFOSIRelationship(int $mfoId, int $siId): array
    {
        $errors = [];

        // Check if MFO exists
        $mfo = \App\Models\MajorFinalOutput::find($mfoId);
        if (!$mfo) {
            $errors[] = 'Major Final Output not found';
        }

        // Check if Success Indicator exists
        $si = \App\Models\SuccessIndicator::find($siId);
        if (!$si) {
            $errors[] = 'Success Indicator not found';
        }

        // Check if Success Indicator belongs to MFO
        if ($mfo && $si && $si->mfo_id !== $mfoId) {
            $errors[] = 'Success Indicator does not belong to the specified Major Final Output';
        }

        if (!empty($errors)) {
            throw new ValidationException(
                Validator::make([], [], [
                    'mfo_si_relationship' => implode('; ', $errors)
                ])
            );
        }

        return [
            'valid' => true,
            'mfo_id' => $mfoId,
            'si_id' => $siId,
            'mfo' => $mfo,
            'success_indicator' => $si,
        ];
    }

    /**
     * Helper methods
     */
    private function isValidRatingFormat(float $rating): bool
    {
        $ratingStr = (string) $rating;
        return preg_match('/^\d+(\.\d{1,2})?$/', $ratingStr) === 1;
    }

    private function getAdjectivalRating(float $score): string
    {
        if ($score >= 4.5) return 'Outstanding';
        if ($score >= 3.5) return 'Very Satisfactory';
        if ($score >= 2.5) return 'Satisfactory';
        if ($score >= 1.5) return 'Fairly Satisfactory';
        if ($score >= 0.5) return 'Poor';
        return 'No Rating';
    }

    private function calculateCompletionPercentage(PerformanceTarget $target, array $accomplishment): float
    {
        if (!isset($target->target_quantity) || $target->target_quantity == 0) {
            return 0;
        }

        $accomplishedQuantity = $accomplishment['quantity'] ?? 0;
        return min(($accomplishedQuantity / $target->target_quantity) * 100, 999.99);
    }
}