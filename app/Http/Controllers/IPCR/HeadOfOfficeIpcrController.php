<?php

namespace App\Http\Controllers\IPCR;

use App\Http\Controllers\Controller;
use App\Http\Requests\IPCR\HeadApprovalIpcrRequest;
use App\Models\Ipcr;
use App\Services\IpcrReviewService;
use App\Services\IpcrWorkflowService;
use App\Services\IpcrProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HeadOfOfficeIpcrController extends Controller
{
    public function __construct(
        protected IpcrReviewService $reviewService,
        protected IpcrWorkflowService $workflowService,
        protected IpcrProgressService $progressService
    ) {
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:ipcr.approve');
    }

    public function index(Request $request): View
    {
        $user = Auth::user();

        $ipcrs = Ipcr::query()
            ->with(['employee', 'period'])
            ->where('head_of_office_id', $user->employee_id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->get('status')))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('ipcr.head.index', [
            'ipcrs' => $ipcrs,
            'filters' => [
                'status' => $request->get('status'),
            ],
        ]);
    }

    public function show(Ipcr $ipcr): View
    {
        $this->authorize('approve', $ipcr);

        $ipcr->load([
            'items' => fn ($query) => $query->orderBy('sequence'),
            'employee',
            'period',
            'workflowLogs' => fn ($query) => $query->latest(),
        ]);

        $availableTransitions = $this->workflowService->availableTransitions(Auth::user(), $ipcr);
        $progressSnapshot = $this->progressService->snapshot($ipcr);
        $progressUpdates = $ipcr->progressUpdates()->with('reporter')->latest('progress_date')->limit(10)->get();
        $developmentActions = $ipcr->developmentActions()->orderBy('target_date')->get();
        $coachingSessions = $ipcr->coachingSessions()->with(['coach', 'participant'])->latest('session_date')->limit(10)->get();

        return view('ipcr.head.show', [
            'ipcr' => $ipcr,
            'availableTransitions' => $availableTransitions,
            'progressSnapshot' => $progressSnapshot,
            'progressUpdates' => $progressUpdates,
            'developmentActions' => $developmentActions,
            'coachingSessions' => $coachingSessions,
        ]);
    }

    public function approve(HeadApprovalIpcrRequest $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('approve', $ipcr);

        $this->reviewService->headOfOfficeReview($ipcr, Auth::user(), $request->validated());

        $this->workflowService->transition(
            Auth::user(),
            $ipcr,
            IpcrWorkflowService::STATE_FOR_PMT_VALIDATION,
            $request->input('remarks')
        );

        return redirect()
            ->route('ipcr.head.index')
            ->with('status', 'IPCR endorsed to PMT for validation.');
    }

    public function returnToSupervisor(Request $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('approve', $ipcr);

        $this->workflowService->transition(
            Auth::user(),
            $ipcr,
            IpcrWorkflowService::STATE_RETURNED_WITH_NOTES,
            $request->input('remarks')
        );

        return back()->with('status', 'IPCR returned with remarks.');
    }
}
