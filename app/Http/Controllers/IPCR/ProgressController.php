<?php

namespace App\Http\Controllers\IPCR;

use App\Http\Controllers\Controller;
use App\Http\Requests\IPCR\StoreProgressUpdateRequest;
use App\Models\Ipcr;
use App\Services\IpcrProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    public function __construct(protected IpcrProgressService $progressService)
    {
        $this->middleware(['auth', 'verified']);
    }

    public function store(StoreProgressUpdateRequest $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('view', $ipcr);

        abort_if($ipcr->is_locked, 403, 'Locked IPCR cannot record progress updates.');

        $this->progressService->recordProgress($ipcr, Auth::user(), $request->validated());

        return back()->with('status', 'Progress update recorded.');
    }
}
