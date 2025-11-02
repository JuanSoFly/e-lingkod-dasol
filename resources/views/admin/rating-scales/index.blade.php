<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rating Scale Configuration
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Header with Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Manage Rating Scales
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Configure rating scales and QET weights for OPCR evaluation
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.rating-scales.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New Rating Scale
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search rating scales..."
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="active_only" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="active_only"
                                    name="active_only"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="1" {{ request('active_only', '1') == '1' ? 'selected' : '' }}>Active Only</option>
                                <option value="0" {{ request('active_only') == '0' ? 'selected' : '' }}>All Scales</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                    form="filter-form"
                                    class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating Scales List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($ratingScales->count() > 0)
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach($ratingScales as $ratingScale)
                                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <!-- Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <h4 class="text-lg font-medium text-gray-900">
                                                    {{ $ratingScale->name }}
                                                </h4>
                                                @if($ratingScale->is_default)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Default
                                                    </span>
                                                @endif
                                                @if($ratingScale->is_active)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                            @if($ratingScale->description)
                                                <p class="mt-1 text-sm text-gray-600">{{ $ratingScale->description }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- QET Weights -->
                                    <div class="mb-4">
                                        <h5 class="text-sm font-medium text-gray-700 mb-2">QET Weights</h5>
                                        <div class="grid grid-cols-3 gap-2 text-xs">
                                            <div class="bg-gray-50 rounded p-2">
                                                <span class="font-medium">Quantity:</span>
                                                <span class="float-right">{{ $ratingScale->getQETWeights()['quantity'] * 100 }}%</span>
                                            </div>
                                            <div class="bg-gray-50 rounded p-2">
                                                <span class="font-medium">Efficiency:</span>
                                                <span class="float-right">{{ $ratingScale->getQETWeights()['efficiency'] * 100 }}%</span>
                                            </div>
                                            <div class="bg-gray-50 rounded p-2">
                                                <span class="font-medium">Timeliness:</span>
                                                <span class="float-right">{{ $ratingScale->getQETWeights()['timeliness'] * 100 }}%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Rating Values Preview -->
                                    <div class="mb-4">
                                        <h5 class="text-sm font-medium text-gray-700 mb-2">Rating Scale</h5>
                                        <div class="space-y-1">
                                            @foreach($ratingScale->ratingValues()->take(3)->get() as $value)
                                                <div class="flex items-center justify-between text-xs">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="inline-block w-3 h-3 rounded" style="background-color: {{ $value->color_code }}"></span>
                                                        <span>{{ $value->rating_value }} - {{ $value->rating_label }}</span>
                                                    </div>
                                                    <span class="text-gray-500">{{ $value->min_percentage }}% - {{ $value->max_percentage }}%</span>
                                                </div>
                                            @endforeach
                                            @if($ratingScale->ratingValues()->count() > 3)
                                                <div class="text-xs text-gray-500 text-center">
                                                    ... and {{ $ratingScale->ratingValues()->count() - 3 }} more
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Usage Info -->
                                    <div class="mb-4 flex items-center justify-between text-xs text-gray-500">
                                        <span>Used by {{ $ratingScale->offices_count }} office(s)</span>
                                        <span>Created {{ $ratingScale->created_at->format('M d, Y') }}</span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.rating-scales.show', $ratingScale) }}"
                                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                View Details
                                            </a>
                                            @if(!$ratingScale->is_default && auth()->user()->can('opcr.manage'))
                                                <a href="{{ route('admin.rating-scales.edit', $ratingScale) }}"
                                                   class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                                    Edit
                                                </a>
                                            @endif
                                        </div>

                                        @if(auth()->user()->can('opcr.manage'))
                                            <div class="flex space-x-2">
                                                @if(!$ratingScale->is_default)
                                                    <form action="{{ route('admin.rating-scales.set-default', $ratingScale) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('Set this rating scale as the default?')">
                                                        @csrf
                                                        <button type="submit"
                                                                class="text-yellow-600 hover:text-yellow-900 text-sm font-medium"
                                                                title="Set as Default">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($ratingScale->canBeDeleted())
                                                    <form action="{{ route('admin.rating-scales.destroy', $ratingScale) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('Are you sure you want to delete this rating scale?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="text-red-600 hover:text-red-900 text-sm font-medium"
                                                                title="Delete">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $ratingScales->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No rating scales found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating your first rating scale.</p>
                            <div class="mt-6">
                                <a href="{{ route('admin.rating-scales.create') }}"
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Create Rating Scale
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for filters -->
    <form id="filter-form" action="{{ route('admin.rating-scales.index') }}" method="GET" class="hidden">
        @if(request()->has('search'))
            <input type="hidden" name="search" value="{{ request('search') }}">
        @endif
        @if(request()->has('active_only'))
            <input type="hidden" name="active_only" value="{{ request('active_only') }}">
        @endif
    </form>
</x-app-layout>