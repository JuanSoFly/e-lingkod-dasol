@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Head Review - {{ optional($ipcr->employee)->first_name }} {{ optional($ipcr->employee)->last_name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Performance Period: {{ optional($ipcr->period)->name }}</p>
            </div>
            <a href="{{ route('ipcr.head.index') }}" class="text-primary-600 hover:text-primary-700">Back to list</a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>
        @endif

        <form action="{{ route('ipcr.head.approve', $ipcr) }}" method="POST" class="space-y-6">
            @csrf

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-gray-900">Ratings Overview</h2>
                </div>
                <div class="space-y-6 px-4 py-5">
                    @foreach ($ipcr->items as $item)
                        <div class="rounded-lg border border-gray-100 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">{{ $item->title }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">{{ $item->description }}</p>
                                </div>
                                <div class="text-sm text-gray-500">Weight: {{ number_format($item->weight, 2) }}%</div>
                            </div>

                            <dl class="mt-3 grid grid-cols-1 gap-4 text-sm text-gray-600 md:grid-cols-3">
                                <div>
                                    <dt class="font-medium text-gray-500">Self Rating</dt>
                                    <dd>{{ $item->self_rating ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">Supervisor Rating</dt>
                                    <dd>{{ $item->supervisor_rating ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">Head Rating</dt>
                                    <dd>
                                        <input type="number" name="items[{{ $loop->index }}][head_rating]" value="{{ old("items.$loop->index.head_rating", $item->head_rating) }}" min="1" max="5" step="0.01" class="w-24 rounded border-gray-300" />
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-3">
                                <label class="block text-sm font-medium text-gray-700">Head Comments</label>
                                <textarea name="items[{{ $loop->index }}][head_comments]" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old("items.$loop->index.head_comments", data_get($item->head_rating_details, 'comments')) }}</textarea>
                            </div>

                            <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                        </div>
                    @endforeach

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Head of Office Remarks</label>
                        <textarea name="remarks" rows="3" class="mt-1 w-full rounded border-gray-300"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <button type="submit" class="inline-flex items-center rounded bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Endorse to PMT</button>
                </div>
            </div>
        </form>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-sky-200 bg-white shadow-sm">
                <div class="border-b border-sky-200 bg-sky-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-sky-900">Progress Snapshot</h2>
                    <p class="text-xs text-sky-700">Risk Level: {{ ucfirst($progressSnapshot['risk_level'] ?? 'low') }}</p>
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
                        </div>
                    @empty
                        <p class="text-sm text-sky-700">No updates recorded.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-violet-200 bg-white shadow-sm">
                <div class="border-b border-violet-200 bg-violet-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-violet-900">Development Actions</h2>
                </div>
                <div class="px-4 py-4 space-y-2 text-sm text-violet-800">
                    @forelse ($developmentActions as $action)
                        <div class="rounded border border-violet-100 p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ $action->focus_area }}</span>
                                <span class="text-xs text-violet-600">{{ ucfirst(str_replace('_', ' ', $action->status)) }}</span>
                            </div>
                            <p class="mt-1">{{ $action->action_item }}</p>
                        </div>
                    @empty
                        <p>No development actions logged.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <form action="{{ route('ipcr.head.return', $ipcr) }}" method="POST" class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-amber-900">Return to Supervisor</label>
                    <input type="text" name="remarks" class="mt-1 w-full rounded border-amber-300 text-sm" placeholder="Reason for returning" required>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Return for Revisions</button>
            </form>
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
