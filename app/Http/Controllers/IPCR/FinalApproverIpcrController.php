<?php

namespace App\Http\Controllers\IPCR;

use App\Http\Controllers\Controller;
use App\Http\Requests\IPCR\FinalizeIpcrRequest;
use App\Models\Ipcr;
use App\Services\IpcrValidationService;
use App\Services\IpcrWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FinalApproverIpcrController extends Controller
{
    public function __construct(
        protected IpcrValidationService $validationService,
        protected IpcrWorkflowService $workflowService
    ) {
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:ipcr.finalize');
    }

    public function index(Request $request): View
    {
        $user = Auth::user();

        $ipcrs = Ipcr::query()
            ->with(['employee', 'period'])
            ->where('final_approver_id', $user->employee_id)
            ->whereIn('status', [IpcrWorkflowService::STATE_FINALIZED, IpcrWorkflowService::STATE_LOCKED])
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('ipcr.final.index', [
            'ipcrs' => $ipcrs,
        ]);
    }

    public function show(Ipcr $ipcr): View
    {
        $this->authorize('finalize', $ipcr);

        $ipcr->load([
            'items' => fn ($query) => $query->orderBy('sequence'),
            'employee',
            'period',
            'finalRating',
        ]);

        $availableTransitions = $this->workflowService->availableTransitions(Auth::user(), $ipcr);
        $progressUpdates = $ipcr->progressUpdates()->with('reporter')->latest('progress_date')->limit(5)->get();

        return view('ipcr.final.show', [
            'ipcr' => $ipcr,
            'availableTransitions' => $availableTransitions,
            'progressUpdates' => $progressUpdates,
        ]);
    }

    public function finalize(FinalizeIpcrRequest $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('finalize', $ipcr);

        $this->validationService->recordFinalRating($ipcr, Auth::user(), $request->validated());

        $this->workflowService->transition(
            Auth::user(),
            $ipcr,
            IpcrWorkflowService::STATE_LOCKED,
            $request->input('remarks')
        );

        return redirect()
            ->route('ipcr.final.index')
            ->with('status', 'IPCR locked with final rating.');
    }
}
