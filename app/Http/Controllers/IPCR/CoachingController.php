<?php

namespace App\Http\Controllers\IPCR;

use App\Http\Controllers\Controller;
use App\Http\Requests\IPCR\StoreCoachingSessionRequest;
use App\Http\Requests\IPCR\UpdateDevelopmentActionRequest;
use App\Models\Ipcr;
use App\Models\IpcrDevelopmentAction;
use App\Services\IpcrCoachingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CoachingController extends Controller
{
    public function __construct(protected IpcrCoachingService $coachingService)
    {
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:ipcr.adjustments.manage');
    }

    public function store(StoreCoachingSessionRequest $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('manageAdjustments', $ipcr);

        $this->coachingService->logSession($ipcr, Auth::user(), $request->validated());

        return back()->with('status', 'Coaching session recorded.');
    }

    public function updateAction(UpdateDevelopmentActionRequest $request, IpcrDevelopmentAction $action): RedirectResponse
    {
        $ipcr = $action->ipcr;
        $this->authorize('manageAdjustments', $ipcr);

        $this->coachingService->updateAction($action, $request->validated(), Auth::user());

        return back()->with('status', 'Development action updated.');
    }
}
