<?php

namespace App\Services;

use App\Models\SuccessIndicator;
use App\Models\PerformanceRating;
use App\Models\RatingScale;
use App\Models\Office;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class QETRatingCalculationService
{
    private ?RatingScale $defaultRatingScale = null;

    /**
     * Calculate QET ratings for a success indicator
     */
    public function calculateQETRatings(SuccessIndicator $indicator, array $accomplishments, ?RatingScale $ratingScale = null): array
    {
        $ratingScale = $ratingScale ?? $this->getDefaultRatingScale();

        if (!$ratingScale) {
            throw new \RuntimeException('No rating scale available for calculation');
        }

        $ratings = [
            'quantity_rating' => $this->calculateQuantityRating(
                $indicator->target_quantity,
                $accomplishments['quantity'] ?? null,
                $ratingScale
            ),
            'efficiency_rating' => $this->calculateEfficiencyRating(
                $indicator->target_efficiency,
                $accomplishments['efficiency'] ?? null,
                $ratingScale
            ),
            'timeliness_rating' => $this->calculateTimelinessRating(
                $indicator->target_timeliness,
                $accomplishments['timeliness'] ?? null,
                $ratingScale
            ),
        ];

        // Calculate weighted average using rating scale weights
        $averageRating = $ratingScale->calculateWeightedAverage($ratings);
        $adjectivalRating = $ratingScale->getAdjectivalRating($averageRating);

        return [
            'ratings' => $ratings,
            'average_rating' => $averageRating,
            'adjectival_rating' => $adjectivalRating,
            'qet_weights' => $ratingScale->getQETWeights(),
            'rating_scale_id' => $ratingScale->id,
        ];
    }

    /**
     * Calculate quantity rating (1-5 scale)
     */
    private function calculateQuantityRating(?float $target, ?float $accomplished, RatingScale $ratingScale): int
    {
        if (!$target || $target <= 0) {
            return 3; // Default to satisfactory if no target
        }

        if (!$accomplished) {
            return 1; // Poor if no accomplishment
        }

        $percentage = ($accomplished / $target) * 100;
        $ratingInfo = $ratingScale->getRatingFromPercentage($percentage);

        return $ratingInfo['rating_value'];
    }

    /**
     * Calculate efficiency rating based on quality metrics
     */
    private function calculateEfficiencyRating(?string $target, ?string $accomplished, RatingScale $ratingScale): int
    {
        if (!$target) {
            return 3; // Default to satisfactory
        }

        if (!$accomplished) {
            return 1; // Poor if no accomplishment
        }

        // Parse target and accomplished efficiency
        $targetScore = $this->parseEfficiencyScore($target);
        $accomplishedScore = $this->parseEfficiencyScore($accomplished);

        if ($targetScore === null || $accomplishedScore === null) {
            return 3; // Default if parsing fails
        }

        $percentage = ($accomplishedScore / $targetScore) * 100;
        $ratingInfo = $ratingScale->getRatingFromPercentage($percentage);

        return $ratingInfo['rating_value'];
    }

    /**
     * Calculate timeliness rating based on deadline compliance
     */
    private function calculateTimelinessRating(?string $target, ?string $accomplished, RatingScale $ratingScale): int
    {
        if (!$target) {
            return 3; // Default to satisfactory
        }

        if (!$accomplished) {
            return 1; // Poor if no accomplishment
        }

        // Parse timeliness - can be percentage, descriptive, or date-based
        $targetScore = $this->parseTimelinessScore($target);
        $accomplishedScore = $this->parseTimelinessScore($accomplished);

        if ($targetScore === null || $accomplishedScore === null) {
            return 3; // Default if parsing fails
        }

        $percentage = ($accomplishedScore / $targetScore) * 100;
        $ratingInfo = $ratingScale->getRatingFromPercentage($percentage);

        return $ratingInfo['rating_value'];
    }

    /**
     * Parse efficiency score from various formats
     */
    private function parseEfficiencyScore(string $efficiency): ?float
    {
        // Handle percentage format
        if (preg_match('/(\d+(?:\.\d+)?)%/', $efficiency, $matches)) {
            return (float) $matches[1];
        }

        // Handle descriptive ratings
        $descriptiveScores = [
            'excellent' => 100,
            'very good' => 90,
            'good' => 80,
            'satisfactory' => 75,
            'fair' => 60,
            'poor' => 40,
            'outstanding' => 100,
            'very satisfactory' => 90,
            'satisfactory' => 80,
            'unsatisfactory' => 60,
            'poor' => 40,
        ];

        foreach ($descriptiveScores as $description => $score) {
            if (stripos($efficiency, $description) !== false) {
                return $score;
            }
        }

        // Handle numeric score
        if (is_numeric($efficiency)) {
            return (float) $efficiency;
        }

        return null;
    }

    /**
     * Parse timeliness score from various formats
     */
    private function parseTimelinessScore(string $timeliness): ?float
    {
        // Handle percentage format
        if (preg_match('/(\d+(?:\.\d+)?)%/', $timeliness, $matches)) {
            return (float) $matches[1];
        }

        // Handle descriptive terms
        $descriptiveScores = [
            'on time' => 100,
            'ahead of schedule' => 100,
            'within deadline' => 100,
            'slightly delayed' => 80,
            'delayed' => 60,
            'significantly delayed' => 40,
            'missed deadline' => 20,
        ];

        foreach ($descriptiveScores as $description => $score) {
            if (stripos($timeliness, $description) !== false) {
                return $score;
            }
        }

        // Handle numeric score
        if (is_numeric($timeliness)) {
            return (float) $timeliness;
        }

        return null;
    }

    /**
     * Get default rating scale
     */
    private function getDefaultRatingScale(): ?RatingScale
    {
        if ($this->defaultRatingScale === null) {
            $this->defaultRatingScale = RatingScale::getDefault();
        }

        return $this->defaultRatingScale;
    }

    /**
     * Get rating scale for specific office
     */
    public function getRatingScaleForOffice(Office $office, ?\DateTime $date = null): ?RatingScale
    {
        return RatingScale::getForOffice($office, $date);
    }

    /**
     * Update performance rating with calculated QET values
     */
    public function updatePerformanceRating(PerformanceRating $rating, array $accomplishments, ?RatingScale $ratingScale = null): PerformanceRating
    {
        if (!$rating->target || !$rating->target->successIndicator) {
            throw new \InvalidArgumentException('Performance rating must have a target with success indicator');
        }

        $indicator = $rating->target->successIndicator;
        $qetRatings = $this->calculateQETRatings($indicator, $accomplishments, $ratingScale);

        // Update the rating record
        $rating->update([
            'accomplished_quantity' => $accomplishments['quantity'] ?? null,
            'accomplished_efficiency' => $accomplishments['efficiency'] ?? null,
            'accomplished_timeliness' => $accomplishments['timeliness'] ?? null,
            'rating_quantity' => $qetRatings['ratings']['quantity_rating'],
            'rating_efficiency' => $qetRatings['ratings']['efficiency_rating'],
            'rating_timeliness' => $qetRatings['ratings']['timeliness_rating'],
            'average_qet_rating' => $qetRatings['average_rating'],
            'adjectival_rating' => $qetRatings['adjectival_rating'],
            'rating_scale_id' => $qetRatings['rating_scale_id'],
        ]);

        // Also update the success indicator
        $indicator->update([
            'accomplished_quantity' => $accomplishments['quantity'] ?? null,
            'accomplished_efficiency' => $accomplishments['efficiency'] ?? null,
            'accomplished_timeliness' => $accomplishments['timeliness'] ?? null,
            'rating_quantity' => $qetRatings['ratings']['quantity_rating'],
            'rating_efficiency' => $qetRatings['ratings']['efficiency_rating'],
            'rating_timeliness' => $qetRatings['ratings']['timeliness_rating'],
            'average_rating' => $qetRatings['average_rating'],
            'adjectival_rating' => $qetRatings['adjectival_rating'],
        ]);

        return $rating;
    }

    
    /**
     * Calculate overall OPCR rating from multiple success indicators
     */
    public function calculateOverallOPCRRating(Collection $successIndicators, ?RatingScale $ratingScale = null): array
    {
        if ($successIndicators->isEmpty()) {
            return [
                'overall_rating' => 0,
                'overall_adjectival_rating' => 'Not Rated',
                'rating_summary' => [],
            ];
        }

        $ratingScale = $ratingScale ?? $this->getDefaultRatingScale();
        if (!$ratingScale) {
            throw new \RuntimeException('No rating scale available for calculation');
        }

        $ratings = $successIndicators->filter(function ($si) {
            return $si->average_rating !== null;
        });

        if ($ratings->isEmpty()) {
            return [
                'overall_rating' => 0,
                'overall_adjectival_rating' => 'Not Rated',
                'rating_summary' => [],
            ];
        }

        $totalRating = $ratings->sum('average_rating');
        $count = $ratings->count();
        $overallRating = round($totalRating / $count, 2);
        $overallAdjectivalRating = $ratingScale->getAdjectivalRating($overallRating);

        // Rating distribution
        $ratingDistribution = $ratings->groupBy('adjectival_rating')->map(function ($group) {
            return $group->count();
        });

        return [
            'overall_rating' => $overallRating,
            'overall_adjectival_rating' => $overallAdjectivalRating,
            'total_indicators' => $successIndicators->count(),
            'rated_indicators' => $count,
            'rating_distribution' => $ratingDistribution->toArray(),
            'qet_averages' => [
                'quantity' => round($ratings->avg('rating_quantity'), 2),
                'efficiency' => round($ratings->avg('rating_efficiency'), 2),
                'timeliness' => round($ratings->avg('rating_timeliness'), 2),
            ],
            'rating_scale' => $ratingScale->getConfigurationArray(),
        ];
    }

    /**
     * Validate rating inputs
     */
    public function validateRatingInputs(array $accomplishments): array
    {
        $errors = [];

        if (isset($accomplishments['quantity']) && !is_numeric($accomplishments['quantity'])) {
            $errors['quantity'] = 'Quantity must be a valid number';
        }

        if (isset($accomplishments['efficiency']) && !is_string($accomplishments['efficiency'])) {
            $errors['efficiency'] = 'Efficiency must be a descriptive text or percentage';
        }

        if (isset($accomplishments['timeliness']) && !is_string($accomplishments['timeliness'])) {
            $errors['timeliness'] = 'Timeliness must be a descriptive text';
        }

        return $errors;
    }

    /**
     * Get rating scale for display
     */
    public function getRatingScale(?RatingScale $ratingScale = null): array
    {
        $ratingScale = $ratingScale ?? $this->getDefaultRatingScale();
        return $ratingScale ? $ratingScale->getRatingScaleOptions() : [];
    }

    /**
     * Get QET weights for display
     */
    public function getQETWeights(?RatingScale $ratingScale = null): array
    {
        $ratingScale = $ratingScale ?? $this->getDefaultRatingScale();
        return $ratingScale ? $ratingScale->getQETWeights() : [
            'quantity' => 0.4,
            'efficiency' => 0.3,
            'timeliness' => 0.3,
        ];
    }

    /**
     * Export ratings to array for reporting
     */
    public function exportRatingsForReporting(Collection $successIndicators): array
    {
        return $successIndicators->map(function ($si) {
            return [
                'mfo_code' => $si->mfo->code ?? 'N/A',
                'mfo_title' => $si->mfo->title ?? 'N/A',
                'si_code' => $si->code,
                'si_title' => $si->title,
                'target_quantity' => $si->target_quantity,
                'target_efficiency' => $si->target_efficiency,
                'target_timeliness' => $si->target_timeliness,
                'accomplished_quantity' => $si->accomplished_quantity,
                'accomplished_efficiency' => $si->accomplished_efficiency,
                'accomplished_timeliness' => $si->accomplished_timeliness,
                'quantity_rating' => $si->rating_quantity,
                'efficiency_rating' => $si->rating_efficiency,
                'timeliness_rating' => $si->rating_timeliness,
                'average_rating' => $si->average_rating,
                'adjectival_rating' => $si->adjectival_rating,
                'remarks' => $si->remarks,
            ];
        })->toArray();
    }
}