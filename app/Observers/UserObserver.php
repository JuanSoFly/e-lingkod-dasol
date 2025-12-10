<?php

namespace App\Observers;

use App\Models\User;
use App\Services\IdentityLinker;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle events after transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    public function created(User $user): void
    {
        $this->syncIdentity($user);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged(['email', 'employee_id'])) {
            $this->syncIdentity($user);
        }
    }

    public function deleted(User $user): void
    {
        try {
            app(IdentityLinker::class)->detachUser($user);
        } catch (\Throwable $e) {
            Log::error('Failed to detach user identity relationship', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncIdentity(User $user): void
    {
        try {
            app(IdentityLinker::class)->syncForUser($user);
        } catch (\Throwable $e) {
            Log::error('Failed to synchronize user identity link', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

