@extends('layouts.full-width')

@section('content')
<div class="min-h-screen bg-gray-50/50">
    <!-- Hero Section with Profile -->
    <div class="relative z-0 bg-blue-600 bg-gradient-to-r from-blue-600 to-indigo-700 pb-10 isolation-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 flex-wrap">
                <div class="text-white">
                    <p class="text-blue-100 font-medium mb-1">Welcome back,</p>
                    <h1 class="text-4xl font-bold tracking-tight">{{ $employee->full_name }}</h1>
                    <p class="text-blue-100 mt-2 flex items-center gap-2">
                        <span>{{ now()->format('l, F j, Y') }}</span>
                    </p>
                </div>
                
                <!-- Enhanced Employee Info Card -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 text-white min-w-[300px]">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center text-xl font-bold border-2 border-white/30">
                            {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="opacity-70">ID:</span>
                                <span class="font-mono font-medium tracking-wide">{{ $employee->employee_number }}</span>
                            </div>
                            <div class="font-medium truncate max-w-[200px]" title="{{ $employee->position }}">
                                {{ $employee->position }}
                            </div>
                            <div class="text-sm opacity-80 truncate max-w-[200px]" title="{{ $employee->office?->name ?? $employee->department }}">
                                {{ $employee->office?->name ?? $employee->department }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column (Metrics & Actions) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Quick Actions Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-bolt text-amber-500"></i>
                        Quick Actions
                    </h2>
                    
                    @php
                        $quickActionGridLgCols = config('employee_portal.features.document_services') ? 'lg:grid-cols-3' : 'lg:grid-cols-2';
                    @endphp
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 {{ $quickActionGridLgCols }} gap-4">
                        <a href="{{ route('employee-portal.dashboard.leave') }}" 
                           class="group p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-blue-50 hover:border-blue-100 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-blue-100 text-blue-600 rounded-lg group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-calendar-check text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 group-hover:text-blue-700">Leave Dashboard</h3>
                                    <p class="text-sm text-gray-500 mt-1">Manage applications</p>
                                </div>
                            </div>
                        </a>

                        @if (config('employee_portal.features.document_services'))
                        <a href="{{ route('employee-portal.document-requests') }}" 
                           class="group p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-emerald-50 hover:border-emerald-100 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-file-contract text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 group-hover:text-emerald-700">Request Documents</h3>
                                    <p class="text-sm text-gray-500 mt-1">Official certificates</p>
                                </div>
                            </div>
                        </a>
                        @endif

                        <a href="{{ route('pds.dashboard', auth()->user()->employee) }}" 
                           class="group p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-amber-50 hover:border-amber-100 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-amber-100 text-amber-600 rounded-lg group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-user-cog text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 group-hover:text-amber-700">Personal Data Sheet</h3>
                                    <p class="text-sm text-gray-500 mt-1">Update PDS info</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('employee-portal.service-record') }}" 
                           class="group p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-purple-50 hover:border-purple-100 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-purple-100 text-purple-600 rounded-lg group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-scroll text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 group-hover:text-purple-700">Service Record</h3>
                                    <p class="text-sm text-gray-500 mt-1">Timeline & history</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="{{ route('employee-portal.my-201-file') }}" 
                           class="group p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-rose-50 hover:border-rose-100 transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-rose-100 text-rose-600 rounded-lg group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-folder text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 group-hover:text-rose-700">201 File</h3>
                                    <p class="text-sm text-gray-500 mt-1">Digital records</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Metrics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Leave Balance Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-gray-500 text-sm font-medium">Leave Credits</h3>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $metrics['leave_balance']['total'] ?? 0 }}</p>
                            </div>
                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                <i class="fas fa-wallet text-xl"></i>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 mb-4">
                            <span class="font-medium text-gray-900">{{ $metrics['leave_balance']['used_this_year'] ?? 0 }}</span> days used this year
                        </div>
                        
                        @if(($metrics['leave_balance']['pending_applications'] ?? 0) > 0)
                            <div class="bg-amber-50 text-amber-800 text-xs px-3 py-2 rounded-lg inline-flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                {{ $metrics['leave_balance']['pending_applications'] }} pending approval
                            </div>
                        @else
                            <div class="text-xs text-gray-400">No pending applications</div>
                        @endif
                    </div>


                    <!-- Document Requests (Conditional) -->
                    @if (config('employee_portal.features.document_services'))
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-gray-500 text-sm font-medium">Document Requests</h3>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $metrics['document_requests']['total_this_year'] ?? 0 }}</p>
                            </div>
                            <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                                <i class="fas fa-file-contract text-xl"></i>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 mb-4">
                            <span class="font-medium text-gray-900">{{ $metrics['document_requests']['total_this_year'] ?? 0 }}</span> requests this year
                        </div>
                        <div class="flex gap-2">
                             @if(($metrics['document_requests']['pending'] ?? 0) > 0)
                                <span class="bg-amber-50 text-amber-800 text-xs px-2.5 py-1 rounded-md border border-amber-100">
                                    {{ $metrics['document_requests']['pending'] }} pending
                                </span>
                            @endif
                            @if(($metrics['document_requests']['ready'] ?? 0) > 0)
                                <span class="bg-emerald-50 text-emerald-800 text-xs px-2.5 py-1 rounded-md border border-emerald-100">
                                    {{ $metrics['document_requests']['ready'] }} ready
                                </span>
                            @endif
                            @if(($metrics['document_requests']['pending'] ?? 0) == 0 && ($metrics['document_requests']['ready'] ?? 0) == 0)
                                <span class="text-xs text-gray-400 italic">No active requests</span>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Change Requests (Conditional) -->
                    @if (config('employee_portal.features.personal_data_update'))
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-gray-500 text-sm font-medium">Data Changes</h3>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ ($metrics['change_requests']['pending'] ?? 0) + ($metrics['change_requests']['under_review'] ?? 0) }}</p>
                            </div>
                            <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                                <i class="fas fa-user-edit text-xl"></i>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 mb-4">
                            Active change requests
                        </div>
                         <div class="flex gap-2">
                             @if(($metrics['change_requests']['pending'] ?? 0) > 0)
                                <span class="bg-amber-50 text-amber-800 text-xs px-2.5 py-1 rounded-md border border-amber-100">
                                    {{ $metrics['change_requests']['pending'] }} pending
                                </span>
                            @endif
                            @if(($metrics['change_requests']['approved'] ?? 0) > 0)
                                <span class="bg-green-50 text-green-800 text-xs px-2.5 py-1 rounded-md border border-green-100">
                                    {{ $metrics['change_requests']['approved'] }} approved
                                </span>
                            @endif
                            @if(($metrics['change_requests']['pending'] ?? 0) == 0 && ($metrics['change_requests']['approved'] ?? 0) == 0)
                                <span class="text-xs text-gray-400 italic">No active changes</span>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Performance Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-gray-500 text-sm font-medium">Performance Rating</h3>
                                <p class="text-3xl font-bold text-gray-900 mt-1">
                                    {{ $metrics['performance']['latest_rating'] ? number_format($metrics['performance']['latest_rating'], 2) : 'N/A' }}
                                </p>
                            </div>
                            <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                        </div>
                        
                        @if(($metrics['performance']['targets_this_period'] ?? 0) > 0)
                            <div class="w-full bg-gray-100 rounded-full h-2 mb-3">
                                @php
                                    $completionRate = ($metrics['performance']['completed_targets'] ?? 0) / $metrics['performance']['targets_this_period'] * 100;
                                @endphp
                                <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ $completionRate }}%"></div>
                            </div>
                            <div class="text-sm text-gray-600">
                                <span class="font-medium text-gray-900">{{ $metrics['performance']['completed_targets'] ?? 0 }}</span>
                                of {{ $metrics['performance']['targets_this_period'] }} targets completed
                            </div>
                        @else
                            <div class="text-sm text-gray-500">No active targets</div>
                        @endif
                    </div>
                </div>

                <!-- Notifications -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
                        @if(count($notifications) > 0)
                            <span class="bg-red-100 text-red-600 text-xs font-bold px-2.5 py-1 rounded-full">{{ count($notifications) }} New</span>
                        @endif
                    </div>
                    <div class="divide-y divide-gray-50">
                        @forelse($notifications as $notification)
                            <div class="p-4 hover:bg-gray-50 transition-colors flex items-start gap-4">
                                <div class="flex-shrink-0 mt-1">
                                    @switch($notification['type'])
                                        @case('success')
                                            <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-sm"><i class="fas fa-check"></i></div>
                                            @break
                                        @case('warning')
                                            <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-sm"><i class="fas fa-exclamation"></i></div>
                                            @break
                                        @case('danger')
                                            <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-sm"><i class="fas fa-times"></i></div>
                                            @break
                                        @default
                                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm"><i class="fas fa-info"></i></div>
                                    @endswitch
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-gray-900">{{ $notification['title'] }}</h4>
                                    <p class="text-sm text-gray-600 mt-0.5">{{ $notification['message'] }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $notification['date']->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-bell-slash text-gray-400"></i>
                                </div>
                                <p>No notifications at the moment</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Column (Announcements & Extras) -->
            <div class="space-y-8">
                <!-- Announcements -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-bullhorn text-blue-500"></i>
                            Announcements
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        @forelse($announcements as $announcement)
                            <div class="relative pl-4 border-l-2 @if($announcement->is_important) border-amber-500 @else border-blue-500 @endif">
                                <h3 class="text-sm font-bold text-gray-900 mb-1">
                                    {{ $announcement->title }}
                                    @if($announcement->is_important)
                                        <i class="fas fa-star text-amber-500 text-xs ml-1"></i>
                                    @endif
                                </h3>
                                <p class="text-sm text-gray-600 leading-relaxed mb-2">{{ $announcement->message }}</p>
                                <span class="text-xs text-gray-400">
                                    {{ $announcement->starts_at ? $announcement->starts_at->diffForHumans() : $announcement->created_at->diffForHumans() }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-4">
                                <p class="text-sm">No new announcements</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Additional Info / Widgets can go here -->
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
                    <h3 class="font-bold text-lg mb-2">Need Help?</h3>
                    <p class="text-purple-100 text-sm mb-4">Contact HR if you notice any discrepancies in your records.</p>
                    <a href="mailto:{{ config('mail.from.address') }}" class="block text-center bg-white text-indigo-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-colors w-full">
                        Contact HR Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
