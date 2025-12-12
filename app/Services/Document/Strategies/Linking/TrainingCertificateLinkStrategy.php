<?php

namespace App\Services\Document\Strategies\Linking;

use App\Models\EmployeeDocument;
use Illuminate\Support\Str;

class TrainingCertificateLinkStrategy implements LinkStrategyInterface
{
    public function supports(EmployeeDocument $document): bool
    {
        $keywords = ['training', 'certificate', 'seminar', 'workshop', 'course', 'certification'];
        return Str::contains(strtolower($document->file_name), $keywords);
    }

    public function findTargets(EmployeeDocument $document): array
    {
        // Currently no Training Record model exists to link to.
        // Returning empty array until Training Module is implemented.
        return [];
    }

    public function getLinkType(): string
    {
        return 'training_certificate';
    }
}
