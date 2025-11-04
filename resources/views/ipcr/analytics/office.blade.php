@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Office Performance Analytics</h1>
                <p class="mt-1 text-sm text-gray-500">Aggregated scores and completion rates by office.</p>
            </div>
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <select name="period_id" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Periods</option>
                    @foreach ($analytics['periods'] as $period)
                        <option value="{{ $period->id }}" @selected($filters['period_id'] ?? '' == $period->id)>{{ $period->name }}</option>
                    @endforeach
                </select>
                <select name="office_id" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Offices</option>
                    @foreach ($analytics['offices'] as $office)
                        <option value="{{ $office->id }}" @selected(($filters['office_id'] ?? '') == $office->id)>{{ $office->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                <p class="text-xs uppercase tracking-wide text-blue-600">Total IPCRs</p>
                <p class="mt-2 text-2xl font-semibold text-blue-900">{{ $analytics['records']->count() }}</p>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                <p class="text-xs uppercase tracking-wide text-emerald-600">Locked</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-900">{{ $analytics['records']->where('status', 'locked')->count() }}</p>
            </div>
            <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                <p class="text-xs uppercase tracking-wide text-amber-600">Pending</p>
                <p class="mt-2 text-2xl font-semibold text-amber-900">{{ $analytics['records']->whereIn('status', ['for_supervisor_review','for_head_approval','for_pmt_validation'])->count() }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h2 class="text-lg font-medium text-gray-900">Average Scores by Office</h2>
            </div>
            <div class="px-4 py-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="py-2">Office</th>
                            <th class="py-2">Average Score</th>
                            <th class="py-2">Locked</th>
                            <th class="py-2">Pending</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($analytics['by_office'] as $office)
                            <tr>
                                <td class="py-2">{{ $office['office'] }}</td>
                                <td class="py-2">{{ $office['average_score'] ?? '—' }}</td>
                                <td class="py-2">{{ $office['locked_count'] }}</td>
                                <td class="py-2">{{ $office['pending_count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-sm text-gray-500">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-purple-200 bg-white shadow-sm">
            <div class="border-b border-purple-200 bg-purple-50 px-4 py-3">
                <h2 class="text-lg font-medium text-purple-900">Status Distribution</h2>
            </div>
            <div class="px-4 py-4">
                <ul class="space-y-2 text-sm text-purple-800">
                    @foreach ($analytics['status_distribution'] as $status => $count)
                        <li class="flex items-center justify-between">
                            <span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                            <span class="font-semibold">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
