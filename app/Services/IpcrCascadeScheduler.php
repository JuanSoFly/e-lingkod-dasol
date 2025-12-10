<?php

namespace App\Services;

use App\Events\OpcrWorkflowApproved;
use App\Models\OPCRWorkflow;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class IpcrCascadeScheduler
{
    /**
     * Reconcile OPCR workflows that still need IPCR cascades.
     *
     * @return array{count:int,processed:array<int>}
     */
    public function reconcile(?int $officeId = null, ?int $workflowId = null, bool $force = false): array
    {
        $query = OPCRWorkflow::query()->where('workflow_state', OPCRWorkflow::STATE_FINAL_APPROVAL);

        if ($officeId) {
            $query->where('office_id', $officeId);
        }

        if ($workflowId) {
            $query->whereKey($workflowId);
        }

        /** @var Collection<int, OPCRWorkflow> $workflows */
        $workflows = $query->get();
        $processed = [];

        foreach ($workflows as $workflow) {
            $metadata = $workflow->metadata ?? [];
            $alreadyCascaded = Arr::get($metadata, 'ipcr_cascade_at');

            if (!$force && $alreadyCascaded) {
                continue;
            }

            event(new OpcrWorkflowApproved($workflow, null, true));
            $processed[] = $workflow->id;
        }

        return [
            'count' => count($processed),
            'processed' => $processed,
        ];
    }
}

