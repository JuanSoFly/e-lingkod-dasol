<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Leave Policy Details') }}
            </h2>
            <div class="flex flex-col gap-2 w-full sm:w-auto sm:flex-row sm:items-center">
                <a href="{{ route('leave-policies.edit', $leavePolicy) }}" class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 w-full sm:w-auto">
                    Edit Policy
                </a>
                <a href="{{ route('leave-policies.index') }}" class="inline-flex items-center justify-center gap-2 rounded-md bg-gray-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 w-full sm:w-auto">
                    Back to Policies
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Policy Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $leavePolicy->name }}</h3>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                @if($leavePolicy->is_active) bg-green-100 text-green-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ $leavePolicy->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if($leavePolicy->is_government_policy)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                    Government Policy
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    @if($leavePolicy->description)
                        <p class="text-gray-700 mb-4">{{ $leavePolicy->description }}</p>
                    @endif
                </div>
            </div>

            <!-- Policy Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Basic Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h4>
                        
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Leave Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $leavePolicy->leaveType->name ?? 'N/A' }}</dd>
                            </div>
                            
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Maximum Days Per Year</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $leavePolicy->max_days_per_year }} days</dd>
                            </div>
                            
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Accrual Method</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($leavePolicy->accrual_method) }}</dd>
                            </div>
                            
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Effective Start Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $leavePolicy->effective_start_date ? \Carbon\Carbon::parse($leavePolicy->effective_start_date)->format('F j, Y') : 'Not set' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Employment Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Applicable Employment Status</h4>
                        
                        <div class="space-y-2">
                            @if(is_array($leavePolicy->employment_statuses) && count($leavePolicy->employment_statuses) > 0)
                                @foreach($leavePolicy->employment_statuses as $status)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        {{ ucfirst($status) }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-gray-500 text-sm">All employment statuses</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Policy Options -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Policy Configuration</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 border rounded-lg {{ $leavePolicy->is_active ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                            <div class="text-2xl mb-2">
                                @if($leavePolicy->is_active)
                                    <span class="text-green-600">✓</span>
                                @else
                                    <span class="text-red-600">✗</span>
                                @endif
                            </div>
                            <h5 class="font-medium text-gray-900">Active Policy</h5>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $leavePolicy->is_active ? 'Available for leave applications' : 'Not available for new applications' }}
                            </p>
                        </div>
                        
                        <div class="text-center p-4 border rounded-lg {{ $leavePolicy->is_government_policy ? 'bg-purple-50 border-purple-200' : 'bg-gray-50 border-gray-200' }}">
                            <div class="text-2xl mb-2">
                                @if($leavePolicy->is_government_policy)
                                    <span class="text-purple-600">🏛️</span>
                                @else
                                    <span class="text-gray-600">🏢</span>
                                @endif
                            </div>
                            <h5 class="font-medium text-gray-900">Government Policy</h5>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $leavePolicy->is_government_policy ? 'Mandated by government regulations' : 'Custom organizational policy' }}
                            </p>
                        </div>
                        
                        <div class="text-center p-4 border rounded-lg {{ $leavePolicy->requires_approval ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200' }}">
                            <div class="text-2xl mb-2">
                                @if($leavePolicy->requires_approval)
                                    <span class="text-yellow-600">👥</span>
                                @else
                                    <span class="text-green-600">🤖</span>
                                @endif
                            </div>
                            <h5 class="font-medium text-gray-900">Approval Required</h5>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $leavePolicy->requires_approval ? 'Supervisor approval needed' : 'Automatic approval' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Policy Impact Information -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h4 class="text-lg font-semibold text-blue-900 mb-2">Policy Impact</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                    <div>
                        <h5 class="font-medium mb-1">Leave Calculations</h5>
                        <p>This policy affects how leave balances are calculated for applicable employees based on their employment status and hire date.</p>
                    </div>
                    <div>
                        <h5 class="font-medium mb-1">Application Workflow</h5>
                        <p>{{ $leavePolicy->requires_approval ? 'Leave applications will require supervisor approval.' : 'Leave applications will be automatically approved if balance is sufficient.' }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Policy Actions</h4>
                            <p class="text-sm text-gray-600">Manage this leave policy</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('leave-policies.edit', $leavePolicy) }}" 
                               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Edit Policy
                            </a>
                            <form method="POST" action="{{ route('leave-policies.destroy', $leavePolicy) }}" class="inline" data-confirm="Are you sure you want to delete this leave policy? This action cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                    Delete Policy
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
