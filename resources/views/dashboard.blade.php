<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-4 sm:space-y-6 no-overflow-x">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
            <!-- Stat Card: Total Employees -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-4 sm:p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Employees</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalEmployees) }}</p>
                    </div>
                </div>
            </div>
            <!-- Stat Card: On Leave Today -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-4 sm:p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4M8 7H3a1 1 0 00-1 1v2a1 1 0 001 1h5M8 7h8m8 0v12a1 1 0 01-1 1H5a1 1 0 01-1-1V8a1 1 0 011-1h3"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">On Leave Today</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($leaveToday) }}</p>
                    </div>
                </div>
            </div>
            <!-- Stat Card: Pending Requests -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-4 sm:p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Pending Requests</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($pendingLeaveApps) }}</p>
                    </div>
                </div>
            </div>

            <!-- OPCR Stat Cards - Only show if user has OPCR permissions -->
            @if($canViewOPCR || $isDepartmentHead || $isAssessor || $isFinalApprover)
                <!-- OPCR Total Workflows Card -->
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 overflow-hidden shadow-sm rounded-lg p-6 border border-purple-200 hover:shadow-md transition-all duration-200 hover:from-purple-100 hover:to-indigo-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex items-center space-x-1">
                                    <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">OPCR Workflows</h3>
                                    <!-- Help tooltip for OPCR Workflows -->
                                    <div class="group relative">
                                        <button class="inline-flex items-center justify-center w-4 h-4 text-gray-400 hover:text-purple-600 transition-colors duration-200" title="OPCR Workflows help">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                        <div class="absolute bottom-full left-0 mb-2 w-48 p-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                            <div class="absolute bottom-0 left-2 transform translate-y-1/2 rotate-45 w-1.5 h-1.5 bg-gray-900"></div>
                                            <div class="relative">
                                                <p class="leading-relaxed">Total active OPCR workflows for the current performance period</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-2xl font-bold text-purple-900 mt-1">{{ number_format($opcrData['metrics']['total_workflows'] ?? 0) }}</p>
                                <p class="text-xs text-gray-500 mt-1">Total this period</p>
                            </div>
                        </div>
                        <a href="{{ route('opcr.dashboard') }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                            View →
                        </a>
                    </div>
                </div>

                <!-- OPCR Pending Tasks Card -->
                @if($isDepartmentHead || $isAssessor || $isFinalApprover)
                <div class="bg-gradient-to-br from-orange-50 to-red-50 overflow-hidden shadow-sm rounded-lg p-6 border border-orange-200 hover:shadow-md transition-all duration-200 hover:from-orange-100 hover:to-red-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Pending OPCR</h3>
                                <p class="text-2xl font-bold text-orange-900 mt-1">
                                    {{
                                        ($isDepartmentHead ? ($opcrData['pending_actions']['draft_workflows'] ?? 0) + ($opcrData['pending_actions']['returned_workflows'] ?? 0) : 0) +
                                        ($isAssessor ? ($opcrData['pending_assessments']['evaluation_workflows'] ?? 0) : 0) +
                                        ($isFinalApprover ? ($opcrData['pending_approvals']['final_approval_workflows'] ?? 0) : 0)
                                    }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    @if($isDepartmentHead)
                                        Draft & Returned
                                    @elseif($isAssessor)
                                        For Evaluation
                                    @elseif($isFinalApprover)
                                        For Approval
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('opcr.workflows.index') }}" class="text-orange-600 hover:text-orange-800 text-sm font-medium">
                            Review →
                        </a>
                    </div>
                </div>
                @endif

                <!-- OPCR Completed Card -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 overflow-hidden shadow-sm rounded-lg p-6 border border-green-200 hover:shadow-md transition-all duration-200 hover:from-green-100 hover:to-emerald-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">OPCR Completed</h3>
                                <p class="text-2xl font-bold text-green-900 mt-1">{{ number_format($opcrData['metrics']['completed_workflows'] ?? 0) }}</p>
                                <p class="text-xs text-gray-500 mt-1">Approved this period</p>
                            </div>
                        </div>
                        <a href="{{ route('opcr.archive.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">
                            Archive →
                        </a>
                    </div>
                </div>

                <!-- OPCR Average Rating Card -->
                @if($isAssessor || $isFinalApprover || $canManageOPCR)
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 overflow-hidden shadow-sm rounded-lg p-6 border border-blue-200 hover:shadow-md transition-all duration-200 hover:from-blue-100 hover:to-cyan-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">OPCR Rating</h3>
                                <p class="text-2xl font-bold text-blue-900 mt-1">{{ number_format($opcrData['metrics']['average_rating'] ?? 0, 2) }}</p>
                                <p class="text-xs text-gray-500 mt-1">Average score</p>
                            </div>
                        </div>
                        <a href="{{ route('opcr.analytics.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Analytics →
                        </a>
                    </div>
                </div>
                @endif
            @endif
        </div>

        <!-- OPCR Quick Actions Section - Only show if user has OPCR permissions -->
        @if($canViewOPCR || $isDepartmentHead || $isAssessor || $isFinalApprover)
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-purple-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <h3 class="text-lg font-semibold text-gray-900">OPCR Quick Actions</h3>
                    <!-- OPCR Help Button -->
                    <div class="group relative">
                        <button class="inline-flex items-center justify-center w-5 h-5 text-gray-400 hover:text-purple-600 transition-colors duration-200" title="What is OPCR?">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </button>
                        <!-- Tooltip -->
                        <div class="absolute bottom-full left-0 mb-2 w-64 p-3 bg-gray-900 text-white text-sm rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="absolute bottom-0 left-4 transform translate-y-1/2 rotate-45 w-2 h-2 bg-gray-900"></div>
                            <div class="relative">
                                <h4 class="font-semibold mb-1">Office Performance Commitment & Review</h4>
                                <p class="text-xs leading-relaxed">OPCR manages office-level performance targets, workflows, and evaluations for Philippine government compliance.</p>
                                <div class="mt-2 pt-2 border-t border-gray-700">
                                    <p class="text-xs"><strong>Your role:</strong>
                                        @if($isDepartmentHead)
                                            Department Head - Create and manage OPCR workflows
                                        @elseif($isAssessor)
                                            Assessor - Evaluate and rate OPCR submissions
                                        @elseif($isFinalApprover)
                                            Final Approver - Approve completed OPCR evaluations
                                        @else
                                            Viewer - Monitor OPCR analytics and reports
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        Performance Management
                    </span>
                    @if(($isDepartmentHead ? ($opcrData['pending_actions']['draft_workflows'] ?? 0) + ($opcrData['pending_actions']['returned_workflows'] ?? 0) : 0) + ($isAssessor ? ($opcrData['pending_assessments']['evaluation_workflows'] ?? 0) : 0) + ($isFinalApprover ? ($opcrData['pending_approvals']['final_approval_workflows'] ?? 0) : 0) > 0)
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span>
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Create New OPCR -->
                @if($canManageOPCR || $isDepartmentHead)
                <a href="{{ route('opcr.workflows.create') }}" class="group block p-3 sm:p-4 bg-white rounded-lg border border-purple-200 hover:border-purple-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-purple-100 group-hover:bg-purple-200 rounded-lg flex items-center justify-center transition-colors duration-200">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 group-hover:text-purple-900">Create New OPCR</h4>
                            <p class="text-xs text-gray-500 mt-1">Start new workflow</p>
                        </div>
                    </div>
                </a>
                @endif

                <!-- My Pending Tasks -->
                @if($isDepartmentHead || $isAssessor || $isFinalApprover)
                <a href="{{ route('opcr.workflows.index', ['workflow_state' => $isDepartmentHead ? 'draft' : ($isAssessor ? 'evaluation' : 'final_approval')]) }}" class="group relative block p-3 sm:p-4 bg-white rounded-lg border border-orange-200 hover:border-orange-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-orange-100 group-hover:bg-orange-200 rounded-lg flex items-center justify-center transition-colors duration-200">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 group-hover:text-orange-900">My Pending Tasks</h4>
                            <p class="text-xs text-gray-500 mt-1">
                                @if($isDepartmentHead)
                                    {{ ($opcrData['pending_actions']['draft_workflows'] ?? 0) + ($opcrData['pending_actions']['returned_workflows'] ?? 0) }} pending
                                @elseif($isAssessor)
                                    {{ $opcrData['pending_assessments']['evaluation_workflows'] ?? 0 }} for review
                                @elseif($isFinalApprover)
                                    {{ $opcrData['pending_approvals']['final_approval_workflows'] ?? 0 }} for approval
                                @endif
                            </p>
                        </div>
                    </div>
                    @if(($isDepartmentHead ? ($opcrData['pending_actions']['draft_workflows'] ?? 0) + ($opcrData['pending_actions']['returned_workflows'] ?? 0) : 0) + ($isAssessor ? ($opcrData['pending_assessments']['evaluation_workflows'] ?? 0) : 0) + ($isFinalApprover ? ($opcrData['pending_approvals']['final_approval_workflows'] ?? 0) : 0) > 0)
                        <div class="absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-orange-500 rounded-full">
                            {{ (($isDepartmentHead ? ($opcrData['pending_actions']['draft_workflows'] ?? 0) + ($opcrData['pending_actions']['returned_workflows'] ?? 0) : 0) + ($isAssessor ? ($opcrData['pending_assessments']['evaluation_workflows'] ?? 0) : 0) + ($isFinalApprover ? ($opcrData['pending_approvals']['final_approval_workflows'] ?? 0) : 0)) }}
                        </div>
                    @endif
                </a>
                @endif

                <!-- OPCR Analytics -->
                @if($canViewOPCR)
                <a href="{{ route('opcr.analytics.index') }}" class="group block p-3 sm:p-4 bg-white rounded-lg border border-blue-200 hover:border-blue-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-100 group-hover:bg-blue-200 rounded-lg flex items-center justify-center transition-colors duration-200">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 group-hover:text-blue-900">OPCR Analytics</h4>
                            <p class="text-xs text-gray-500 mt-1">View insights & reports</p>
                        </div>
                    </div>
                </a>
                @endif

                <!-- OPCR Dashboard -->
                @if($canViewOPCR)
                <a href="{{ route('opcr.dashboard') }}" class="group block p-3 sm:p-4 bg-white rounded-lg border border-green-200 hover:border-green-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-100 group-hover:bg-green-200 rounded-lg flex items-center justify-center transition-colors duration-200">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 group-hover:text-green-900">OPCR Dashboard</h4>
                            <p class="text-xs text-gray-500 mt-1">Full OPCR overview</p>
                        </div>
                    </div>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <!-- Chart Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-4 sm:p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-base sm:text-lg text-gray-900">Employees by Department</h3>
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                </div>
                <div class="h-60 sm:h-80 flex items-center justify-center">
                    <canvas id="employeesByDeptChart" class="max-w-full max-h-full"></canvas>
                </div>
            </div>
            
            <!-- Quick Overview Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 p-4 sm:p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-base sm:text-lg text-gray-900">Quick Overview</h3>
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Staff</span>
                            <span class="font-semibold text-gray-900">{{ number_format($totalEmployees) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Present Today</span>
                            <span class="font-semibold text-gray-900">{{ number_format($totalEmployees - $leaveToday) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">On Leave</span>
                            <span class="font-semibold text-gray-900">{{ number_format($leaveToday) }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="font-medium text-gray-900 mb-3">Upcoming Birthdays</h4>
                            <div class="max-h-32 sm:max-h-40 overflow-y-auto space-y-2 sm:space-y-3">
                                @forelse($upcomingBirthdays as $employee)
                                    <div class="flex items-center space-x-2 sm:space-x-3 p-2 bg-gray-50 rounded-lg">
                                        <div class="w-8 h-8 bg-gradient-to-br from-pink-400 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $employee->birth_date?->format('F j') ?? 'Date unknown' }}</div>
                                        </div>
                                        <div class="text-xs text-pink-600 font-medium">
                                            🎂
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-gray-400 py-6">
                                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4M8 7H3a1 1 0 00-1 1v2a1 1 0 001 1h5M8 7h8m0 0V6a2 2 0 012-2h1a2 2 0 012 2v1"></path>
                                        </svg>
                                        <div class="text-sm">No upcoming birthdays</div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    // Employees by Department Chart
    const deptCtx = document.getElementById('employeesByDeptChart').getContext('2d');
    const employeesByDeptData = @json($employeesByDept);
    
    // Check if we have data before creating the chart
    if (Object.keys(employeesByDeptData).length === 0) {
        // Show message if no data
        document.getElementById('employeesByDeptChart').parentElement.innerHTML = 
            '<div class="text-center py-8">' +
            '<p class="text-gray-500">No department data available</p>' +
            '</div>';
        return;
    }

    new Chart(deptCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(employeesByDeptData),
            datasets: [{
                label: 'Employees',
                data: Object.values(employeesByDeptData),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',  // Blue
                    'rgba(16, 185, 129, 0.8)',  // Green
                    'rgba(245, 158, 11, 0.8)',  // Yellow
                    'rgba(239, 68, 68, 0.8)',   // Red
                    'rgba(139, 92, 246, 0.8)',  // Purple
                    'rgba(6, 182, 212, 0.8)'    // Cyan
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} employees (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

</x-app-layout>