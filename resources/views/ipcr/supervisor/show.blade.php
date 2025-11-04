@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Review IPCR - {{ optional($ipcr->employee)->first_name }} {{ optional($ipcr->employee)->last_name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Performance Period: {{ optional($ipcr->period)->name }}</p>
            </div>
            <a href="{{ route('ipcr.supervisor.index') }}" class="text-primary-600 hover:text-primary-700">Back to list</a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('ipcr.supervisor.review', $ipcr) }}" method="POST" class="space-y-6">
            @csrf

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-gray-900">Targets &amp; Ratings</h2>
                </div>
                <div class="space-y-6 px-4 py-5">
                    @foreach ($ipcr->items as $item)
                        <div class="rounded-lg border border-gray-100 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">{{ $item->title }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">{{ $item->description }}</p>
                                </div>
                                <div class="text-sm text-gray-500">Weight: {{ number_format($item->weight, 2) }}%</div>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Self Rating</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $item->self_rating ?? '—' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Supervisor Rating</label>
                                    <input type="number" name="items[{{ $loop->index }}][supervisor_rating]" value="{{ old("items.$loop->index.supervisor_rating", $item->supervisor_rating) }}" min="1" max="5" step="0.01" class="mt-1 w-full rounded border-gray-300" />
                                    @error("items.$loop->index.supervisor_rating")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Supervisor Comments</label>
                                <textarea name="items[{{ $loop->index }}][supervisor_comments]" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old("items.$loop->index.supervisor_comments", data_get($item->supervisor_rating_details, 'comments')) }}</textarea>
                                @error("items.$loop->index.supervisor_comments")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                        </div>
                    @endforeach

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Overall Remarks</label>
                        <textarea name="overall_remarks" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('overall_remarks') }}</textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <button type="submit" class="inline-flex items-center rounded bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Save Ratings</button>
                </div>
            </div>
        </form>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-sky-200 bg-white shadow-sm">
                <div class="border-b border-sky-200 bg-sky-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-sky-900">Progress Overview</h2>
                    <p class="text-xs text-sky-700">Risk: {{ ucfirst($progressSnapshot['risk_level'] ?? 'low') }}</p>
                </div>
                <div class="px-4 py-4 space-y-3">
                    @forelse ($progressUpdates as $update)
                        <div class="rounded border border-sky-100 p-3 text-sm">
                            <div class="flex items-center justify-between text-sky-900">
                                <span>{{ ucfirst(str_replace('_', ' ', $update->status)) }}</span>
                                <span class="text-xs text-sky-600">{{ $update->progress_date->format('M d, Y') }}</span>
                            </div>
                            @if ($update->accomplishments)
                                <p class="mt-1 text-sky-800">{{ $update->accomplishments }}</p>
                            @endif
                            @if ($update->challenges)
                                <p class="mt-1 text-xs text-sky-600">Challenges: {{ $update->challenges }}</p>
                            @endif
                            <p class="mt-1 text-xs text-sky-500">Reported by {{ optional($update->reporter)->name }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-sky-700">No progress updates yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-rose-200 bg-white shadow-sm">
                <div class="border-b border-rose-200 bg-rose-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-rose-900">Log Coaching Session</h2>
                </div>
                <form action="{{ route('ipcr.supervisor.coaching.store', $ipcr) }}" method="POST" class="space-y-3 px-4 py-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-rose-900">Date</label>
                            <input type="date" name="session_date" value="{{ old('session_date', now()->toDateString()) }}" class="mt-1 w-full rounded border-rose-200 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-rose-900">Focus Area</label>
                            <input type="text" name="focus_area" value="{{ old('focus_area') }}" class="mt-1 w-full rounded border-rose-200 text-sm" placeholder="e.g., Timeliness">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-rose-900">Discussion Notes</label>
                        <textarea name="discussion_notes" rows="3" class="mt-1 w-full rounded border-rose-200 text-sm">{{ old('discussion_notes') }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-rose-900">Agreements</label>
                            <textarea name="agreements" rows="2" class="mt-1 w-full rounded border-rose-200 text-sm">{{ old('agreements') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-rose-900">Follow-up Date</label>
                            <input type="date" name="follow_up_date" value="{{ old('follow_up_date') }}" class="mt-1 w-full rounded border-rose-200 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-rose-900">Action Focus</label>
                            <input type="text" name="actions[0][focus_area]" class="mt-1 w-full rounded border-rose-200 text-sm" placeholder="Area to improve">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rose-900">Action Item</label>
                            <input type="text" name="actions[0][action_item]" class="mt-1 w-full rounded border-rose-200 text-sm" placeholder="Planned action">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-rose-900">Target Date</label>
                            <input type="date" name="actions[0][target_date]" class="mt-1 w-full rounded border-rose-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rose-900">Support Needed</label>
                            <input type="text" name="actions[0][support_needed]" class="mt-1 w-full rounded border-rose-200 text-sm" placeholder="Resources or training">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center rounded bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Record Coaching</button>
                    </div>
                </form>
                <div class="border-t border-rose-100 px-4 py-4">
                    <h3 class="text-sm font-semibold text-rose-900">Recent Sessions</h3>
                    <div class="mt-2 space-y-2 text-sm text-rose-800">
                        @forelse ($coachingSessions as $session)
                            <div class="rounded border border-rose-100 p-3">
                                <div class="flex items-center justify-between">
                                    <span>{{ $session->session_date->format('M d, Y') }} — {{ $session->session_type }}</span>
                                    <span class="text-xs text-rose-600">Follow-up: {{ optional($session->follow_up_date)->format('M d, Y') ?? 'N/A' }}</span>
                                </div>
                                @if ($session->focus_area)
                                    <p class="mt-1 text-xs text-rose-600">Focus: {{ $session->focus_area }}</p>
                                @endif
                                @if ($session->discussion_notes)
                                    <p class="mt-1">{{ $session->discussion_notes }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-rose-700">No prior coaching sessions.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-violet-200 bg-white shadow-sm">
            <div class="border-b border-violet-200 bg-violet-50 px-4 py-3">
                <h2 class="text-lg font-medium text-violet-900">Development Actions</h2>
            </div>
            <div class="overflow-x-auto px-4 py-4">
                <table class="min-w-full divide-y divide-violet-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-violet-600">
                            <th class="py-2">Focus Area</th>
                            <th class="py-2">Action Item</th>
                            <th class="py-2">Target Date</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Support Needed</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-violet-100">
                        @forelse ($developmentActions as $action)
                            <tr>
                                <td class="py-2">{{ $action->focus_area }}</td>
                                <td class="py-2">{{ $action->action_item }}</td>
                                <td class="py-2">{{ optional($action->target_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="py-2">
                                    <select name="status" form="action-update-{{ $action->id }}" class="rounded border-violet-200 text-xs">
                                        @foreach (['planned' => 'Planned', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'on_hold' => 'On Hold'] as $value => $label)
                                            <option value="{{ $value }}" @selected($action->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2">
                                    <input type="text" name="support_needed" form="action-update-{{ $action->id }}" value="{{ $action->support_needed }}" class="w-full rounded border-violet-200 text-xs" />
                                </td>
                                <td class="py-2 text-right">
                                    <form id="action-update-{{ $action->id }}" action="{{ route('ipcr.supervisor.actions.update', [$ipcr, $action]) }}" method="POST" class="inline-flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center rounded bg-violet-600 px-3 py-1 text-xs font-semibold text-white hover:bg-violet-700">Update</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-sm text-violet-600">No development actions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-amber-900">Next Actions</p>
                <p class="text-sm text-amber-800">Endorse to Head of Office or return to employee with notes.</p>
            </div>
            <div class="flex flex-col gap-2 md:flex-row">
                @if (in_array(App\Services\IpcrWorkflowService::STATE_FOR_HEAD_APPROVAL, $availableTransitions))
                    <form method="POST" action="{{ route('ipcr.supervisor.endorse', $ipcr) }}">
                        @csrf
                        <input type="hidden" name="remarks" value="">
                        <button type="submit" class="inline-flex items-center rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Endorse to Head</button>
                    </form>
                @endif
                @if (in_array(App\Services\IpcrWorkflowService::STATE_RETURNED_WITH_NOTES, $availableTransitions))
                    <form method="POST" action="{{ route('ipcr.supervisor.return', $ipcr) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="text" name="remarks" placeholder="Return remarks" class="w-full rounded border-amber-300 text-sm" required>
                        <button type="submit" class="inline-flex items-center rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Return to Employee</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h2 class="text-lg font-medium text-gray-900">Workflow History</h2>
            </div>
            <ul class="divide-y divide-gray-200">
                @foreach ($ipcr->workflowLogs as $log)
                    <li class="px-4 py-3">
                        <p class="text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $log->from_state ?? 'start')) }} → {{ ucfirst(str_replace('_', ' ', $log->to_state)) }}</p>
                        <p class="text-xs text-gray-500">{{ optional($log->performed_at)->format('M d, Y H:i') }} — {{ optional($log->user)->name ?? 'System' }}</p>
                        @if ($log->remarks)
                            <p class="mt-1 text-sm text-gray-600">{{ $log->remarks }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
