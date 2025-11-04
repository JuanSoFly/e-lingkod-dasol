@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Compliance Snapshot</h1>
                <p class="mt-1 text-sm text-gray-500">Track consolidation progress for CSC reporting and PBB readiness.</p>
            </div>
            <form method="GET" class="flex items-center gap-3">
                <select name="period_id" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected($period_id == $period->id)>{{ $period->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                <p class="text-xs uppercase tracking-wide text-blue-600">Total IPCRs</p>
                <p class="mt-2 text-2xl font-semibold text-blue-900">{{ $snapshot['total'] }}</p>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                <p class="text-xs uppercase tracking-wide text-emerald-600">Locked</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-900">{{ $snapshot['locked'] }}</p>
            </div>
            <div class="rounded-lg border border-purple-100 bg-purple-50 p-4">
                <p class="text-xs uppercase tracking-wide text-purple-600">Validated</p>
                <p class="mt-2 text-2xl font-semibold text-purple-900">{{ $snapshot['validated'] }}</p>
            </div>
            <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                <p class="text-xs uppercase tracking-wide text-amber-600">Submitted</p>
                <p class="mt-2 text-2xl font-semibold text-amber-900">{{ $snapshot['submitted'] }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-gray-900">Status Distribution</h2>
                </div>
                <div class="px-4 py-4">
                    <ul class="space-y-2 text-sm text-gray-700">
                        @foreach ($snapshot['status_counts'] as $status => $count)
                            <li class="flex items-center justify-between">
                                <span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                <span class="font-semibold">{{ $count }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="rounded-lg border border-rose-200 bg-white shadow-sm">
                <div class="border-b border-rose-200 bg-rose-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-rose-900">Non-compliant IPCRs</h2>
                    <p class="text-xs text-rose-700">Records missing submission, validation, or final lock.</p>
                </div>
                <div class="px-4 py-4 space-y-2 text-sm text-rose-800">
                    @forelse ($snapshot['non_compliant'] as $record)
                        <div class="rounded border border-rose-100 p-3">
                            <div class="flex items-center justify-between">
                                <span>{{ optional($record->employee)->full_name ?? optional($record->employee)->first_name }}</span>
                                <span class="text-xs text-rose-600">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-rose-600">Period: {{ optional($record->period)->name }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-rose-700">All IPCRs are compliant for the selected period.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
