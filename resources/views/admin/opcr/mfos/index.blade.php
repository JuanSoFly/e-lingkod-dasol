<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Major Final Outputs (MFOs)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">MFO Management</h3>
                            <p class="mt-1 text-sm text-gray-600">Configure Major Final Outputs and Success Indicators</p>
                        </div>
                        <a href="{{ route('opcr.mfos.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            New MFO
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('opcr.mfos.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="office_id" value="Office" />
                                <select id="office_id" name="office_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Offices</option>
                                    @foreach($offices ?? [] as $office)
                                        <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>
                                            {{ $office->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="level" value="MFO Level" />
                                <select id="level" name="level" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Levels</option>
                                    <option value="1" {{ request('level') == '1' ? 'selected' : '' }}>Level 1</option>
                                    <option value="2" {{ request('level') == '2' ? 'selected' : '' }}>Level 2</option>
                                    <option value="3" {{ request('level') == '3' ? 'selected' : '' }}>Level 3</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="search" value="Search" />
                                <input type="text" id="search" name="search" value="{{ request('search') }}" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="MFO code or title...">
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('opcr.mfos.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Clear
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MFOs List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Code & Title
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Office
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Level
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Success Indicators
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Average Rating
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($mfos ?? [] as $mfo)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $mfo->full_code_path }}</div>
                                        <div class="text-sm text-gray-500">{{ $mfo->title }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $mfo->office->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Level {{ $mfo->level }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center space-x-4">
                                            <span>{{ $mfo->successIndicators->count() }} total</span>
                                            <span class="text-green-600">{{ $mfo->successIndicators->where('is_active', true)->count() }} active</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($mfo->ratings_summary['rated_indicators'] > 0)
                                            <div class="flex items-center">
                                                <span class="font-medium">{{ number_format($mfo->ratings_summary['average_rating'], 2) }}</span>
                                                <span class="ml-2 text-xs text-gray-500">({{ $mfo->ratings_summary['rated_indicators'] }} rated)</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">No ratings</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mfo->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $mfo->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('opcr.success-indicators.index', ['mfo_id' => $mfo->id]) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Indicators
                                        </a>
                                        <a href="{{ route('opcr.mfos.show', $mfo) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            View
                                        </a>
                                        <a href="{{ route('opcr.mfos.edit', $mfo) }}" class="text-yellow-600 hover:text-yellow-900">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No MFOs found</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            @if(request('office_id') || request('level') || request('search'))
                                                Try adjusting your filters or
                                                <a href="{{ route('opcr.mfos.index') }}" class="text-indigo-600 hover:text-indigo-500">clear all filters</a>.
                                            @else
                                                Get started by creating your first MFO.
                                            @endif
                                        </p>
                                        @if(!request('office_id') && !request('level') && !request('search'))
                                            <div class="mt-6">
                                                <a href="{{ route('opcr.mfos.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    </svg>
                                                    Create MFO
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>