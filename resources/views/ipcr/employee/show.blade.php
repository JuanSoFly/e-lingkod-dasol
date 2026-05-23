@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 leading-tight">
                    {{ optional($ipcr->period)->name }} IPCR
                </h1>
                <p class="mt-1.5 text-sm text-gray-500">Evaluation and monitoring sheet for employee performance.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-50 text-gray-700 border-gray-205',
                        'for_supervisor_review' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'supervisor_approved' => 'bg-blue-50 text-blue-700 border-blue-100',
                        'for_pmt_review' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                        'pmt_approved' => 'bg-purple-50 text-purple-700 border-purple-100',
                        'final_approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                    ];
                    $colorClass = $statusColors[$ipcr->status] ?? 'bg-gray-50 text-gray-700 border-gray-205';
                @endphp
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border {{ $colorClass }} select-none">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ ucwords(str_replace('_', ' ', $ipcr->status)) }}
                </span>
                <a href="{{ route('ipcr.employee.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-semibold transition-all shadow-sm">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to list
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
            <!-- Assessment area (Left columns) -->
            <div class="lg:col-span-2 space-y-6">
                <form action="{{ route('ipcr.employee.update', $ipcr) }}" method="POST" class="bg-white border border-gray-250/80 rounded-2xl shadow-sm overflow-hidden">
                    @csrf
                    @method('PATCH')

                    <div class="border-b border-gray-150 bg-gray-50/50 px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900">Targets &amp; Self Assessment</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Please rate each indicator and add self comments before submission.</p>
                    </div>

                    <div class="space-y-6 p-6">
                        @foreach ($ipcr->items as $item)
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
                                        <input type="number" name="items[{{ $loop->index }}][weight]" value="{{ old("items.$loop->index.weight", $item->weight) }}" step="0.01" min="0" max="100" class="w-16 bg-transparent border-0 p-0 text-right font-mono text-sm font-bold text-gray-900 focus:ring-0 focus:outline-none" />
                                        <span class="text-xs font-bold text-gray-500">%</span>
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

                                <!-- Self assessment inputs -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="sm:col-span-1">
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Self Rating (1-5)</label>
                                        <input type="number" name="items[{{ $loop->index }}][self_rating]" value="{{ old("items.$loop->index.self_rating", $item->self_rating) }}" min="1" max="5" step="0.01" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-semibold text-gray-900" />
                                        @error("items.$loop->index.self_rating")
                                            <p class="mt-1 text-xs text-red-650 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Self Remarks / Comments</label>
                                        <textarea name="items[{{ $loop->index }}][remarks]" rows="2" placeholder="Describe achievements, issues met, or metrics met..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-gray-700">{{ old("items.$loop->index.remarks", $item->remarks) }}</textarea>
                                        @error("items.$loop->index.remarks")
                                            <p class="mt-1 text-xs text-red-650 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                            </div>
                        @endforeach

                        <div class="pt-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Overall Remarks / Summary</label>
                            <textarea name="remarks" rows="3" placeholder="Enter overall performance narrative or review notes..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-gray-700">{{ old('remarks', $ipcr->remarks) }}</textarea>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-150 bg-gray-50/50 px-6 py-4">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Ensure target weights sum up to 100% before submission.</span>
                        </div>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-xl text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150 whitespace-nowrap">
                            Save Assessment Changes
                        </button>
                    </div>
                </form>

                @if (in_array(App\Services\IpcrWorkflowService::STATE_FOR_SUPERVISOR_REVIEW, $availableTransitions))
                    <form action="{{ route('ipcr.employee.submit', $ipcr) }}" method="POST" class="bg-amber-50/20 border border-amber-100 rounded-2xl p-6 space-y-4">
                        @csrf
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-amber-50 text-amber-700 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-amber-900">Submit IPCR for Supervisor Review</h3>
                                <p class="text-sm text-amber-700/80 mt-0.5">Ready to lock and submit this evaluation period? You won't be able to edit weights and self ratings once submitted.</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-amber-800 uppercase tracking-wider">Remarks to Supervisor</label>
                            <textarea name="remarks" rows="2" placeholder="Optional remarks, notes or cover note for your supervisor..." class="w-full rounded-lg border-amber-250 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm text-gray-800"></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 bg-amber-600 border border-transparent rounded-xl text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors duration-150">
                                Submit Assessment
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- Sidebar (Right columns) -->
            <div class="space-y-6">
                <!-- Progress Updates Card -->
                <div class="bg-white border border-sky-200 rounded-2xl shadow-sm overflow-hidden bg-sky-50/10">
                    <div class="border-b border-sky-100 bg-sky-50/50 px-5 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-sky-900">Progress Updates</h3>
                            <p class="text-xs text-sky-700 mt-0.5 font-medium">Risk Level: <span class="font-bold text-sky-800">{{ ucfirst($progressSnapshot['risk_level'] ?? 'low') }}</span></p>
                        </div>
                    </div>
                    <div class="p-5 space-y-6">
                        <form action="{{ route('ipcr.employee.progress.store', $ipcr) }}" method="POST" class="bg-white border border-sky-100 rounded-xl p-4 space-y-3.5 shadow-sm">
                            @csrf
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-sky-850 uppercase tracking-wider mb-1">Date</label>
                                    <input type="date" name="progress_date" value="{{ old('progress_date', now()->toDateString()) }}" class="w-full rounded-lg border-sky-100 text-sm text-gray-800 focus:border-sky-500 focus:ring-sky-500 py-1.5" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-sky-850 uppercase tracking-wider mb-1">Status</label>
                                    <select name="status" class="w-full rounded-lg border-sky-100 text-sm text-gray-800 focus:border-sky-500 focus:ring-sky-500 py-1.5">
                                        @foreach ([
                                            'on_track' => 'On Track',
                                            'at_risk' => 'At Risk',
                                            'delayed' => 'Delayed',
                                            'critical' => 'Critical',
                                        ] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-sky-850 uppercase tracking-wider mb-1">Related Target</label>
                                    <select name="ipcr_item_id" class="w-full rounded-lg border-sky-100 text-sm text-gray-800 focus:border-sky-500 focus:ring-sky-500 py-1.5">
                                        <option value="">General Update</option>
                                        @foreach ($ipcr->items as $item)
                                            <option value="{{ $item->id }}" @selected(old('ipcr_item_id') == $item->id)>{{ $item->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <textarea name="accomplishments" rows="2" placeholder="Accomplishments..." class="w-full rounded-lg border-sky-100 text-xs text-gray-700 placeholder-gray-400 focus:border-sky-500 focus:ring-sky-500"></textarea>
                                <textarea name="challenges" rows="2" placeholder="Challenges..." class="w-full rounded-lg border-sky-100 text-xs text-gray-700 placeholder-gray-400 focus:border-sky-500 focus:ring-sky-500"></textarea>
                                <textarea name="next_steps" rows="2" placeholder="Next Steps..." class="w-full rounded-lg border-sky-100 text-xs text-gray-700 placeholder-gray-400 focus:border-sky-500 focus:ring-sky-500"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-sm">
                                    Log Progress
                                </button>
                            </div>
                        </form>

                        <div class="space-y-4">
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
                                    <p class="text-xs font-bold text-indigo-700 leading-tight">{{ optional($update->item)->title ?? 'General System Update' }}</p>
                                    
                                    @if ($update->accomplishments)
                                        <div class="text-gray-800 text-xs pt-1 border-t border-gray-50">
                                            <strong class="text-gray-500 block text-[10px] uppercase font-bold tracking-wider">Accomplishments:</strong>
                                            <p class="mt-0.5 leading-relaxed text-gray-750">{{ $update->accomplishments }}</p>
                                        </div>
                                    @endif
                                    @if ($update->challenges)
                                        <div class="text-gray-800 text-xs pt-1 border-t border-gray-50">
                                            <strong class="text-gray-500 block text-[10px] uppercase font-bold tracking-wider">Challenges:</strong>
                                            <p class="mt-0.5 leading-relaxed text-gray-750">{{ $update->challenges }}</p>
                                        </div>
                                    @endif
                                    @if ($update->next_steps)
                                        <div class="text-gray-800 text-xs pt-1 border-t border-gray-50">
                                            <strong class="text-gray-500 block text-[10px] uppercase font-bold tracking-wider">Next Steps:</strong>
                                            <p class="mt-0.5 leading-relaxed text-gray-750">{{ $update->next_steps }}</p>
                                        </div>
                                    @endif
                                    <div class="text-[10px] text-gray-450 pt-1 text-right italic font-medium">
                                        Reported by {{ optional($update->reporter)->name }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-sky-700 text-center py-4 italic">No progress updates recorded yet.</p>
                            @endforelse
                        </div>
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
                                <p class="text-xs text-gray-700 leading-relaxed">{{ $action->action_item }}</p>
                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border bg-violet-50 text-violet-700 border-violet-150">
                                        Status: {{ ucwords(str_replace('_', ' ', $action->status)) }}
                                    </span>
                                    @if ($action->support_needed)
                                        <span class="text-[10px] text-violet-600 italic">Support: {{ $action->support_needed }}</span>
                                    @endif
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
                                    <p class="text-xs text-gray-700 leading-relaxed pt-1">{{ $session->discussion_notes }}</p>
                                @endif
                                <div class="flex items-center justify-between pt-1 border-t border-gray-50 text-[10px] text-gray-450">
                                    <span>Coach: {{ optional($session->coach)->name }}</span>
                                    @if ($session->follow_up_date)
                                        <span class="font-mono text-rose-600">Follow-up: {{ $session->follow_up_date->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-rose-750 text-center py-4 italic">No coaching sessions recorded.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Workflow History Card -->
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="border-b border-gray-150 bg-gray-50/50 px-5 py-4">
                        <h3 class="font-bold text-gray-950">Workflow History</h3>
                    </div>
                    <div class="p-5">
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @forelse ($ipcr->workflowLogs as $log)
                                    <li>
                                        <div class="relative pb-8">
                                            @if (!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center ring-8 ring-white text-indigo-600">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0 pt-1.5 flex justify-between gap-4 text-xs">
                                                    <div>
                                                        <p class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $log->from_state ?? 'start')) }} &rarr; {{ ucfirst(str_replace('_', ' ', $log->to_state)) }}</p>
                                                        <p class="text-[10px] text-gray-500 mt-0.5 font-medium">{{ optional($log->user)->name ?? 'System' }}</p>
                                                        @if ($log->remarks)
                                                            <div class="mt-1.5 text-gray-600 p-2 bg-gray-50 rounded-lg border border-gray-150 italic leading-relaxed">
                                                                "{{ $log->remarks }}"
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="text-right text-gray-400 whitespace-nowrap font-mono text-[10px]">
                                                        {{ optional($log->performed_at)->format('M d, Y H:i') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-xs text-gray-500 text-center py-4 italic">No workflow activity recorded yet.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
