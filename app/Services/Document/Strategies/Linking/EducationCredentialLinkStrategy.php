<?php

namespace App\Services\Document\Strategies\Linking;

use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use Illuminate\Support\Str;

class EducationCredentialLinkStrategy implements LinkStrategyInterface
{
    public function supports(EmployeeDocument $document): bool
    {
        $keywords = ['diploma', 'degree', 'transcript', 'tor', 'graduate'];
        return Str::contains(strtolower($document->file_name), $keywords);
    }

    public function findTargets(EmployeeDocument $document): array
    {
        $targets = [];
        // Education records are usually static, so we link all belonging to the employee
        // with a moderate confidence, relying on manual verification for specific mapping
        // if multiple degrees exist.
        
        $educationRecords = EmployeeEducation::where('employee_id', $document->employee_id)->get();

        foreach ($educationRecords as $education) {
            $targets[] = [
                'model' => $education,
                'confidence' => 0.7, // Moderate confidence as it's hard to match specific degree by filename alone
                'criteria' => ['employee_education_match']
            ];
        }

        return $targets;
    }

    public function getLinkType(): string
    {
        return 'education_credential';
    }
}
