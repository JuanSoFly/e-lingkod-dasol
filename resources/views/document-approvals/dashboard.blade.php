@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Document Approval Dashboard</h2>
                        <p class="mt-1 text-sm text-gray-600">Overview of document approval requests and analytics</p>
                    </div>
                    <a href="{{ route('document-approvals.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        New Request
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Requests -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Requests</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['total_requests'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['pending_requests'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approved Requests -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['approved_requests'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Pending Approvals -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">My Actions Needed</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['my_pending_approvals'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Recent Requests -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Requests</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @for($i = 1; $i <= 3; $i++)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-600 font-medium text-sm">DA</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">Sample Request {{ $i }}</p>
                                        <p class="text-sm text-gray-500">Employee Document</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            </div>
                        @endfor
                        <div class="text-center">
                            <a href="{{ route('document-approvals.index') }}" 
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                View all requests →
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4">
                        <a href="{{ route('document-approvals.create') }}" 
                           class="flex items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors duration-200">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">New Request</p>
                                <p class="text-sm text-gray-500">Create a new document approval request</p>
                            </div>
                        </a>

                        <a href="{{ route('document-approvals.my-requests') }}" 
                           class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors duration-200">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">My Requests</p>
                                <p class="text-sm text-gray-500">View your submitted requests</p>
                            </div>
                        </a>

                        @can('document-approval.approve')
                            <a href="{{ route('document-approvals.pending-approvals') }}" 
                               class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-200">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">Pending Approvals</p>
                                    <p class="text-sm text-gray-500">Review requests awaiting approval</p>
                                </div>
                            </a>
                        @endcan

                        @can('document-approval.view')
                            <a href="{{ route('document-approvals.index') }}" 
                               class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">All Requests</p>
                                    <p class="text-sm text-gray-500">Browse all approval requests</p>
                                </div>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Overview Chart -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Request Status Overview</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ $stats['total_requests'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500">Total</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending_requests'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500">Pending</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $stats['approved_requests'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500">Approved</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $stats['rejected_requests'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500">Rejected</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ $stats['overdue_requests'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500">Overdue</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection