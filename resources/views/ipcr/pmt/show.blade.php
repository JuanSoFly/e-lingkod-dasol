@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">PMT Validation - {{ optional($ipcr->employee)->first_name }} {{ optional($ipcr->employee)->last_name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Period: {{ optional($ipcr->period)->name }} | Office: {{ optional($ipcr->office)->name }}</p>
            </div>
            <a href="{{ route('ipcr.pmt.index') }}" class="text-primary-600 hover:text-primary-700">Back to queue</a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="mb-6 grid gap-4 rounded-lg border border-blue-100 bg-blue-50 p-4 md:grid-cols-3">
            <div>
                <h3 class="text-sm font-semibold text-blue-900">Self Avg</h3>
                <p class="text-lg font-semibold text-blue-800">{{ $calibration['self_average'] ?? '—' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-blue-900">Supervisor Avg</h3>
                <p class="text-lg font-semibold text-blue-800">{{ $calibration['supervisor_average'] ?? '—' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-blue-900">PMT Avg</h3>
                <p class="text-lg font-semibold text-blue-800">{{ $calibration['pmt_average'] ?? '—' }}</p>
            </div>
        </div>

        <form action="{{ route('ipcr.pmt.validate', $ipcr) }}" method="POST" class="space-y-6">
            @csrf

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-gray-900">Rating Calibration</h2>
                </div>
                <div class="space-y-6 px-4 py-5">
                    @foreach ($ipcr->items as $index => $item)
                        <div class="rounded-lg border border-gray-100 p-4">
                            <h3 class="text-base font-semibold text-gray-900">{{ $item->title }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $item->description }}</p>

                            <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-4">
                                <div>
                                    <span class="text-xs uppercase text-gray-500">Self</span>
                                    <p class="text-sm text-gray-900">{{ $item->self_rating ?? '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs uppercase text-gray-500">Supervisor</span>
                                    <p class="text-sm text-gray-900">{{ $item->supervisor_rating ?? '—' }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium uppercase tracking-wide text-gray-500">PMT Rating</label>
                                    <input type="number" name="items[{{ $index }}][pmt_rating]" value="{{ old("items.$index.pmt_rating", $item->pmt_rating) }}" min="1" max="5" step="0.01" class="mt-1 w-full rounded border-gray-300" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium uppercase tracking-wide text-gray-500">Comments</label>
                                    <textarea name="items[{{ $index }}][pmt_comments]" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old("items.$index.pmt_comments", data_get($item->pmt_rating_details, 'comments')) }}</textarea>
                                </div>
                            </div>

                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                        </div>
                    @endforeach

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Recommended Overall Rating</label>
                            <input type="number" name="recommended_rating" value="{{ old('recommended_rating', $ipcr->overall_score) }}" min="1" max="5" step="0.01" class="mt-1 w-full rounded border-gray-300" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Validation Remarks</label>
                            <textarea name="remarks" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('remarks') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <button type="submit" class="inline-flex items-center rounded bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Save Validation</button>
                </div>
            </div>
        </form>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-sky-200 bg-white shadow-sm">
                <div class="border-b border-sky-200 bg-sky-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-sky-900">Recent Progress</h2>
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
                        <p class="text-sm text-sky-700">No progress updates recorded.</p>
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

        <div class="mt-6 rounded-lg border border-rose-200 bg-white shadow-sm">
            <div class="border-b border-rose-200 bg-rose-50 px-4 py-3">
                <h2 class="text-lg font-medium text-rose-900">Coaching Sessions</h2>
            </div>
            <div class="px-4 py-4 space-y-2 text-sm text-rose-800">
                @forelse ($coachingSessions as $session)
                    <div class="rounded border border-rose-100 p-3">
                        <div class="flex items-center justify-between">
                            <span>{{ $session->session_date->format('M d, Y') }} — {{ $session->session_type }}</span>
                            <span class="text-xs text-rose-600">Coach: {{ optional($session->coach)->name }}</span>
                        </div>
                        @if ($session->focus_area)
                            <p class="mt-1 text-xs text-rose-600">Focus: {{ $session->focus_area }}</p>
                        @endif
                        @if ($session->discussion_notes)
                            <p class="mt-1">{{ $session->discussion_notes }}</p>
                        @endif
                    </div>
                @empty
                    <p>No coaching records available.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 md:flex-row md:items-center md:justify-between">
            <form action="{{ route('ipcr.pmt.endorse', $ipcr) }}" method="POST">
                @csrf
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <div>
                        <label class="block text-sm font-semibold text-emerald-900">Finalize Overall Rating</label>
                        <input type="number" name="overall_score" value="{{ old('overall_score', $ipcr->overall_score) }}" min="1" max="5" step="0.01" class="mt-1 w-full rounded border-emerald-300 md:w-32" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-emerald-900">PMT Remarks</label>
                        <textarea name="remarks" rows="2" class="mt-1 w-full rounded border-emerald-300">{{ old('remarks') }}</textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Endorse to Final Approver</button>
                </div>
            </form>

            <form action="{{ route('ipcr.pmt.return', $ipcr) }}" method="POST" class="md:w-1/3">
                @csrf
                <label class="block text-sm font-semibold text-amber-900">Return to Head of Office</label>
                <div class="mt-1 flex gap-2">
                    <input type="text" name="remarks" placeholder="Reason" class="flex-1 rounded border-amber-300 text-sm" required>
                    <button type="submit" class="inline-flex items-center rounded bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700">Return</button>
                </div>
            </form>
        </div>
    </div>
@endsection
