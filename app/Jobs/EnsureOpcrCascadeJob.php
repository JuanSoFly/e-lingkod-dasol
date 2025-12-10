<?php

namespace App\Jobs;

use App\Services\IpcrCascadeScheduler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnsureOpcrCascadeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $officeId = null,
        public ?int $workflowId = null,
        public bool $force = false,
    ) {
    }

    public function handle(IpcrCascadeScheduler $scheduler): void
    {
        $scheduler->reconcile($this->officeId, $this->workflowId, $this->force);
    }
}

