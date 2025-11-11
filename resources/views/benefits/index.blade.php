<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Government Benefits Management') }}
            </h2>
            <div class="flex flex-wrap gap-3">
                <x-primary-button onclick="window.location.href='{{ route('benefits.create') }}'">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Enrollment
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total Enrollments</h3>
                    <p class="text-3xl font-bold mt-2 text-blue-600">{{ $stats['total_enrollments'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Active Enrollments</h3>
                    <p class="text-3xl font-bold mt-2 text-green-600">{{ $stats['active_enrollments'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Pending Verification</h3>
                    <p class="text-3xl font-bold mt-2 text-yellow-600">{{ $stats['pending_verification'] ?? 0 }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Active Loans</h3>
                    <p class="text-3xl font-bold mt-2 text-purple-600">{{ $stats['active_loans'] ?? 0 }}</p>
                </div>
            </div>

        <!-- Government Benefits Overview -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Philippine Government Benefits Overview</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-blue-700">GSIS</h4>
                            <p class="text-sm text-gray-600 mt-1">Government Service Insurance System</p>
                            <p class="text-2xl font-bold mt-2">{{ $benefitTypeCounts['GSIS'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-green-700">PhilHealth</h4>
                            <p class="text-sm text-gray-600 mt-1">Universal Health Coverage</p>
                            <p class="text-2xl font-bold mt-2">{{ $benefitTypeCounts['PhilHealth'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-orange-700">Pag-IBIG</h4>
                            <p class="text-sm text-gray-600 mt-1">Home Development Mutual Fund</p>
                            <p class="text-2xl font-bold mt-2">{{ $benefitTypeCounts['Pag-IBIG'] ?? 0 }}</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <h4 class="font-medium text-red-700">SSS</h4>
                            <p class="text-sm text-gray-600 mt-1">Social Security System</p>
                            <p class="text-2xl font-bold mt-2">{{ $benefitTypeCounts['SSS'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Filters -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <form method="GET" action="{{ route('benefits.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-48">
                            <label for="benefit_type" class="block text-sm font-medium text-gray-700">Benefit Type</label>
                            <select name="benefit_type" id="benefit_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Types</option>
                                @foreach($benefitTypes as $type)
                                    <option value="{{ $type }}" {{ request('benefit_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-32">
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Statuses</option>
                                @foreach($statusOptions as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-48">
                            <label for="employee" class="block text-sm font-medium text-gray-700">Employee Search</label>
                            <input type="text" name="employee" id="employee" value="{{ request('employee') }}" 
                                   placeholder="Search by name..." 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <x-secondary-button type="submit">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                </svg>
                                Filter
                            </x-secondary-button>
                        </div>
                    </form>
                </div>
            </div>

        <!-- Benefits Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Benefit Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrollment Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($benefits as $benefit)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <a href="{{ route('benefits.show', $benefit) }}" class="text-blue-600 hover:text-blue-900">
                                                {{ $benefit->employee->first_name }} {{ $benefit->employee->last_name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($benefit->benefit_type == 'GSIS') bg-blue-100 text-blue-800
                                                @elseif($benefit->benefit_type == 'PhilHealth') bg-green-100 text-green-800
                                                @elseif($benefit->benefit_type == 'Pag-IBIG') bg-orange-100 text-orange-800
                                                @elseif($benefit->benefit_type == 'SSS') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ $benefit->benefit_type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $benefit->member_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($benefit->enrollment_status == 'active') bg-green-100 text-green-800
                                                @elseif($benefit->enrollment_status == 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($benefit->enrollment_status == 'inactive') bg-gray-100 text-gray-800
                                                @elseif($benefit->enrollment_status == 'suspended') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($benefit->enrollment_status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $benefit->enrollment_date?->format('M d, Y') ?? 'Not set' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="{{ route('benefits.show', $benefit) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View
                                                </a>
                                                <a href="{{ route('benefits.edit', $benefit) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('benefits.destroy', $benefit) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150" 
                                                            onclick="return confirm('Are you sure you want to delete this benefit enrollment?')">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No government benefit enrollments found. <a href="{{ route('benefits.create') }}" class="text-blue-600 hover:text-blue-900">Create the first enrollment</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if(method_exists($benefits, 'links'))
                        <div class="mt-6">
                            {{ $benefits->links() }}
                        </div>
                    @endif
                </div>
            </div>
    </div>
</x-app-layout>
