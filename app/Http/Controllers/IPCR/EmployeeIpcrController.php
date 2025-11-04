<?php

namespace App\Http\Controllers\IPCR;

use App\Http\Controllers\Controller;
use App\Http\Requests\IPCR\UpdateEmployeeIpcrRequest;
use App\Models\Ipcr;
use App\Services\IpcrEditorService;
use App\Services\IpcrWorkflowService;
use App\Services\IpcrProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeIpcrController extends Controller
{
    public function __construct(
        protected IpcrEditorService $editorService,
        protected IpcrWorkflowService $workflowService,
        protected IpcrProgressService $progressService
    ) {
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:ipcr.view-own');
    }

    public function index(Request $request): View
    {
        $user = Auth::user();

        $ipcrs = Ipcr::query()
            ->where('employee_id', $user->employee_id)
            ->with(['period', 'items' => fn ($query) => $query->orderBy('sequence')])
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('ipcr.employee.index', [
            'ipcrs' => $ipcrs,
        ]);
    }

    public function show(Ipcr $ipcr): View
    {
        $this->authorize('view', $ipcr);

        $ipcr->load([
            'items' => fn ($query) => $query->orderBy('sequence'),
            'supervisor',
            'headOfOffice',
            'pmtValidator',
            'finalApprover',
            'workflowLogs' => fn ($query) => $query->latest(),
        ]);

        $availableTransitions = $this->workflowService->availableTransitions(Auth::user(), $ipcr);
        $progressSnapshot = $this->progressService->snapshot($ipcr);
        $progressUpdates = $ipcr->progressUpdates()->with('reporter')->latest('progress_date')->limit(10)->get();
        $developmentActions = $ipcr->developmentActions()->orderBy('target_date')->get();
        $coachingSessions = $ipcr->coachingSessions()->with(['coach', 'participant'])->latest('session_date')->limit(5)->get();

        return view('ipcr.employee.show', [
            'ipcr' => $ipcr,
            'availableTransitions' => $availableTransitions,
            'progressSnapshot' => $progressSnapshot,
            'progressUpdates' => $progressUpdates,
            'developmentActions' => $developmentActions,
            'coachingSessions' => $coachingSessions,
        ]);
    }

    public function update(UpdateEmployeeIpcrRequest $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('update', $ipcr);

        $this->editorService->updateEmployeeSubmission($ipcr, $request->validated());

        return back()->with('status', 'IPCR updated successfully.');
    }

    public function submit(Request $request, Ipcr $ipcr): RedirectResponse
    {
        $this->authorize('submit', $ipcr);

        $this->workflowService->transition(
            Auth::user(),
            $ipcr,
            IpcrWorkflowService::STATE_FOR_SUPERVISOR_REVIEW,
            $request->input('remarks')
        );

        return redirect()
            ->route('ipcr.employee.show', $ipcr)
            ->with('status', 'IPCR submitted for supervisor review.');
    }
}
