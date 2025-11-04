@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ optional($ipcr->period)->name }} IPCR</h1>
                <p class="mt-1 text-sm text-gray-500">Status: {{ str_replace('_', ' ', $ipcr->status) }}</p>
            </div>
            <a href="{{ route('ipcr.employee.index') }}" class="text-primary-600 hover:text-primary-700">Back to list</a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-6">
            <form action="{{ route('ipcr.employee.update', $ipcr) }}" method="POST" class="rounded-lg border border-gray-200 bg-white shadow-sm">
                @csrf
                @method('PATCH')

                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-gray-900">Targets &amp; Self Assessment</h2>
                </div>

                <div class="space-y-6 px-4 py-6">
                    @foreach ($ipcr->items as $item)
                        <div class="rounded-lg border border-gray-100 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">{{ $item->title }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">{{ $item->description }}</p>
                                </div>
                                <div class="text-sm text-gray-500">Weight
                                    <input type="number" name="items[{{ $loop->index }}][weight]" value="{{ old("items.$loop->index.weight", $item->weight) }}" step="0.01" min="0" max="100" class="ml-2 w-24 rounded border-gray-300 text-right" />
                                </div>
                            </div>

                            <dl class="mt-3 grid grid-cols-1 gap-4 text-sm text-gray-600 md:grid-cols-3">
                                <div>
                                    <dt class="font-medium text-gray-500">Quantity Target</dt>
                                    <dd>{{ $item->target_quantity ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">Efficiency Target</dt>
                                    <dd>{{ $item->target_efficiency ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">Timeliness Target</dt>
                                    <dd>{{ $item->target_timeliness ?? 'N/A' }}</dd>
                                </div>
                            </dl>

                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Self Rating (1-5)</label>
                                    <input type="number" name="items[{{ $loop->index }}][self_rating]" value="{{ old("items.$loop->index.self_rating", $item->self_rating) }}" min="1" max="5" step="0.01" class="mt-1 w-full rounded border-gray-300" />
                                    @error("items.$loop->index.self_rating")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Comments</label>
                                    <textarea name="items[{{ $loop->index }}][remarks]" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old("items.$loop->index.remarks", $item->remarks) }}</textarea>
                                    @error("items.$loop->index.remarks")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                        </div>
                    @endforeach

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Overall Remarks</label>
                        <textarea name="remarks" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('remarks', $ipcr->remarks) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-sm text-gray-500">Ensure weights add up to 100% before submitting to your supervisor.</p>
                    <button type="submit" class="inline-flex items-center rounded bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">Save Changes</button>
                </div>
            </form>

            <div class="rounded-lg border border-sky-200 bg-sky-50 shadow-sm">
                <div class="flex items-center justify-between border-b border-sky-200 px-4 py-3">
                    <div>
                        <h2 class="text-lg font-medium text-sky-900">Progress Updates</h2>
                        <p class="text-xs text-sky-700">Risk Level: <span class="font-semibold">{{ ucfirst($progressSnapshot['risk_level'] ?? 'low') }}</span></p>
                    </div>
                </div>
                <div class="space-y-5 px-4 py-5">
                    <form action="{{ route('ipcr.employee.progress.store', $ipcr) }}" method="POST" class="rounded-lg border border-sky-100 bg-white/60 p-4">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-sky-900">Date</label>
                                <input type="date" name="progress_date" value="{{ old('progress_date', now()->toDateString()) }}" class="mt-1 w-full rounded border-sky-200 text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-sky-900">Status</label>
                                <select name="status" class="mt-1 w-full rounded border-sky-200 text-sm">
                                    @foreach ([
                                        'on_track' => 'On Track',
                                        'at_risk' => 'At Risk',
                                        'delayed' => 'Delayed',
                                        'critical' => 'Critical',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-sky-900">Related Target</label>
                                <select name="ipcr_item_id" class="mt-1 w-full rounded border-sky-200 text-sm">
                                    <option value="">General</option>
                                    @foreach ($ipcr->items as $item)
                                        <option value="{{ $item->id }}" @selected(old('ipcr_item_id') == $item->id)>{{ $item->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <textarea name="accomplishments" rows="2" placeholder="Accomplishments" class="rounded border-sky-200 text-sm">{{ old('accomplishments') }}</textarea>
                            <textarea name="challenges" rows="2" placeholder="Challenges" class="rounded border-sky-200 text-sm">{{ old('challenges') }}</textarea>
                            <textarea name="next_steps" rows="2" placeholder="Next Steps" class="rounded border-sky-200 text-sm">{{ old('next_steps') }}</textarea>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="inline-flex items-center rounded bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700">Log Progress</button>
                        </div>
                    </form>

                    <div class="space-y-3">
                        @forelse ($progressUpdates as $update)
                            <div class="rounded border border-sky-100 bg-white/80 p-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-sky-900">{{ ucfirst(str_replace('_', ' ', $update->status)) }}</div>
                                    <div class="text-xs text-sky-700">{{ $update->progress_date->format('M d, Y') }}</div>
                                </div>
                                <p class="mt-1 text-xs text-sky-600">{{ optional($update->item)->title ?? 'General Update' }}</p>
                                @if ($update->accomplishments)
                                    <p class="mt-2 text-sky-900">{{ $update->accomplishments }}</p>
                                @endif
                                @if ($update->challenges)
                                    <p class="mt-1 text-xs text-sky-700">Challenges: {{ $update->challenges }}</p>
                                @endif
                                @if ($update->next_steps)
                                    <p class="mt-1 text-xs text-sky-700">Next Steps: {{ $update->next_steps }}</p>
                                @endif
                                <p class="mt-1 text-xs text-sky-500">Reported by {{ optional($update->reporter)->name }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-sky-700">No progress updates recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-violet-200 bg-white shadow-sm">
                <div class="border-b border-violet-200 bg-violet-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-violet-900">Development Actions</h2>
                </div>
                <div class="px-4 py-5">
                    <div class="space-y-3">
                        @forelse ($developmentActions as $action)
                            <div class="rounded border border-violet-100 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-violet-900">
                                    <span class="font-semibold">{{ $action->focus_area }}</span>
                                    <span class="text-xs text-violet-600">Target: {{ optional($action->target_date)->format('M d, Y') ?? 'N/A' }}</span>
                                </div>
                                <p class="mt-1 text-sm text-violet-800">{{ $action->action_item }}</p>
                                <p class="mt-1 text-xs text-violet-600">Status: {{ ucfirst(str_replace('_', ' ', $action->status)) }}</p>
                                @if ($action->support_needed)
                                    <p class="mt-1 text-xs text-violet-500">Support Needed: {{ $action->support_needed }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-violet-700">No development actions logged yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-rose-200 bg-white shadow-sm">
                <div class="border-b border-rose-200 bg-rose-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-rose-900">Coaching Sessions</h2>
                </div>
                <div class="space-y-3 px-4 py-5">
                    @forelse ($coachingSessions as $session)
                        <div class="rounded border border-rose-100 p-3 text-sm">
                            <div class="flex items-center justify-between text-rose-900">
                                <span>{{ $session->session_type }} — {{ $session->session_date->format('M d, Y') }}</span>
                                <span class="text-xs text-rose-600">Coach: {{ optional($session->coach)->name }}</span>
                            </div>
                            @if ($session->focus_area)
                                <p class="mt-1 text-xs text-rose-700">Focus: {{ $session->focus_area }}</p>
                            @endif
                            @if ($session->discussion_notes)
                                <p class="mt-2 text-rose-900">{{ $session->discussion_notes }}</p>
                            @endif
                            @if ($session->follow_up_date)
                                <p class="mt-1 text-xs text-rose-600">Follow-up: {{ $session->follow_up_date->format('M d, Y') }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-rose-700">No coaching sessions recorded.</p>
                    @endforelse
                </div>
            </div>

            @if (in_array(App\Services\IpcrWorkflowService::STATE_FOR_SUPERVISOR_REVIEW, $availableTransitions))
                <form action="{{ route('ipcr.employee.submit', $ipcr) }}" method="POST" class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                    @csrf
                    <div>
                        <p class="text-sm font-semibold text-amber-900">Ready to submit to supervisor?</p>
                        <textarea name="remarks" rows="2" placeholder="Optional remarks to supervisor" class="mt-2 w-full rounded border-amber-300 text-sm"></textarea>
                    </div>
                    <button type="submit" class="ml-4 inline-flex items-center rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Submit for Review</button>
                </form>
            @endif

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-gray-900">Workflow History</h2>
                </div>
                <ul class="divide-y divide-gray-200">
                    @forelse ($ipcr->workflowLogs as $log)
                        <li class="px-4 py-3">
                            <p class="text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $log->from_state ?? 'start')) }} → {{ ucfirst(str_replace('_', ' ', $log->to_state)) }}</p>
                            <p class="text-xs text-gray-500">{{ optional($log->performed_at)->format('M d, Y H:i') }} — {{ optional($log->user)->name ?? 'System' }}</p>
                            @if ($log->remarks)
                                <p class="mt-1 text-sm text-gray-600">{{ $log->remarks }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-3 text-sm text-gray-500">No workflow activity recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
