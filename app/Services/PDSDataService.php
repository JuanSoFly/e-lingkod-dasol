<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PDSDataService
{
    /**
     * Get completion status for all PDS panels with caching.
     */
    public function getCompletionStatus(Employee $employee): array
    {
        $cacheKey = "pds_completion_{$employee->id}";
        
        return Cache::remember($cacheKey, 60, function () use ($employee) {
            return $this->calculateCompletionStatus($employee);
        });
    }

    /**
     * Calculate completion status without caching.
     */
    public function calculateCompletionStatus(Employee $employee): array
    {
        // Load only necessary relationship counts/existence
        $employee->loadCount([
            'children',
            'pdsEligibilities',
            'workExperiences',
            'voluntaryWork',
            'trainings',
            'otherInformation',
            'references'
        ]);
        
        $employee->load(['familyBackground', 'questionnaire']);

        $panels = [
            'personal_information' => $this->getPersonalInfoCompletionRate($employee),
            'family_background' => $this->getFamilyBackgroundCompletionRate($employee),
            'educational_background' => $this->getEducationCompletionRate($employee),
            'civil_service_eligibility' => $employee->pds_eligibilities_count > 0 ? 100 : 0,
            'work_experience' => $employee->work_experiences_count > 0 ? 100 : 0,
            'voluntary_work' => 100, // Optional
            'learning_development' => 100, // Optional
            'other_information' => 100, // Optional
            'references' => $employee->references_count >= 3 ? 100 : round(($employee->references_count / 3) * 100),
            'questionnaire' => $employee->questionnaire ? $employee->questionnaire->getCompletionPercentage() : 0,
        ];

        $overallCompletion = array_sum($panels) / count($panels);

        return [
            'panels' => $panels,
            'overall_completion' => round($overallCompletion, 2),
            'completed_panels' => count(array_filter($panels, fn($rate) => $rate >= 100)),
            'total_panels' => count($panels),
        ];
    }

    private function getPersonalInfoCompletionRate(Employee $employee): int
    {
        $requiredFields = [
            'first_name', 'last_name', 'birth_date', 'gender', 
            'civil_status', 'citizenship', 'email', 'mobile_no'
        ];
        
        $filled = 0;
        foreach ($requiredFields as $field) {
            if (!empty($employee->$field)) {
                $filled++;
            }
        }
        
        return round(($filled / count($requiredFields)) * 100);
    }

    private function getFamilyBackgroundCompletionRate(Employee $employee): int
    {
        if (!$employee->familyBackground) {
            return 0;
        }
        
        // Basic check: if family background record exists, we consider it started.
        // For distinct fields, we'd need more logic. 
        // Assuming if the record exists it's at least partially done.
        return 100; 
    }

    private function getEducationCompletionRate(Employee $employee): int
    {
         // Assuming logic similar to others, if any education record exists
         return $employee->education()->exists() ? 100 : 0;
    }
    
    /**
     * Clear PDS Cache for an employee.
     */
    public function clearCache(Employee $employee)
    {
        Cache::forget("pds_completion_{$employee->id}");
    }
}
