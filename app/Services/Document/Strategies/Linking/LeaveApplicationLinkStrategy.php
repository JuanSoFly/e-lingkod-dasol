<?php

namespace App\Services\Document\Strategies\Linking;

use App\Models\EmployeeDocument;
use App\Models\LeaveApplication;
use Illuminate\Support\Str;

class LeaveApplicationLinkStrategy implements LinkStrategyInterface
{
    public function supports(EmployeeDocument $document): bool
    {
        // Check if document name suggests it's leave-related
        $keywords = ['leave', 'sick', 'vacation', 'emergency', 'maternity', 'paternity', 'medical'];
        return Str::contains(strtolower($document->file_name), $keywords);
    }

    public function findTargets(EmployeeDocument $document): array
    {
        $targets = [];
        $uploadDate = $document->uploaded_at ?? $document->created_at;
        
        // Find leave applications around the document upload date (+/- 30 days)
        $leaveApplications = LeaveApplication::where('employee_id', $document->employee_id)
            ->whereBetween('start_date', [
                $uploadDate->copy()->subDays(30),
                $uploadDate->copy()->addDays(30)
            ])
            ->get();

        foreach ($leaveApplications as $application) {
            // Calculate confidence based on date proximity
            // Closer date = higher confidence
            $daysDiff = abs($uploadDate->diffInDays($application->start_date));
            $dateConfidence = 1.0 - ($daysDiff / 30);
            
            // Base confidence from filename match
            $baseConfidence = 0.5;
            
            // Boost if "Medical" matches "Sick Leave"
            $docName = strtolower($document->file_name);
            $leaveType = strtolower($application->leaveType->name ?? '');

            if (Str::contains($docName, 'medical') && Str::contains($leaveType, 'sick')) {
                $baseConfidence = 0.8;
            }

            $totalConfidence = ($baseConfidence + $dateConfidence) / 2;

            if ($totalConfidence >= 0.5) {
                $targets[] = [
                    'model' => $application,
                    'confidence' => $totalConfidence,
                    'criteria' => [
                        "filename_match",
                        "date_proximity_{$daysDiff}_days",
                        "confidence_score_{$totalConfidence}"
                    ]
                ];
            }
        }

        return $targets;
    }

    public function getLinkType(): string
    {
        return 'leave_supporting_doc';
    }
}
