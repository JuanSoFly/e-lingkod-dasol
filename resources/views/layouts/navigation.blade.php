<nav x-data="{ open: false }" class="bg-white shadow-lg border-b border-gray-100 sticky top-0 z-50 backdrop-blur-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="transition-transform duration-200 hover:scale-105">
                        <x-application-logo class="block h-10 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:ms-12 xlg:flex items-center">
                    @if(auth()->user()->hasRole('Employee') && !auth()->user()->hasAnyRole(['HR Admin', 'Super Admin', 'Department Head']))
                        <x-nav-link :href="route('employee-portal.dashboard')" :active="request()->routeIs('employee-portal.*')">
                            {{ __('My Dashboard') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endif

                    @can('user.manage')
                    <!-- Employee Management Dropdown -->
                    <div class="hidden xlg:flex xlg:items-center">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border-b-2 {{ request()->routeIs('employees.*', 'employees.archive.*') ? 'border-indigo-400 text-indigo-600' : 'border-transparent text-gray-600' }} text-sm font-medium leading-5 hover:text-gray-800 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out rounded-t-md group">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        <span>Employees</span>
                                        <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('employees.index')">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        Active Employees
                                    </div>
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('employees.archive.index')" :active="request()->routeIs('employees.archive.*')">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                        </svg>
                                        Archived Employees
                                    </div>
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endcan

                    <!-- Leave Management Dropdown -->
                    @if(auth()->user()->can('leave.view') || auth()->user()->can('leave.approve') || auth()->user()->can('user.manage'))
                    <div class="hidden xlg:flex xlg:items-center">
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border-b-2 {{ request()->routeIs('leave-applications.*', 'leave-types.*') ? 'border-indigo-400 text-indigo-600' : 'border-transparent text-gray-600' }} text-sm font-medium leading-5 hover:text-gray-800 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out rounded-t-md group">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>Leave</span>
                                        <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @if(auth()->user()->hasRole('Employee'))
                                    <x-dropdown-link :href="route('leave-applications.index')">
                                        {{ __('My Applications') }}
                                    </x-dropdown-link>
                                @endif
                                <x-dropdown-link :href="route('leave-card.view')">
                                    {{ __('Leave Card') }}
                                </x-dropdown-link>
                                @can('leave.approve')
                                     <x-dropdown-link :href="route('leave-applications.index', ['status' => 'pending'])">
                                        {{ __('Leave Approvals') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('user.manage')
                                    <x-dropdown-link :href="route('leave-types.index')">
                                        {{ __('Manage Leave Types') }}
                                    </x-dropdown-link>
                                @endcan
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endif

                     <!-- Performance Management Dropdown -->
                    @if(auth()->user()->can('performance.view') || auth()->user()->can('user.manage') || auth()->user()->can('performance.evaluate'))
                    <div class="hidden xlg:flex xlg:items-center relative">
                        <!-- OPCR Notification Badge -->
                        @if(auth()->user()->can('opcr.view') || auth()->user()->hasAnyRole(['Department Head', 'Assessor', 'Final Approver']))
                        @php
                            $userCanViewOPCR = auth()->user()->can('opcr.view');
                            $pendingOPCRCount = 0;
                            if(auth()->user()->hasRole('Department Head')) {
                                $officeAssignment = auth()->user()->officeAssignments()->first();
                                if($officeAssignment) {
                                    $pendingOPCRCount += \App\Models\OPCRWorkflow::where('office_id', $officeAssignment->office_id)
                                        ->whereIn('workflow_state', ['draft', 'returned'])
                                        ->count();
                                }
                            }
                            if(auth()->user()->hasRole('Assessor')) {
                                $assignedOfficeIds = auth()->user()->officeAssignments()->pluck('office_id');
                                $pendingOPCRCount += \App\Models\OPCRWorkflow::where('workflow_state', 'evaluation')
                                    ->whereHas('office', function($query) use ($assignedOfficeIds) {
                                        $query->whereIn('id', $assignedOfficeIds);
                                    })
                                    ->count();
                            }
                            if(auth()->user()->hasRole('Final Approver')) {
                                $pendingOPCRCount += \App\Models\OPCRWorkflow::where('workflow_state', 'final_approval')->count();
                            }
                        @endphp
                        @if(!$userCanViewOPCR && $pendingOPCRCount > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full animate-pulse">
                            {{ $pendingOPCRCount }}
                        </span>
                        @endif
                        @endif

                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border-b-2 {{ request()->routeIs('performance-periods.*', 'performance-targets.*', 'opcr.*') ? 'border-indigo-400 text-indigo-600' : 'border-transparent text-gray-600' }} text-sm font-medium leading-5 hover:text-gray-800 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out rounded-t-md group">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                        <span>Performance Management</span>
                                        @if($userCanViewOPCR && $pendingOPCRCount > 0)
                                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium leading-none text-white bg-red-500 rounded-full">
                                            {{ $pendingOPCRCount }}
                                        </span>
                                        @endif
                                        <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @if(auth()->user()->hasRole('Employee'))
                                    <x-dropdown-link :href="route('performance-targets.index')">
                                        {{ __('My Performance Targets') }}
                                    </x-dropdown-link>
                                @endif
                                @can('performance.evaluate')
                                    <x-dropdown-link :href="route('performance-targets.index')">
                                        {{ __('Performance Reviews') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('user.manage')
                                    <x-dropdown-link :href="route('performance-periods.index')">
                                        {{ __('Manage Periods') }}
                                    </x-dropdown-link>
                                @endcan

                                <!-- OPCR System Separator -->
                                <div class="border-t border-gray-100"></div>
                                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    OPCR System
                                </div>

                                <!-- OPCR Navigation -->
                                @can('opcr.view')
                                    <x-dropdown-link :href="route('opcr.dashboard')">
                                        {{ __('OPCR Dashboard') }}
                                    </x-dropdown-link>
                                @endcan
                                @canany(['opcr.view', 'opcr.manage'])
                                    <x-dropdown-link :href="route('opcr.workflows.index')">
                                        {{ __('OPCR Workflows') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('opcr.export')
                                    <x-dropdown-link :href="route('opcr.archive.index')">
                                        {{ __('OPCR Archive') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('opcr.settings')
                                    <x-dropdown-link :href="route('opcr.offices.index')">
                                        {{ __('Office Management') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('opcr.manage')
                                    <x-dropdown-link :href="route('opcr.mfos.index')">
                                        {{ __('Manage MFOs') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('opcr.manage')
                                    <x-dropdown-link :href="route('admin.rating-scales.index')">
                                        {{ __('Rating Scales') }}
                                    </x-dropdown-link>
                                @endcan

                                <!-- Audit Trail Separator -->
                                <div class="border-t border-gray-100"></div>
                                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    System Administration
                                </div>

                                <!-- Audit Trail Navigation -->
                                @can('audit.view')
                                    <x-dropdown-link :href="route('admin.audit-trail.index')">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Audit Trail
                                        </div>
                                    </x-dropdown-link>
                                @endcan
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endif



                    <!-- Government Benefits (New) -->
                    @can('reports.view')
                        <x-nav-link :href="route('benefits.index')" :active="request()->routeIs('benefits.*')">
                            {{ __('Benefits') }}
                        </x-nav-link>
                    @endcan

                    <!-- Leave Policies (New) -->
                    @can('user.manage')
                        <x-nav-link :href="route('leave-policies.index')" :active="request()->routeIs('leave-policies.*')">
                            {{ __('Leave Policies') }}
                        </x-nav-link>
                    @endcan

                    <!-- Employee Self-Service Portal -->
                    @if(auth()->user()->employee)
                    <div class="hidden xlg:flex xlg:items-center">
                        <x-dropdown align="left" width="60">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border-b-2 {{ request()->routeIs('employee-portal.*') ? 'border-indigo-400 text-indigo-600' : 'border-transparent text-gray-600' }} text-sm font-medium leading-5 hover:text-gray-800 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out rounded-t-md group">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>Self-Service</span>
                                        <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('employee-portal.dashboard')">
                                    {{ __('My Portal') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('employee-portal.service-record')">
                                    {{ __('Service Record') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('employee-portal.document-requests')">
                                    {{ __('HR Document Services') }}
                                </x-dropdown-link>

                                <x-dropdown-link :href="route('employee-portal.personal-data-update')">
                                    {{ __('Update Personal Info') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('employee-portal.benefits-summary')">
                                    {{ __('Benefits Summary') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endif

                    @canany(['ipcr.view-own','ipcr.review','ipcr.approve','ipcr.validate','ipcr.finalize','ipcr.analytics'])
                        <div class="hidden xlg:flex xlg:items-center">
                            <x-dropdown align="left" width="56">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border-b-2 {{ request()->routeIs('ipcr.*') ? 'border-indigo-400 text-indigo-600' : 'border-transparent text-gray-600' }} text-sm font-medium leading-5 hover:text-gray-800 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out rounded-t-md group">
                                        <div class="flex items-center space-x-1">
                                            <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h6m2 2l-2-2m0 0l-2 2m2-2v6" />
                                            </svg>
                                            <span>IPCR</span>
                                            <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @can('ipcr.view-own')
                                        <x-dropdown-link :href="route('ipcr.employee.index')">My IPCR</x-dropdown-link>
                                    @endcan
                                    @can('ipcr.review')
                                        <x-dropdown-link :href="route('ipcr.supervisor.index')">Team IPCR Reviews</x-dropdown-link>
                                    @endcan
                                    @can('ipcr.approve')
                                        <x-dropdown-link :href="route('ipcr.head.index')">Head of Office Queue</x-dropdown-link>
                                    @endcan
                                    @can('ipcr.validate')
                                        <x-dropdown-link :href="route('ipcr.pmt.index')">PMT Validation</x-dropdown-link>
                                    @endcan
                                    @can('ipcr.finalize')
                                        <x-dropdown-link :href="route('ipcr.final.index')">Final Approval</x-dropdown-link>
                                    @endcan
                                    @can('ipcr.analytics')
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <x-dropdown-link :href="route('ipcr.analytics.individual')">Analytics: Individual</x-dropdown-link>
                                        <x-dropdown-link :href="route('ipcr.analytics.office')">Analytics: Office</x-dropdown-link>
                                        <x-dropdown-link :href="route('ipcr.analytics.compliance')">Analytics: Compliance</x-dropdown-link>
                                    @endcan
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endcanany
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden xlg:flex xlg:items-center xlg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm leading-4 font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 ease-in-out shadow-sm group">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                    {{ Auth::user()->avatar_initials }}
                                </div>
                                <span class="hidden md:block font-medium">{{ Auth::user()->full_name }}</span>
                                <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}" id="logout-form-desktop">
                            @csrf

                            <x-dropdown-link href="#"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center xlg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 ease-in-out">
                    <svg class="h-6 w-6 transition-transform duration-200" :class="{ 'rotate-90': open }" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden xlg:hidden bg-white border-t border-gray-100 shadow-sm">
        <div class="pt-4 pb-3 space-y-2 px-4">
            @if(auth()->user()->hasRole('Employee') && !auth()->user()->hasAnyRole(['HR Admin', 'Super Admin', 'Department Head']))
                <x-responsive-nav-link :href="route('employee-portal.dashboard')" :active="request()->routeIs('employee-portal.*')">
                    {{ __('My Dashboard') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @endif

            @can('user.manage')
                <x-responsive-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.*') && !request()->routeIs('employees.archive.*')">
                    {{ __('Active Employees') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('employees.archive.index')" :active="request()->routeIs('employees.archive.*')">
                    {{ __('Archived Employees') }}
                </x-responsive-nav-link>
            @endcan

             @can('leave.view-own')
                <x-responsive-nav-link :href="route('employee-portal.leave-applications.index')">
                    {{ __('My Leave Applications') }}
                </x-responsive-nav-link>
            @endcan
            @can('leave.approve')
                    <x-responsive-nav-link :href="route('leave-applications.index', ['status' => 'pending'])">
                    {{ __('Leave Approvals') }}
                </x-responsive-nav-link>
            @endcan
            @can('user.manage')
                <x-responsive-nav-link :href="route('leave-types.index')">
                    {{ __('Manage Leave Types') }}
                </x-responsive-nav-link>
            @endcan
                 @can('performance.evaluate')
                <x-responsive-nav-link :href="route('performance-targets.index')">
                    {{ __('Performance Reviews') }}
                </x-responsive-nav-link>
            @endcan
            @can('user.manage')
                <x-responsive-nav-link :href="route('performance-periods.index')">
                    {{ __('Manage Perf. Periods') }}
                </x-responsive-nav-link>
            @endcan

            @can('ipcr.view-own')
                <x-responsive-nav-link :href="route('ipcr.employee.index')" :active="request()->routeIs('ipcr.employee.*')">
                    {{ __('My IPCR') }}
                </x-responsive-nav-link>
            @endcan
            @can('ipcr.review')
                <x-responsive-nav-link :href="route('ipcr.supervisor.index')" :active="request()->routeIs('ipcr.supervisor.*')">
                    {{ __('Team IPCR Reviews') }}
                </x-responsive-nav-link>
            @endcan
            @can('ipcr.approve')
                <x-responsive-nav-link :href="route('ipcr.head.index')" :active="request()->routeIs('ipcr.head.*')">
                    {{ __('Head IPCR Queue') }}
                </x-responsive-nav-link>
            @endcan
            @can('ipcr.validate')
                <x-responsive-nav-link :href="route('ipcr.pmt.index')" :active="request()->routeIs('ipcr.pmt.*')">
                    {{ __('PMT Validation') }}
                </x-responsive-nav-link>
            @endcan
            @can('ipcr.finalize')
                <x-responsive-nav-link :href="route('ipcr.final.index')" :active="request()->routeIs('ipcr.final.*')">
                    {{ __('Final Approval') }}
                </x-responsive-nav-link>
            @endcan
            @can('ipcr.analytics')
                <div class="border-t border-gray-200 my-2"></div>
                <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">IPCR Analytics</div>
                <x-responsive-nav-link :href="route('ipcr.analytics.individual')" :active="request()->routeIs('ipcr.analytics.individual')">
                    {{ __('Analytics: Individual') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ipcr.analytics.office')" :active="request()->routeIs('ipcr.analytics.office')">
                    {{ __('Analytics: Office') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ipcr.analytics.compliance')" :active="request()->routeIs('ipcr.analytics.compliance')">
                    {{ __('Analytics: Compliance') }}
                </x-responsive-nav-link>
            @endcan

            <!-- OPCR System (Mobile) -->
            <div class="border-t border-gray-200 my-2"></div>
            <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider flex items-center justify-between">
                <span>OPCR System</span>
                @if(auth()->user()->can('opcr.view') || auth()->user()->hasAnyRole(['Department Head', 'Assessor', 'Final Approver']))
                @php
                    $pendingOPCRCountMobile = 0;
                    if(auth()->user()->hasRole('Department Head')) {
                        $officeAssignmentMobile = auth()->user()->officeAssignments()->first();
                        if($officeAssignmentMobile) {
                            $pendingOPCRCountMobile += \App\Models\OPCRWorkflow::where('office_id', $officeAssignmentMobile->office_id)
                                ->whereIn('workflow_state', ['draft', 'returned'])
                                ->count();
                        }
                    }
                    if(auth()->user()->hasRole('Assessor')) {
                        $assignedOfficeIdsMobile = auth()->user()->officeAssignments()->pluck('office_id');
                        $pendingOPCRCountMobile += \App\Models\OPCRWorkflow::where('workflow_state', 'evaluation')
                            ->whereHas('office', function($query) use ($assignedOfficeIdsMobile) {
                                $query->whereIn('id', $assignedOfficeIdsMobile);
                            })
                            ->count();
                    }
                    if(auth()->user()->hasRole('Final Approver')) {
                        $pendingOPCRCountMobile += \App\Models\OPCRWorkflow::where('workflow_state', 'final_approval')->count();
                    }
                @endphp
                @if($pendingOPCRCountMobile > 0)
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium leading-none text-white bg-red-500 rounded-full animate-pulse">
                        {{ $pendingOPCRCountMobile }}
                    </span>
                @endif
                @endif
            </div>
            @can('opcr.view')
                <x-responsive-nav-link :href="route('opcr.dashboard')">
                    {{ __('OPCR Dashboard') }}
                    @if($pendingOPCRCountMobile > 0)
                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium leading-none text-red-600 bg-red-100 rounded-full">
                            {{ $pendingOPCRCountMobile }}
                        </span>
                    @endif
                </x-responsive-nav-link>
            @endcan
            @canany(['opcr.view', 'opcr.manage'])
                <x-responsive-nav-link :href="route('opcr.workflows.index')">
                    {{ __('OPCR Workflows') }}
                </x-responsive-nav-link>
            @endcan
            @can('opcr.export')
                <x-responsive-nav-link :href="route('opcr.archive.index')">
                    {{ __('OPCR Archive') }}
                </x-responsive-nav-link>
            @endcan
            @can('audit.view')
                <x-responsive-nav-link :href="route('admin.audit-trail.index')">
                    {{ __('Audit Trail') }}
                </x-responsive-nav-link>
            @endcan

            <!-- Employee Self-Service Portal (Mobile) -->
            @if(auth()->user()->employee)
                <x-responsive-nav-link :href="route('employee-portal.dashboard')" :active="request()->routeIs('employee-portal.dashboard')">
                    {{ __('My Portal') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('employee-portal.service-record')" :active="request()->routeIs('employee-portal.service-record')">
                    {{ __('Service Record') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('employee-portal.document-requests')" :active="request()->routeIs('employee-portal.document-requests')">
                    {{ __('HR Document Services') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('employee-portal.personal-data-update')" :active="request()->routeIs('employee-portal.personal-data-update')">
                    {{ __('Update Personal Info') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('employee-portal.benefits-summary')" :active="request()->routeIs('employee-portal.benefits-summary')">
                    {{ __('Benefits Summary') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-4 border-t border-gray-200 bg-gray-50">
            <div class="px-4 mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        {{ Auth::user()->avatar_initials }}
                    </div>
                    <div>
                        <div class="font-medium text-base text-gray-900">{{ Auth::user()->full_name }}</div>
                        <div class="font-medium text-sm text-gray-600">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="space-y-2 px-4">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" id="logout-form-mobile">
                    @csrf

                    <x-responsive-nav-link href="#"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
