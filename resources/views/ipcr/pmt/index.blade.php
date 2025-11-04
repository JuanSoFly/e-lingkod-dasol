@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">PMT Validation Queue</h1>
                <p class="mt-1 text-sm text-gray-500">Review and calibrate IPCRs endorsed by Heads of Office.</p>
            </div>
        </div>

        <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-4">
            <h2 class="text-sm font-semibold text-blue-900">Calibration Snapshot</h2>
            <dl class="mt-2 grid grid-cols-1 gap-4 text-sm text-blue-800 md:grid-cols-3">
                <div>
                    <dt class="text-xs uppercase tracking-wide">Rated Items</dt>
                    <dd class="text-lg font-semibold">{{ $overview['count'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide">Average Supervisor Rating</dt>
                    <dd class="text-lg font-semibold">{{ $overview['average'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide">Variance</dt>
                    <dd class="text-lg font-semibold">{{ $overview['variance'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Office</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($ipcrs as $ipcr)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ optional($ipcr->employee)->first_name }} {{ optional($ipcr->employee)->last_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ optional($ipcr->office)->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ optional($ipcr->period)->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex rounded-full bg-purple-50 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-purple-700">
                                {{ str_replace('_', ' ', $ipcr->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('ipcr.pmt.show', $ipcr) }}" class="text-primary-600 hover:text-primary-700">Validate</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No IPCRs awaiting PMT validation.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $ipcrs->links() }}
        </div>
    </div>
@endsection
