<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Leave Policies Management') }}
            </h2>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('leave-policies.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Create New Policy
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total Policies</h3>
                    <p class="text-3xl font-bold mt-2 text-blue-600">{{ $stats['total_policies'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Active Policies</h3>
                    <p class="text-3xl font-bold mt-2 text-green-600">{{ $stats['active_policies'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Government Policies</h3>
                    <p class="text-3xl font-bold mt-2 text-purple-600">{{ $stats['government_policies'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Custom Policies</h3>
                    <p class="text-3xl font-bold mt-2 text-orange-600">{{ $stats['custom_policies'] ?? 0 }}</p>
                </div>
            </div>

            <!-- Policy Types Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Leave Policy Management System</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 border rounded-lg bg-blue-50">
                            <h4 class="font-medium text-blue-700 mb-2">Government-Compliant Policies</h4>
                            <p class="text-sm text-gray-600">Configure leave policies that comply with Philippine government regulations for different employment statuses (permanent, temporary, contractual, casual).</p>
                            <ul class="mt-2 text-xs text-blue-600">
                                <li>• Pro-rated calculations for new employees</li>
                                <li>• Carry-over rules and expiration dates</li>
                                <li>• CSC reporting compliance</li>
                            </ul>
                        </div>
                        <div class="p-4 border rounded-lg bg-green-50">
                            <h4 class="font-medium text-green-700 mb-2">Automated Leave Management</h4>
                            <p class="text-sm text-gray-600">Automate leave calculations, approvals, and balance tracking based on configurable rules for each employee type and position.</p>
                            <ul class="mt-2 text-xs text-green-600">
                                <li>• Monthly accrual calculations</li>
                                <li>• Approval hierarchy enforcement</li>
                                <li>• Medical certificate requirements</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('leave-policies.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-48">
                            <label for="leave_type" class="block text-sm font-medium text-gray-700">Leave Type</label>
                            <select name="leave_type" id="leave_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Leave Types</option>
                                @foreach($leaveTypes as $leaveType)
                                    <option value="{{ $leaveType->id }}" {{ request('leave_type') == $leaveType->id ? 'selected' : '' }}>
                                        {{ $leaveType->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-32">
                            <label for="employment_status" class="block text-sm font-medium text-gray-700">Employment Status</label>
                            <select name="employment_status" id="employment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Statuses</option>
                                @foreach($employmentStatuses as $status)
                                    <option value="{{ $status }}" {{ request('employment_status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-32">
                            <label for="status" class="block text-sm font-medium text-gray-700">Policy Status</label>
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                @foreach($statusOptions as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Policies Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Policy Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Days/Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($policies as $policy)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <a href="{{ route('leave-policies.show', $policy) }}" class="text-blue-600 hover:text-blue-900">
                                                {{ $policy->name }}
                                            </a>
                                            @if($policy->is_government_policy)
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    Government
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $policy->leaveType->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $policy->max_days_per_year }} days
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex flex-wrap gap-1">
                                                @if(is_array($policy->employment_statuses))
                                                    @foreach($policy->employment_statuses as $status)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                            {{ ucfirst($status) }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-gray-500">All</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($policy->is_active) bg-green-100 text-green-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ $policy->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <a href="{{ route('leave-policies.show', $policy) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                            <a href="{{ route('leave-policies.edit', $policy) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            <form method="POST" action="{{ route('leave-policies.destroy', $policy) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" 
                                                        onclick="return confirm('Are you sure you want to delete this leave policy?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No leave policies found. <a href="{{ route('leave-policies.create') }}" class="text-blue-600 hover:text-blue-900">Create the first policy</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if(method_exists($policies, 'links'))
                        <div class="mt-6">
                            {{ $policies->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
