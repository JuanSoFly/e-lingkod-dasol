<?php

namespace App\Services;

use App\Models\Ipcr;
use App\Models\IpcrWorkflowLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class IpcrWorkflowService
{
    public const STATE_DRAFT = 'draft';
    public const STATE_FOR_SUPERVISOR_REVIEW = 'for_supervisor_review';
    public const STATE_RETURNED_WITH_NOTES = 'returned_with_notes';
    public const STATE_FOR_HEAD_APPROVAL = 'for_head_approval';
    public const STATE_FOR_PMT_VALIDATION = 'for_pmt_validation';
    public const STATE_FINALIZED = 'finalized';
    public const STATE_LOCKED = 'locked';

    protected array $transitions = [
        self::STATE_DRAFT => [
            self::STATE_FOR_SUPERVISOR_REVIEW => [
                'permission' => 'ipcr.submit',
                'action' => 'submit',
                'performed_role' => 'employee',
                'timestamp_columns' => ['submitted_at'],
            ],
        ],
        self::STATE_FOR_SUPERVISOR_REVIEW => [
            self::STATE_RETURNED_WITH_NOTES => [
                'permission' => 'ipcr.review',
                'action' => 'return',
                'performed_role' => 'supervisor',
                'reset_columns' => ['supervisor_reviewed_at'],
            ],
            self::STATE_FOR_HEAD_APPROVAL => [
                'permission' => 'ipcr.review',
                'action' => 'endorse',
                'performed_role' => 'supervisor',
                'timestamp_columns' => ['supervisor_reviewed_at'],
            ],
        ],
        self::STATE_RETURNED_WITH_NOTES => [
            self::STATE_DRAFT => [
                'permission' => 'ipcr.edit',
                'action' => 'revise',
                'performed_role' => 'employee',
                'reset_columns' => [
                    'submitted_at',
                    'supervisor_reviewed_at',
                    'head_reviewed_at',
                    'pmt_validated_at',
                    'finalized_at',
                    'locked_at',
                ],
            ],
        ],
        self::STATE_FOR_HEAD_APPROVAL => [
            self::STATE_RETURNED_WITH_NOTES => [
                'permission' => 'ipcr.approve',
                'action' => 'return',
                'performed_role' => 'head',
                'reset_columns' => ['head_reviewed_at'],
            ],
            self::STATE_FOR_PMT_VALIDATION => [
                'permission' => 'ipcr.approve',
                'action' => 'approve',
                'performed_role' => 'head',
                'timestamp_columns' => ['head_reviewed_at'],
            ],
        ],
        self::STATE_FOR_PMT_VALIDATION => [
            self::STATE_RETURNED_WITH_NOTES => [
                'permission' => 'ipcr.validate',
                'action' => 'return',
                'performed_role' => 'pmt',
                'reset_columns' => ['pmt_validated_at'],
            ],
            self::STATE_FINALIZED => [
                'permission' => 'ipcr.validate',
                'action' => 'validate',
                'performed_role' => 'pmt',
                'timestamp_columns' => ['pmt_validated_at', 'finalized_at'],
            ],
        ],
        self::STATE_FINALIZED => [
            self::STATE_LOCKED => [
                'permission' => 'ipcr.finalize',
                'action' => 'lock',
                'performed_role' => 'final_approver',
                'timestamp_columns' => ['locked_at'],
            ],
        ],
    ];

    public function availableTransitions(User $user, Ipcr $ipcr): array
    {
        $current = $ipcr->status;

        if (!isset($this->transitions[$current])) {
            return [];
        }

        return collect($this->transitions[$current])
            ->filter(fn ($config, $state) => $this->authorizes($user, $ipcr, $state, $config))
            ->keys()
            ->all();
    }

    public function canTransition(User $user, Ipcr $ipcr, string $toState): bool
    {
        $current = $ipcr->status;

        if (!isset($this->transitions[$current][$toState])) {
            return false;
        }

        return $this->authorizes($user, $ipcr, $toState, $this->transitions[$current][$toState]);
    }

    public function transition(User $user, Ipcr $ipcr, string $toState, ?string $remarks = null, array $metadata = []): Ipcr
    {
        $current = $ipcr->status;

        if (!isset($this->transitions[$current][$toState])) {
            throw new AuthorizationException("Transition from {$current} to {$toState} is not allowed.");
        }

        $config = $this->transitions[$current][$toState];

        if (!$this->authorizes($user, $ipcr, $toState, $config)) {
            throw new AuthorizationException('You do not have permission to perform this action.');
        }

        $this->applyStateChanges($ipcr, $toState, $config);

        $ipcr->save();

        IpcrWorkflowLog::create([
            'ipcr_id' => $ipcr->id,
            'user_id' => $user->id,
            'employee_id' => $user->employee_id,
            'from_state' => $current,
            'to_state' => $toState,
            'action' => Arr::get($config, 'action'),
            'performed_role' => Arr::get($config, 'performed_role', $user->getRoleNames()->first()),
            'remarks' => $remarks,
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);

        return $ipcr;
    }

    protected function authorizes(User $user, Ipcr $ipcr, string $toState, array $config): bool
    {
        if (empty($config['permission'])) {
            return true;
        }

        if (!$user->can($config['permission'])) {
            return false;
        }

        // Delegate to policy for contextual checks
        return match ($config['permission']) {
            'ipcr.submit' => Gate::forUser($user)->allows('submit', $ipcr),
            'ipcr.review' => Gate::forUser($user)->allows('review', $ipcr),
            'ipcr.approve' => Gate::forUser($user)->allows('approve', $ipcr),
            'ipcr.validate' => Gate::forUser($user)->allows('validate', $ipcr),
            'ipcr.finalize' => Gate::forUser($user)->allows('finalize', $ipcr),
            'ipcr.edit' => Gate::forUser($user)->allows('update', $ipcr),
            default => true,
        };
    }

    protected function applyStateChanges(Ipcr $ipcr, string $toState, array $config): void
    {
        $ipcr->status = $toState;

        foreach ($config['timestamp_columns'] ?? [] as $column) {
            $ipcr->{$column} = now();
        }

        foreach ($config['reset_columns'] ?? [] as $column) {
            $ipcr->{$column} = null;
        }

        if ($toState === self::STATE_RETURNED_WITH_NOTES) {
            $ipcr->pmt_validated_at = null;
            $ipcr->finalized_at = null;
            $ipcr->locked_at = null;
        }
    }
}
