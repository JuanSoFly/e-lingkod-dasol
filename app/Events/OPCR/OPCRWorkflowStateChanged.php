<?php

namespace App\Events\OPCR;

use App\Models\OPCRWorkflow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OPCRWorkflowStateChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public OPCRWorkflow $workflow;
    public string $previousState;
    public string $newState;
    public array $context;

    /**
     * Create a new event instance.
     */
    public function __construct(OPCRWorkflow $workflow, string $previousState, string $newState, array $context = [])
    {
        $this->workflow = $workflow;
        $this->previousState = $previousState;
        $this->newState = $newState;
        $this->context = $context;
    }
}
