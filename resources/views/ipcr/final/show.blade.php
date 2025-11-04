@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Finalize IPCR</h1>
                <p class="mt-1 text-sm text-gray-500">{{ optional($ipcr->employee)->first_name }} {{ optional($ipcr->employee)->last_name }} — {{ optional($ipcr->period)->name }}</p>
            </div>
            <a href="{{ route('ipcr.final.index') }}" class="text-primary-600 hover:text-primary-700">Back</a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h2 class="text-lg font-medium text-gray-900">Summary</h2>
            </div>
            <div class="px-4 py-5">
                <dl class="grid grid-cols-1 gap-4 text-sm text-gray-600 md:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">PMT Recommended</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ number_format($ipcr->overall_score, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Current Rating</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $ipcr->adjectival_rating ?? 'Pending' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <form action="{{ route('ipcr.final.finalize', $ipcr) }}" method="POST" class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-emerald-900">Final Score</label>
                    <input type="number" name="final_score" value="{{ old('final_score', $ipcr->overall_score) }}" min="1" max="5" step="0.01" class="mt-1 w-full rounded border-emerald-300" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-emerald-900">Performance Level</label>
                    <input type="text" name="performance_level" value="{{ old('performance_level', optional($ipcr->finalRating)->performance_level) }}" class="mt-1 w-full rounded border-emerald-300" placeholder="e.g., Level 5">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-emerald-900">Remarks</label>
                <textarea name="remarks" rows="3" class="mt-1 w-full rounded border-emerald-300">{{ old('remarks', optional($ipcr->finalRating)->remarks) }}</textarea>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="inline-flex items-center rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Lock IPCR</button>
            </div>
        </form>

        <div class="mt-6 rounded-lg border border-sky-200 bg-white shadow-sm">
            <div class="border-b border-sky-200 bg-sky-50 px-4 py-3">
                <h2 class="text-lg font-medium text-sky-900">Recent Progress Updates</h2>
            </div>
            <div class="px-4 py-4 space-y-2 text-sm text-sky-800">
                @forelse ($progressUpdates as $update)
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
                    <p>No recent updates.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h2 class="text-lg font-medium text-gray-900">Targets</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach ($ipcr->items as $item)
                    <div class="px-4 py-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $item->title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ $item->description }}</p>
                        <dl class="mt-2 grid grid-cols-1 gap-4 text-xs text-gray-500 md:grid-cols-4">
                            <div><dt>Self</dt><dd class="text-gray-900">{{ $item->self_rating ?? '—' }}</dd></div>
                            <div><dt>Supervisor</dt><dd class="text-gray-900">{{ $item->supervisor_rating ?? '—' }}</dd></div>
                            <div><dt>PMT</dt><dd class="text-gray-900">{{ $item->pmt_rating ?? '—' }}</dd></div>
                            <div><dt>Weight</dt><dd class="text-gray-900">{{ number_format($item->weight, 2) }}%</dd></div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
