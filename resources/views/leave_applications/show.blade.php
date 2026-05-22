<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Leave Application Details') }}
                </h2>
            </div>
            @if(!request()->query('modal'))
            <div>
                <a href="{{ route('leave-applications.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors duration-150">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 bg-white">
                    <!-- Header Section -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-gray-150 gap-4">
                        <div>
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Leave Application</span>
                            <h3 class="text-xl font-bold text-gray-900 mt-1">Request Information</h3>
                        </div>
                        <div>
                            @if($leaveApplication->status == 'approved')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 select-none">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-emerald-500 rounded-full"></span>
                                    Approved
                                </span>
                            @elseif($leaveApplication->status == 'rejected')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 select-none">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-rose-500 rounded-full"></span>
                                    Rejected
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 select-none">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-amber-500 rounded-full"></span>
                                    Pending Approval
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8 py-8 border-b border-gray-100">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-slate-50 text-slate-500 rounded-lg border border-slate-100 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Employee</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $leaveApplication->employee->full_name }}</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-slate-50 text-slate-500 rounded-lg border border-slate-100 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Leave Type</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $leaveApplication->leaveType->name }}</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-slate-50 text-slate-500 rounded-lg border border-slate-100 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Dates Requested</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $leaveApplication->start_date->format('M d, Y') }} to {{ $leaveApplication->end_date->format('M d, Y') }}</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-slate-50 text-slate-500 rounded-lg border border-slate-100 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Duration</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $leaveApplication->days_requested }} {{ Str::plural('Day', $leaveApplication->days_requested) }}</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-slate-50 text-slate-500 rounded-lg border border-slate-100 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Applied On</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $leaveApplication->applied_date->format('M d, Y') }}</dd>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Reason for Leave</dt>
                            <dd class="mt-2 text-sm text-gray-700 bg-gray-50/50 border border-gray-150 rounded-xl p-4 leading-relaxed">{{ $leaveApplication->reason }}</dd>
                        </div>

                        @if($leaveApplication->status != 'pending')
                            <div class="md:col-span-2 pt-6 border-t border-gray-100">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Remarks / Comments</dt>
                                        <dd class="mt-2 text-sm text-gray-700 bg-gray-50/50 border border-gray-150 rounded-xl p-4 leading-relaxed italic">{{ $leaveApplication->remarks ?: 'No remarks provided.' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Processed By</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $leaveApplication->approver->name ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Processed Date</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $leaveApplication->approved_date?->format('M d, Y') ?? 'N/A' }}</dd>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Approve/Reject Actions Section -->
                    @can('leave.approve')
                        @if($leaveApplication->status === 'pending')
                            <div class="pt-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Take Action</h3>
                                <p class="text-sm text-gray-500 mb-6">Review the application details and choose an action below. If rejecting, a descriptive remark is required.</p>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                                    <!-- Approve Form -->
                                    <form action="{{ route('leave-applications.approve', $leaveApplication) }}" method="POST" class="flex-1 sm:flex-none">
                                        @csrf
                                        @method('PATCH')
                                        <x-primary-button class="w-full sm:w-auto">Approve Application</x-primary-button>
                                    </form>
                                    <!-- Reject Form -->
                                    <form action="{{ route('leave-applications.reject', $leaveApplication) }}" method="POST" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                            <div class="flex-1">
                                                <x-text-input name="remarks" placeholder="Reason for rejection..." required class="block w-full" />
                                            </div>
                                            <x-danger-button class="w-full sm:w-auto">Reject Application</x-danger-button>
                                        </div>
                                        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>