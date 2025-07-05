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
                <div class="hidden space-x-1 sm:ms-12 sm:flex items-center">
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
                        <x-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                            {{ __('Employees') }}
                        </x-nav-link>
                    @endcan

                    <!-- Leave Management Dropdown -->
                    @if(auth()->user()->can('leave.view') || auth()->user()->can('leave.approve') || auth()->user()->can('user.manage'))
                    <div class="hidden sm:flex sm:items-center">
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
                                @can('leave.view')
                                    <x-dropdown-link :href="route('leave-applications.index')">
                                        {{ __('My Applications') }}
                                    </x-dropdown-link>
                                @endcan
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
                    <div class="hidden sm:flex sm:items-center">
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border-b-2 {{ request()->routeIs('performance-periods.*', 'performance-targets.*') ? 'border-indigo-400 text-indigo-600' : 'border-transparent text-gray-600' }} text-sm font-medium leading-5 hover:text-gray-800 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out rounded-t-md group">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                        <span>Performance (IPCR)</span>
                                        <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @can('performance.view')
                                    <x-dropdown-link :href="route('performance-targets.index')">
                                        {{ __('My IPCR') }}
                                    </x-dropdown-link>
                                @endcan
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
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endif

                    <!-- Document Approval Dropdown -->
                    @if(auth()->user()->hasAnyRole(['Super Admin', 'HR Admin', 'Employee']))
                    <div class="hidden sm:flex sm:items-center">
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border-b-2 {{ request()->routeIs('document-approvals.*') ? 'border-indigo-400 text-indigo-600' : 'border-transparent text-gray-600' }} text-sm font-medium leading-5 hover:text-gray-800 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out rounded-t-md group">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span>Document Approvals</span>
                                        <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @can('user.manage')
                                    <x-dropdown-link :href="route('document-approvals.index')">
                                        {{ __('All Requests') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('document-approval.create')
                                    <x-dropdown-link :href="route('document-approvals.create')">
                                        {{ __('New Request') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('document-approval.create')
                                    <x-dropdown-link :href="route('document-approvals.my-requests')">
                                        {{ __('My Requests') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('document-approval.approve')
                                    <x-dropdown-link :href="route('document-approvals.pending-approvals')">
                                        {{ __('Pending Approvals') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('user.manage')
                                    <x-dropdown-link :href="route('document-approvals.dashboard')">
                                        {{ __('Dashboard') }}
                                    </x-dropdown-link>
                                @endcan
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endif

                    <!-- Reports Dropdown -->
                    @if(auth()->user()->can('reports.view') || auth()->user()->can('reports.generate'))
                    <div class="hidden sm:flex sm:items-center">
                        <x-dropdown align="left" width="64">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border-b-2 {{ request()->routeIs('reports.*', 'csc-reports.*') ? 'border-indigo-400 text-indigo-600' : 'border-transparent text-gray-600' }} text-sm font-medium leading-5 hover:text-gray-800 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:border-gray-300 transition-all duration-200 ease-in-out rounded-t-md group">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span>Reports</span>
                                        <svg class="fill-current h-4 w-4 transition-transform duration-200 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @can('reports.view')
                                    <x-dropdown-link :href="route('reports.index')">
                                        {{ __('Standard Reports') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('reports.generate')
                                    <x-dropdown-link :href="route('csc-reports.index')">
                                        {{ __('CSC Reports') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('csc-reports.create')">
                                        {{ __('Generate CSC Report') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('user.manage')
                                    <x-dropdown-link :href="route('documents.search')">
                                        {{ __('Document Search') }}
                                    </x-dropdown-link>
                                @endcan
                                @can('reports.view')
                                    <x-dropdown-link :href="route('documents.analytics')">
                                        {{ __('Document Analytics') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('hr-analytics.dashboard')">
                                        {{ __('HR Analytics') }}
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
                    <div class="hidden sm:flex sm:items-center">
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
                                <x-dropdown-link :href="route('employee-portal.service-record')">
                                    {{ __('Service Record') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('employee-portal.document-requests')">
                                    {{ __('Document Requests') }}
                                </x-dropdown-link>
                                
                                @can('document-approval.view')
                                    <div class="border-t border-gray-100"></div>
                                    <x-dropdown-link :href="route('document-approvals.my-requests')">
                                        {{ __('My Document Approvals') }}
                                    </x-dropdown-link>
                                    @can('document-approval.create')
                                        <x-dropdown-link :href="route('document-approvals.create')">
                                            {{ __('New Approval Request') }}
                                        </x-dropdown-link>
                                    @endcan
                                    <div class="border-t border-gray-100"></div>
                                @endcan
                                
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
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm leading-4 font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 ease-in-out shadow-sm group">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                                </div>
                                <span class="hidden md:block font-medium">{{ Auth::user()->name }}</span>
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
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white border-t border-gray-100 shadow-sm">
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
                <x-responsive-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                    {{ __('Employees') }}
                </x-responsive-nav-link>
            @endcan

             @can('leave.view')
                <x-responsive-nav-link :href="route('leave-applications.index')">
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
             @can('performance.view')
                <x-responsive-nav-link :href="route('performance-targets.index')">
                    {{ __('My IPCR') }}
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
            @can('reports.view')
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
            @endcan
            
            <!-- Document Approval Mobile Navigation -->
            @can('document-approval.create')
                <x-responsive-nav-link :href="route('document-approvals.my-requests')" :active="request()->routeIs('document-approvals.my-requests')">
                    {{ __('My Document Requests') }}
                </x-responsive-nav-link>
            @endcan
            @can('user.manage')
                <x-responsive-nav-link :href="route('document-approvals.dashboard')" :active="request()->routeIs('document-approvals.dashboard')">
                    {{ __('Document Approval Dashboard') }}
                </x-responsive-nav-link>
            @endcan
            @can('document-approval.create')
                <x-responsive-nav-link :href="route('document-approvals.create')" :active="request()->routeIs('document-approvals.create')">
                    {{ __('New Document Request') }}
                </x-responsive-nav-link>
            @endcan
            @can('document-approval.approve')
                <x-responsive-nav-link :href="route('document-approvals.pending-approvals')" :active="request()->routeIs('document-approvals.pending-approvals')">
                    {{ __('Pending Approvals') }}
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
                    {{ __('Document Requests') }}
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
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="font-medium text-base text-gray-900">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-600">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="space-y-2 px-4">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>