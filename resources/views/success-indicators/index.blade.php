<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Success Indicators
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Indicator Management</h3>
                        <p class="mt-1 text-sm text-gray-600">Monitor quantity, efficiency, and timeliness targets across offices.</p>
                    </div>
                    @can('si.create')
                        <a href="{{ route('opcr.success-indicators.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">New Indicator</a>
                    @endcan
                </div>
                <div class="p-6 border-t border-gray-200">
                    <form method="GET" action="{{ route('opcr.success-indicators.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="filter_office" value="Office" />
                            <select id="filter_office" name="office_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">All Offices</option>
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}" {{ ($filters['office_id'] ?? '') == $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="filter_mfo" value="Major Final Output" />
                            <select id="filter_mfo" name="mfo_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">All MFOs</option>
                                @foreach($mfos as $mfo)
                                    <option value="{{ $mfo->id }}" {{ ($filters['mfo_id'] ?? '') == $mfo->id ? 'selected' : '' }}>
                                        {{ $mfo->code }} &mdash; {{ Str::limit($mfo->title, 60) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="filter_status" value="Status" />
                            <select id="filter_status" name="is_active" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">All</option>
                                <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="filter_search" value="Search" />
                            <input id="filter_search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Code or title" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="text">
                        </div>
                        <div class="md:col-span-4 flex justify-end space-x-3">
                            <a href="{{ route('opcr.success-indicators.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Clear</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title & Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MFO</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Targets</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($indicators as $indicator)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $indicator->code }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="font-medium text-gray-900">{{ $indicator->title }}</div>
                                        @if($indicator->description)
                                            <p class="mt-1 text-gray-500 text-xs">{{ Str::limit($indicator->description, 120) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="font-medium text-gray-900">{{ $indicator->mfo->code }}</div>
                                        <div class="text-xs text-gray-500">{{ Str::limit($indicator->mfo->title, 80) }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $indicator->mfo->office->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div><span class="font-semibold">Q:</span> {{ $indicator->target_quantity ?? 'N/A' }}</div>
                                        <div><span class="font-semibold">E:</span> {{ $indicator->target_efficiency ?? 'N/A' }}</div>
                                        <div><span class="font-semibold">T:</span> {{ $indicator->target_timeliness ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $indicator->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $indicator->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        @if($indicator->average_rating)
                                            <div class="mt-1 text-xs text-gray-500">Rating: {{ number_format($indicator->average_rating, 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('opcr.success-indicators.show', $indicator) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                        @can('si.edit')
                                            <a href="{{ route('opcr.success-indicators.edit', $indicator) }}" class="text-yellow-600 hover:text-yellow-900">Edit</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2h6v2a2 2 0 002 2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V6a2 2 0 00-2-2H9a2 2 0 00-2 2v1H6a2 2 0 00-2 2v8a2 2 0 002 2h1a2 2 0 002-2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No indicators found</h3>
                                        <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or create a new indicator.</p>
                                        @can('si.create')
                                            <div class="mt-6">
                                                <a href="{{ route('opcr.success-indicators.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                                                    Create Indicator
                                                </a>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($indicators->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $indicators->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
