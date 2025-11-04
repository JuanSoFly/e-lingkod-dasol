<?php

namespace App\Services;

use App\Models\Ipcr;
use App\Models\IpcrCoachingSession;
use App\Models\IpcrDevelopmentAction;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class IpcrCoachingService
{
    public function logSession(Ipcr $ipcr, User $coach, array $payload): IpcrCoachingSession
    {
        return DB::transaction(function () use ($ipcr, $coach, $payload) {
            $session = $ipcr->coachingSessions()->create([
                'coach_user_id' => $coach->id,
                'participant_user_id' => $ipcr->employee?->user?->id,
                'session_date' => Arr::get($payload, 'session_date', now()->toDateString()),
                'session_type' => Arr::get($payload, 'session_type', 'coaching'),
                'focus_area' => Arr::get($payload, 'focus_area'),
                'discussion_notes' => Arr::get($payload, 'discussion_notes'),
                'agreements' => Arr::get($payload, 'agreements'),
                'follow_up_date' => Arr::get($payload, 'follow_up_date'),
            ]);

            foreach (Arr::get($payload, 'actions', []) as $action) {
                $ipcr->developmentActions()->create([
                    'focus_area' => Arr::get($action, 'focus_area'),
                    'action_item' => Arr::get($action, 'action_item'),
                    'target_date' => Arr::get($action, 'target_date'),
                    'status' => Arr::get($action, 'status', 'planned'),
                    'support_needed' => Arr::get($action, 'support_needed'),
                    'created_by' => $coach->id,
                ]);
            }

            return $session;
        });
    }

    public function updateAction(IpcrDevelopmentAction $action, array $payload, User $user): IpcrDevelopmentAction
    {
        $action->forceFill([
            'focus_area' => Arr::get($payload, 'focus_area', $action->focus_area),
            'action_item' => Arr::get($payload, 'action_item', $action->action_item),
            'target_date' => Arr::get($payload, 'target_date', $action->target_date),
            'status' => Arr::get($payload, 'status', $action->status),
            'support_needed' => Arr::get($payload, 'support_needed', $action->support_needed),
            'updated_by' => $user->id,
        ])->save();

        return $action->fresh();
    }
}
