<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ Auth::user()->can('performance.evaluate') && !request()->routeIs('performance-targets.*') ? 'Performance Reviews' : 'My IPCR' }}
            </h2>
            @if($selectedPeriodId && $periods->firstWhere('id', $selectedPeriodId)->status == 'active' && Auth::user()->can('performance.create'))
            <a href="{{ route('performance-targets.create', ['period_id' => $selectedPeriodId]) }}">
                <x-primary-button>
                    {{ __('Add Target') }}
                </x-primary-button>
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
             @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            <div class="mb-4">
                <form method="GET" action="{{ route('performance-targets.index') }}">
                    <x-input-label for="period_id" value="Select Performance Period" />
                    <select name="period_id" id="period_id" onchange="this.form.submit()" class="mt-1 block w-full md:w-1/3 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        @forelse($periods as $period)
                            <option value="{{ $period->id }}" @selected($period->id == $selectedPeriodId)>{{ $period->year }} - {{ $period->semester }}</option>
                        @empty
                             <option>No periods available</option>
                        @endforelse
                    </select>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                     <h3 class="text-lg font-bold">IPCR for {{ $periods->firstWhere('id', $selectedPeriodId)?->semester ?? 'N/A' }} {{ $periods->firstWhere('id', $selectedPeriodId)?->year ?? '' }}</h3>
                    <div class="overflow-x-auto mt-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objective / Target</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Self Rating</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supervisor Rating</th>
                                    <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                             <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($targets as $target)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-normal">
                                            <p class="text-sm font-medium text-gray-900">{{ $target->objective }}</p>
                                            <p class="text-sm text-gray-500 mt-1">Target: {{ $target->target }}</p>
                                            <p class="text-xs text-gray-400 mt-2">Indicator: {{ $target->success_indicator }}</p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $target->weight }}%</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if ($target->rating && $target->rating->self_rating)
                                                {{ $target->rating->self_rating }}
                                            @else
                                                @can('create', App\Models\PerformanceRating::class)
                                                <form action="{{ route('performance-ratings.self-rate', $target) }}" method="POST">
                                                    @csrf
                                                    <div class="flex items-center">
                                                        <x-text-input name="self_rating" type="number" min="1" max="5" class="w-20" required />
                                                        <x-primary-button class="ms-2">Rate</x-primary-button>
                                                    </div>
                                                </form>
                                                @else
                                                    N/A
                                                @endcan
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if ($target->rating && $target->rating->supervisor_rating)
                                                {{ $target->rating->supervisor_rating }}
                                            @else
                                                 @can('evaluate', App\Models\PerformanceRating::class)
                                                    @if($target->rating && $target->rating->self_rating)
                                                    <form action="{{ route('performance-ratings.supervisor-rate', $target) }}" method="POST">
                                                        @csrf
                                                        <div class="flex items-center">
                                                            <x-text-input name="supervisor_rating" type="number" min="1" max="5" class="w-20" required />
                                                            <x-primary-button class="ms-2">Rate</x-primary-button>
                                                        </div>
                                                    </form>
                                                    @else
                                                    Awaiting self-rating
                                                    @endif
                                                @else
                                                    N/A
                                                @endcan
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @can('update', $target)
                                            <a href="{{ route('performance-targets.edit', $target) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            @endcan
                                            @can('delete', $target)
                                            <form action="{{ route('performance-targets.destroy', $target) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No targets set for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>