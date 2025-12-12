<?php

namespace App\Services\Document\Strategies\Linking;

use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Model;

interface LinkStrategyInterface
{
    /**
     * Determine if this strategy applies to the given document.
     */
    public function supports(EmployeeDocument $document): bool;

    /**
     * Find potential target models to link to.
     * Returns an array of ['model' => Model, 'confidence' => float, 'criteria' => array]
     */
    public function findTargets(EmployeeDocument $document): array;

    /**
     * Get the type of link this strategy creates (e.g., 'leave_supporting_doc')
     */
    public function getLinkType(): string;
}
