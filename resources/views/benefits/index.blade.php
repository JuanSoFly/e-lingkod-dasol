<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Government Benefits Management') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Manage employee memberships, contribution profiles, and active loans.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('benefits.create') }}" class="pds-edit-link" data-title="Add New Enrollment">
                    <button type="button" class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-xl text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add New Enrollment
                    </button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-gray-500 text-sm font-medium">Total Enrollments</h3>
                    <p class="text-3xl font-extrabold mt-2 text-gray-900">{{ $stats['total_enrollments'] ?? 0 }}</p>
                </div>
                <div class="bg-blue-50 text-blue-600 p-3.5 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-gray-500 text-sm font-medium">Active Enrollments</h3>
                    <p class="text-3xl font-extrabold mt-2 text-gray-900">{{ $stats['active_enrollments'] ?? 0 }}</p>
                </div>
                <div class="bg-emerald-50 text-emerald-600 p-3.5 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-gray-500 text-sm font-medium">Pending Verification</h3>
                    <p class="text-3xl font-extrabold mt-2 text-gray-900">{{ $stats['pending_verification'] ?? 0 }}</p>
                </div>
                <div class="bg-amber-50 text-amber-600 p-3.5 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-gray-500 text-sm font-medium">Active Loans</h3>
                    <p class="text-3xl font-extrabold mt-2 text-gray-900">{{ $stats['active_loans'] ?? 0 }}</p>
                </div>
                <div class="bg-purple-50 text-purple-600 p-3.5 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Government Benefits Overview -->
        <div class="bg-white border border-gray-250/80 rounded-2xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5" />
                </svg>
                Philippine Government Benefits Overview
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <!-- GSIS -->
                <div class="p-5 border border-blue-100 bg-blue-50/20 rounded-2xl transition-all duration-200 hover:border-blue-200 shadow-sm relative overflow-hidden group">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-bold text-blue-700 text-lg">GSIS</h4>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Govt Service Insurance System</p>
                        </div>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">PH</span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Members</span>
                        <p class="text-3xl font-extrabold text-blue-900">{{ $benefitTypeCounts['GSIS'] ?? 0 }}</p>
                    </div>
                </div>
                <!-- PhilHealth -->
                <div class="p-5 border border-emerald-100 bg-emerald-50/20 rounded-2xl transition-all duration-200 hover:border-emerald-200 shadow-sm relative overflow-hidden group">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-bold text-emerald-700 text-lg">PhilHealth</h4>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Universal Health Coverage</p>
                        </div>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold">PH</span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Members</span>
                        <p class="text-3xl font-extrabold text-emerald-900">{{ $benefitTypeCounts['PhilHealth'] ?? 0 }}</p>
                    </div>
                </div>
                <!-- Pag-IBIG -->
                <div class="p-5 border border-amber-100 bg-amber-50/20 rounded-2xl transition-all duration-200 hover:border-amber-200 shadow-sm relative overflow-hidden group">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-bold text-amber-700 text-lg">Pag-IBIG</h4>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Home Devt Mutual Fund</p>
                        </div>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold">PH</span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Members</span>
                        <p class="text-3xl font-extrabold text-amber-900">{{ $benefitTypeCounts['Pag-IBIG'] ?? 0 }}</p>
                    </div>
                </div>
                <!-- SSS -->
                <div class="p-5 border border-rose-100 bg-rose-50/20 rounded-2xl transition-all duration-200 hover:border-rose-200 shadow-sm relative overflow-hidden group">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-bold text-rose-700 text-lg">SSS</h4>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Social Security System</p>
                        </div>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-700 text-xs font-bold">PH</span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Members</span>
                        <p class="text-3xl font-extrabold text-rose-900">{{ $benefitTypeCounts['SSS'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-gray-250/80 rounded-2xl shadow-sm p-6">
            <form method="GET" action="{{ route('benefits.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-1/4">
                    <label for="benefit_type" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Benefit Type</label>
                    <select name="benefit_type" id="benefit_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">All Types</option>
                        @foreach($benefitTypes as $type)
                            <option value="{{ $type }}" {{ request('benefit_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-1/4">
                    <label for="status" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" id="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">All Statuses</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:flex-1">
                    <label for="employee" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Employee Search</label>
                    <div class="relative rounded-lg shadow-sm">
                        <input type="text" name="employee" id="employee" value="{{ request('employee') }}" 
                               placeholder="Search by employee name..." 
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm pl-10">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-auto flex gap-2">
                    @if(request()->filled('benefit_type') || request()->filled('status') || request()->filled('employee'))
                        <a href="{{ route('benefits.index') }}" class="w-full md:w-auto inline-flex items-center justify-center px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors duration-150 whitespace-nowrap">
                            Clear
                        </a>
                    @endif
                    <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150 whitespace-nowrap">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                        </svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Benefits Table -->
        <div class="bg-white border border-gray-250/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Benefit Type</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Member Number</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enrollment Date</th>
                            <th class="relative px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($benefits as $benefit)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 select-none flex items-center justify-center text-xs font-bold shadow-sm">
                                            {{ $benefit->employee->avatar_initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('benefits.show', $benefit) }}" class="font-semibold text-gray-900 hover:text-indigo-600 transition-colors block truncate">
                                                {{ $benefit->employee->first_name }} {{ $benefit->employee->last_name }}
                                            </a>
                                            <span class="text-xs text-gray-500 block truncate">{{ $benefit->employee->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold border
                                        @if($benefit->benefit_type == 'GSIS') bg-blue-50/50 text-blue-700 border-blue-100
                                        @elseif($benefit->benefit_type == 'PhilHealth') bg-emerald-50/50 text-emerald-700 border-emerald-100
                                        @elseif($benefit->benefit_type == 'Pag-IBIG') bg-amber-50/50 text-amber-700 border-amber-100
                                        @elseif($benefit->benefit_type == 'SSS') bg-rose-50/50 text-rose-700 border-rose-100
                                        @else bg-gray-50 text-gray-700 border-gray-150 @endif">
                                        {{ $benefit->benefit_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                    {{ $benefit->member_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border
                                        @if($benefit->enrollment_status == 'active') bg-emerald-50 text-emerald-700 border-emerald-150
                                        @elseif($benefit->enrollment_status == 'pending') bg-amber-50 text-amber-700 border-amber-150
                                        @elseif($benefit->enrollment_status == 'inactive') bg-gray-50 text-gray-700 border-gray-200
                                        @elseif($benefit->enrollment_status == 'suspended') bg-rose-50 text-rose-700 border-rose-150
                                        @else bg-gray-50 text-gray-700 border-gray-200 @endif">
                                        <span class="w-1.5 h-1.5 rounded-full 
                                            @if($benefit->enrollment_status == 'active') bg-emerald-500
                                            @elseif($benefit->enrollment_status == 'pending') bg-amber-500
                                            @elseif($benefit->enrollment_status == 'inactive') bg-gray-400
                                            @elseif($benefit->enrollment_status == 'suspended') bg-rose-500
                                            @else bg-gray-400 @endif"></span>
                                        {{ ucfirst($benefit->enrollment_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $benefit->enrollment_date?->format('M d, Y') ?? 'Not set' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('benefits.show', $benefit) }}" class="pds-edit-link inline-flex items-center justify-center p-2 text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 hover:text-indigo-800 transition-colors duration-150 shadow-sm" data-title="View Enrollment">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('benefits.edit', $benefit) }}" class="pds-edit-link inline-flex items-center justify-center p-2 text-gray-700 bg-white border border-gray-250 rounded-lg hover:bg-gray-50 hover:text-gray-800 transition-colors duration-150 shadow-sm" data-title="Edit Enrollment">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('benefits.destroy', $benefit) }}" class="inline" data-confirm="Are you sure you want to delete this benefit enrollment?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center p-2 text-red-700 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 hover:text-red-800 transition-colors duration-150 shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm font-medium text-gray-600">No government benefit enrollments found.</p>
                                        <a href="{{ route('benefits.create') }}" class="pds-edit-link mt-2 text-indigo-600 hover:text-indigo-900 text-sm font-semibold transition-colors duration-150" data-title="Add New Enrollment">
                                            Create the first enrollment
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(method_exists($benefits, 'links'))
                @if($benefits->hasPages())
                    <div class="bg-white px-6 py-4 border-t border-gray-150">
                        {{ $benefits->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
