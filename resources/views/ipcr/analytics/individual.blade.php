@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Individual Performance Report</h1>
                <p class="mt-1 text-sm text-gray-500">Trend analysis and development actions per employee.</p>
            </div>
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <select name="employee_id" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($selected_employee_id == $employee->id)>
                            {{ $employee->last_name }}, {{ $employee->first_name }}
                        </option>
                    @endforeach
                </select>
                <select name="period_id" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Periods</option>
                    @foreach ($report['records']->pluck('period')->filter()->unique('id') as $period)
                        <option value="{{ $period['id'] ?? optional($report['records']->firstWhere('period.id', $period['id'] ?? null))->period_id }}" @selected($period_id == ($period['id'] ?? optional($report['records']->firstWhere('period.id', $period['id'] ?? null))->period_id))>
                            {{ $period['name'] ?? $period }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                <p class="text-xs uppercase tracking-wide text-blue-600">Self Average</p>
                <p class="mt-2 text-2xl font-semibold text-blue-900">{{ $report['averages']['self'] ?? '—' }}</p>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                <p class="text-xs uppercase tracking-wide text-emerald-600">Supervisor Average</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-900">{{ $report['averages']['supervisor'] ?? '—' }}</p>
            </div>
            <div class="rounded-lg border border-purple-100 bg-purple-50 p-4">
                <p class="text-xs uppercase tracking-wide text-purple-600">PMT Average</p>
                <p class="mt-2 text-2xl font-semibold text-purple-900">{{ $report['averages']['pmt'] ?? '—' }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h2 class="text-lg font-medium text-gray-900">Performance Trend</h2>
            </div>
            <div class="px-4 py-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="py-2">Period</th>
                            <th class="py-2">Score</th>
                            <th class="py-2">Adjectival</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Finalized</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($report['trend'] as $item)
                            <tr>
                                <td class="py-2">{{ $item['period'] }}</td>
                                <td class="py-2">{{ $item['overall_score'] ?? '—' }}</td>
                                <td class="py-2">{{ $item['adjectival_rating'] ?? '—' }}</td>
                                <td class="py-2">{{ ucfirst(str_replace('_', ' ', $item['status'])) }}</td>
                                <td class="py-2">{{ $item['finalized_at'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-sm text-gray-500">No IPCR records available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-sky-200 bg-white shadow-sm">
                <div class="border-b border-sky-200 bg-sky-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-sky-900">Recent Progress Updates</h2>
                </div>
                <div class="px-4 py-4 space-y-3 text-sm text-sky-800">
                    @forelse ($report['progress_updates'] as $update)
                        <div class="rounded border border-sky-100 p-3">
                            <div class="flex items-center justify-between">
                                <span>{{ ucfirst(str_replace('_', ' ', $update->status)) }}</span>
                                <span class="text-xs text-sky-600">{{ $update->progress_date->format('M d, Y') }}</span>
                            </div>
                            @if ($update->accomplishments)
                                <p class="mt-1">{{ $update->accomplishments }}</p>
                            @endif
                        </div>
                    @empty
                        <p>No progress updates recorded.</p>
                    @endforelse
                </div>
            </div>
            <div class="rounded-lg border border-violet-200 bg-white shadow-sm">
                <div class="border-b border-violet-200 bg-violet-50 px-4 py-3">
                    <h2 class="text-lg font-medium text-violet-900">Development Actions</h2>
                </div>
                <div class="px-4 py-4 space-y-2 text-sm text-violet-800">
                    @forelse ($report['development_actions'] as $action)
                        <div class="rounded border border-violet-100 p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ $action->focus_area }}</span>
                                <span class="text-xs text-violet-600">{{ ucfirst(str_replace('_', ' ', $action->status)) }}</span>
                            </div>
                            <p class="mt-1">{{ $action->action_item }}</p>
                        </div>
                    @empty
                        <p>No development actions recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
