<?php

namespace App\Services\Document;

use App\Models\DocumentLink;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Services\Document\Strategies\Linking\LinkStrategyInterface;
use Illuminate\Database\Eloquent\Model;

class DocumentLinkingService
{
    protected array $strategies = [];

    public function __construct(iterable $strategies = [])
    {
        foreach ($strategies as $strategy) {
            $this->addStrategy($strategy);
        }
    }

    public function addStrategy(LinkStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function findAndCreateLinks(EmployeeDocument $document, User $creator): array
    {
        $linksCreated = [];

        foreach ($this->strategies as $strategy) {
            if (!$strategy->supports($document)) {
                continue;
            }

            $targets = $strategy->findTargets($document);

            foreach ($targets as $targetInfo) {
                // Determine if manual approval is needed based on confidence
                $requiresApproval = ($targetInfo['confidence'] ?? 0) < 0.9;
                
                $link = DocumentLink::createLink(
                    $document,
                    $targetInfo['model'],
                    $strategy->getLinkType(),
                    $creator,
                    [
                        'is_automatic' => true,
                        'confidence_score' => $targetInfo['confidence'] ?? 0.5,
                        'matching_criteria' => $targetInfo['criteria'] ?? [],
                        'requires_manual_approval' => $requiresApproval,
                        'status' => $requiresApproval ? 'pending_validation' : 'active'
                    ]
                );

                if ($link) {
                    $linksCreated[] = $link;
                }
            }
        }

        return $linksCreated;
    }
}
