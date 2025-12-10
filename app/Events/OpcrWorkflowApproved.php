<?php

namespace App\Events;

use App\Models\OPCRWorkflow;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpcrWorkflowApproved
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public OPCRWorkflow $workflow,
        public ?User $approvedBy = null,
        public bool $forceCascade = false,
    ) {
    }
}

