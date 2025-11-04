@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Final Approval Queue</h1>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">PMT Score</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($ipcrs as $ipcr)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ optional($ipcr->employee)->first_name }} {{ optional($ipcr->employee)->last_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ optional($ipcr->period)->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex rounded-full bg-purple-50 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-purple-700">
                                {{ str_replace('_', ' ', $ipcr->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ number_format($ipcr->overall_score, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('ipcr.final.show', $ipcr) }}" class="text-primary-600 hover:text-primary-700">Finalize</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No IPCRs awaiting final approval.</td>
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
