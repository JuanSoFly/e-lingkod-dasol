@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 leading-tight">
                    PMT Validation &mdash; {{ optional($ipcr->employee)->first_name }} {{ optional($ipcr->employee)->last_name }}
                </h1>
                <p class="mt-1.5 text-sm text-gray-500">
                    Period: {{ optional($ipcr->period)->name }} | Office: {{ optional($ipcr->office)->name }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('ipcr.pmt.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-semibold transition-all shadow-sm">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to queue
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-sm text-emerald-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Validation & Calibration area (Left columns) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Calibration Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Self Average</span>
                        <span class="block mt-2 text-2xl font-extrabold text-indigo-600 font-mono">{{ $calibration['self_average'] ?? '—' }}</span>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Supervisor Average</span>
                        <span class="block mt-2 text-2xl font-extrabold text-blue-600 font-mono">{{ $calibration['supervisor_average'] ?? '—' }}</span>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">PMT Average</span>
                        <span class="block mt-2 text-2xl font-extrabold text-emerald-600 font-mono">{{ $calibration['pmt_average'] ?? '—' }}</span>
                    </div>
                </div>

                <!-- Main Validation Form -->
                <form action="{{ route('ipcr.pmt.validate', $ipcr) }}" method="POST" class="bg-white border border-gray-250/80 rounded-2xl shadow-sm overflow-hidden">
                    @csrf

                    <div class="border-b border-gray-150 bg-gray-50/50 px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900">Rating Calibration</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Validate the ratings against employee targets and supervisor inputs.</p>
                    </div>

                    <div class="space-y-6 p-6">
                        @foreach ($ipcr->items as $index => $item)
                            <div class="rounded-xl border border-gray-200 bg-white p-5 space-y-4 hover:border-gray-300 transition-colors shadow-sm relative">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-indigo-50 text-indigo-700 text-xs font-bold">{{ $loop->iteration }}</span>
                                            {{ $item->title }}
                                        </h3>
                                        <p class="mt-1.5 text-sm text-gray-600 leading-relaxed">{{ $item->description }}</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 sm:self-start bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-lg whitespace-nowrap">
                                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Weight:</span>
                                        <span class="font-mono text-sm font-bold text-gray-900">{{ $item->weight }}%</span>
                                    </div>
                                </div>

                                <!-- Target details -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-b border-gray-100 py-3 text-sm">
                                    <div class="p-3 bg-gray-50/50 rounded-lg border border-gray-100">
                                        <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Quality Target</span>
                                        <span class="block mt-1 font-semibold text-gray-800">{{ $item->target_quality ?? 'N/A' }}</span>
                                    </div>
                                    <div class="p-3 bg-gray-50/50 rounded-lg border border-gray-100">
                                        <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Efficiency Target</span>
                                        <span class="block mt-1 font-semibold text-gray-800">{{ $item->target_efficiency ?? 'N/A' }}</span>
                                    </div>
                                    <div class="p-3 bg-gray-50/50 rounded-lg border border-gray-100">
                                        <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider">Timeliness Target</span>
                                        <span class="block mt-1 font-semibold text-gray-800">{{ $item->target_timeliness ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <!-- Rating Comparison & Calibration Inputs -->
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                    <div class="sm:col-span-1 p-3 bg-indigo-50/30 border border-indigo-100 rounded-lg text-sm">
                                        <span class="block text-xs font-bold text-indigo-500 uppercase tracking-wider">Self Rating</span>
                                        <span class="block mt-1 font-mono font-bold text-indigo-950">{{ $item->self_rating ?? '—' }}</span>
                                    </div>
                                    <div class="sm:col-span-1 p-3 bg-blue-50/30 border border-blue-100 rounded-lg text-sm">
                                        <span class="block text-xs font-bold text-blue-500 uppercase tracking-wider">Supervisor Rating</span>
                                        <span class="block mt-1 font-mono font-bold text-blue-950">{{ $item->supervisor_rating ?? '—' }}</span>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">PMT Rating</label>
                                        <input type="number" name="items[{{ $index }}][pmt_rating]" value="{{ old("items.$index.pmt_rating", $item->pmt_rating) }}" min="1" max="5" step="0.01" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-semibold text-gray-900" />
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Comments</label>
                                        <textarea name="items[{{ $index }}][pmt_comments]" rows="2" placeholder="Validator notes..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs text-gray-700">{{ old("items.$index.pmt_comments", data_get($item->pmt_rating_details, 'comments')) }}</textarea>
                                    </div>
                                </div>

                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                            </div>
                        @endforeach

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-4 border-t border-gray-150">
                            <div class="sm:col-span-1">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Recommended Overall Rating</label>
                                <input type="number" name="recommended_rating" value="{{ old('recommended_rating', $ipcr->overall_score) }}" min="1" max="5" step="0.01" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-semibold text-gray-900" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Validation Remarks</label>
                                <textarea name="remarks" rows="2" placeholder="Describe recommendations or validation updates..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-gray-700">{{ old('remarks') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-150 bg-gray-50/50 px-6 py-4">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-xl text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150 whitespace-nowrap">
                            Save Validation Changes
                        </button>
                    </div>
                </form>

                <!-- Action workflows (Endorse / Return) -->
                <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 space-y-6">
                    <h3 class="text-base font-bold text-gray-900">PMT Action Workflow</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                        <!-- Endorse to Final Approver -->
                        <form action="{{ route('ipcr.pmt.endorse', $ipcr) }}" method="POST" class="bg-emerald-50/20 border border-emerald-100 rounded-xl p-5 space-y-4 shadow-sm">
                            @csrf
                            <h4 class="text-sm font-bold text-emerald-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Endorse to Final Approver
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-emerald-800 uppercase tracking-wider mb-1">Final Overall Score</label>
                                    <input type="number" name="overall_score" value="{{ old('overall_score', $ipcr->overall_score) }}" min="1" max="5" step="0.01" class="w-full rounded-lg border-emerald-250 bg-white shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-semibold text-gray-900" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-emerald-800 uppercase tracking-wider mb-1">Endorsement Remarks</label>
                                    <textarea name="remarks" rows="2" placeholder="Summary notes to the final approver..." class="w-full rounded-lg border-emerald-250 bg-white shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-xs text-gray-855">{{ old('remarks') }}</textarea>
                                </div>
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-emerald-600 border border-transparent rounded-lg text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150">
                                Endorse Assessment
                            </button>
                        </form>

                        <!-- Return to Head of Office -->
                        <form action="{{ route('ipcr.pmt.return', $ipcr) }}" method="POST" class="bg-amber-50/20 border border-amber-100 rounded-xl p-5 space-y-4 shadow-sm">
                            @csrf
                            <h4 class="text-sm font-bold text-amber-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                                </svg>
                                Return to Head of Office
                            </h4>
                            <p class="text-xs text-amber-850">Send this evaluation back to the department head for ratings readjustments or revisions.</p>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-amber-800 uppercase tracking-wider mb-1">Reason for Return</label>
                                    <input type="text" name="remarks" placeholder="Provide specific reasons..." class="w-full rounded-lg border-amber-250 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-xs text-gray-900" required />
                                </div>
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-amber-600 border border-transparent rounded-lg text-xs font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors duration-150">
                                Return for Revisions
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Right columns) -->
            <div class="space-y-6">
                <!-- Recent Progress updates Card -->
                <div class="bg-white border border-sky-200 rounded-2xl shadow-sm overflow-hidden bg-sky-50/10">
                    <div class="border-b border-sky-100 bg-sky-50/50 px-5 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-sky-900">Recent Progress</h3>
                            <p class="text-xs text-sky-700 mt-0.5 font-medium">Risk Level: <span class="font-bold text-sky-800">{{ ucfirst($progressSnapshot['risk_level'] ?? 'low') }}</span></p>
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        @forelse ($progressUpdates as $update)
                            <div class="bg-white border border-sky-100 p-4 rounded-xl shadow-sm text-sm space-y-2 relative">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border
                                        @if($update->status == 'on_track') bg-emerald-50 text-emerald-700 border-emerald-150
                                        @elseif($update->status == 'at_risk') bg-amber-50 text-amber-700 border-amber-150
                                        @elseif($update->status == 'delayed') bg-orange-50 text-orange-700 border-orange-150
                                        @elseif($update->status == 'critical') bg-rose-50 text-rose-700 border-rose-150
                                        @else bg-gray-50 text-gray-700 border-gray-150 @endif">
                                        {{ ucfirst(str_replace('_', ' ', $update->status)) }}
                                    </span>
                                    <span class="text-xs text-gray-400 font-mono">{{ $update->progress_date->format('M d, Y') }}</span>
                                </div>
                                @if ($update->accomplishments)
                                    <div class="text-gray-800 text-xs pt-1">
                                        <strong class="text-gray-500 block text-[10px] uppercase font-bold tracking-wider">Accomplishments:</strong>
                                        <p class="mt-0.5 leading-relaxed text-gray-750">{{ $update->accomplishments }}</p>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-sky-700 text-center py-4 italic">No progress updates recorded.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Development Actions Card -->
                <div class="bg-white border border-violet-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="border-b border-violet-100 bg-violet-50/50 px-5 py-4">
                        <h3 class="font-bold text-violet-900">Development Actions</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        @forelse ($developmentActions as $action)
                            <div class="rounded-xl border border-violet-100 p-4 bg-white/60 text-sm space-y-2">
                                <div class="flex items-center justify-between text-violet-900">
                                    <span class="font-bold">{{ $action->focus_area }}</span>
                                    <span class="text-xs text-gray-400 font-mono">Target: {{ optional($action->target_date)->format('M d, Y') ?? 'N/A' }}</span>
                                </div>
                                <p class="text-xs text-gray-705 leading-relaxed">{{ $action->action_item }}</p>
                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border bg-violet-50 text-violet-700 border-violet-150">
                                        Status: {{ ucwords(str_replace('_', ' ', $action->status)) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-violet-750 text-center py-4 italic">No development actions logged yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Coaching Sessions Card -->
                <div class="bg-white border border-rose-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="border-b border-rose-100 bg-rose-50/50 px-5 py-4">
                        <h3 class="font-bold text-rose-900">Coaching Sessions</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        @forelse ($coachingSessions as $session)
                            <div class="rounded-xl border border-rose-100 p-4 bg-white/60 text-sm space-y-2">
                                <div class="flex items-center justify-between text-rose-900">
                                    <span class="font-bold">{{ $session->session_type }}</span>
                                    <span class="text-xs text-gray-400 font-mono">{{ $session->session_date->format('M d, Y') }}</span>
                                </div>
                                @if ($session->focus_area)
                                    <p class="text-xs text-rose-700 italic">Focus: {{ $session->focus_area }}</p>
                                @endif
                                @if ($session->discussion_notes)
                                    <p class="text-xs text-gray-705 leading-relaxed pt-1">{{ $session->discussion_notes }}</p>
                                @endif
                                <div class="flex items-center justify-between pt-1 border-t border-gray-50 text-[10px] text-gray-450">
                                    <span>Coach: {{ optional($session->coach)->name }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-rose-750 text-center py-4 italic">No coaching sessions recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
