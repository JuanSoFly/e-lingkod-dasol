@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Head of Office Review</h1>
                <p class="mt-1 text-sm text-gray-500">Approve or return IPCRs endorsed by supervisors.</p>
            </div>
            <form method="GET" class="flex items-center gap-3">
                <select name="status" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="for_head_approval" @selected(request('status') === 'for_head_approval')>Awaiting Head Approval</option>
                    <option value="for_pmt_validation" @selected(request('status') === 'for_pmt_validation')>Sent to PMT</option>
                </select>
                @if(request()->has('status'))
                    <a href="{{ route('ipcr.head.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reset</a>
                @endif
            </form>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Supervisor Reviewed</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($ipcrs as $ipcr)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ optional($ipcr->employee)->first_name }} {{ optional($ipcr->employee)->last_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ optional($ipcr->period)->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">
                                {{ str_replace('_', ' ', $ipcr->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ optional($ipcr->supervisor_reviewed_at)->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('ipcr.head.show', $ipcr) }}" class="text-primary-600 hover:text-primary-700">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No IPCRs awaiting Head of Office action.</td>
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
