<?php

namespace App\Http\Controllers\IPCR;

use App\Http\Controllers\Controller;
use App\Http\Requests\IPCR\PMTValidateIpcrRequest;
use App\Http\Requests\IPCR\PMTFinalizeIpcrRequest;
use App\Models\Ipcr;
use App\Services\IpcrCalibrationService;
use App\Services\IpcrValidationService;
use App\Services\IpcrWorkflowService;
use App\Services\IpcrProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PMTIpcrController extends Controller
{
    public function __construct(
        protected IpcrValidationService $validationService,
        protected IpcrWorkflowService $workflowService,
        protected IpcrCalibrationService $calibrationService,
        protected IpcrProgressService $progressService
    ) {
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:ipcr.validate');
    }

    public function index(Request $request): View
    {
        $user = Auth::user();

        $ipcrs = Ipcr::query()
            ->with(['employee', 'period', 'office'])
            ->whereIn('status', [IpcrWorkflowService::STATE_FOR_PMT_VALIDATION, IpcrWorkflowService::STATE_FINALIZED])
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $overview = $this->calibrationService->buildSummary($ipcrs->getCollection());

        return view('ipcr.pmt.index', [
            'ipcrs' => $ipcrs,
            'overview' => $overview,
        ]);
    }

    public function show(Ipcr $ipcr): View
    {
        $this->authorize('validate', $ipcr);

        $ipcr->load([
            'items' => fn ($query) => $query->orderBy('sequence'),
            'employee',
            'period',
            'office',
            'pmtValidations' => fn ($query) => $query->latest(),
            'calibrationItems.session',
        ]);

        $availableTransitions = $this->workflowService->availableTransitions(Auth::user(), $ipcr);
        $calibration = $this->calibrationService->buildIpcrStatistics($ipcr);
        $progressSnapshot = $this->progressService->snapshot($ipcr);
        $progressUpdates = $ipcr->progressUpdates()->with('reporter')->latest('progress_date')->limit(10)->get();
        $developmentActions = $ipcr->developmentActions()->orderBy('target_date')->get();
        $coachingSessions = $ipcr->coachingSessions()->with(['coach', 'participant'])->latest('session_date')->limit(10)->get();

        return view('ipcr.pmt.show', [
            'ipcr' => $ipcr,
            'availableTransitions' => $availableTransitions,
            'calibration' => $calibration,
            'progressSnapshot' => $progressSnapshot,
            'progressUpdates' => $progressUpdates,
            'developmentActions' => $developmentActions,
            'coachingSessions' => $coachingSessions,
        ]);
    }

    public function validateIpcr(PMTValidateIpcrRequest $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('validate', $ipcr);

        $this->validationService->recordValidation($ipcr, Auth::user(), $request->validated());

        return back()->with('status', 'IPCR validation saved.');
    }

    public function endorse(PMTFinalizeIpcrRequest $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('validate', $ipcr);

        $this->validationService->finalizeValidation($ipcr, Auth::user(), $request->validated());

        $this->workflowService->transition(
            Auth::user(),
            $ipcr,
            IpcrWorkflowService::STATE_FINALIZED,
            $request->input('remarks')
        );

        return redirect()
            ->route('ipcr.pmt.index')
            ->with('status', 'IPCR endorsed for final approval.');
    }

    public function returnToHead(Request $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('validate', $ipcr);

        $this->workflowService->transition(
            Auth::user(),
            $ipcr,
            IpcrWorkflowService::STATE_RETURNED_WITH_NOTES,
            $request->input('remarks')
        );

        return back()->with('status', 'IPCR returned to Head of Office.');
    }
}
