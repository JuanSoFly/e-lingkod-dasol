<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Performance Period Calendar
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
                                Calendar View
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Visual overview of all performance periods
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.performance-periods.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                                List View
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                            <select id="year" name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Years</option>
                                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label for="office_id" class="block text-sm font-medium text-gray-700">Office</label>
                            <select id="office_id" name="office_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Offices</option>
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar View -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Calendar Placeholder -->
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Calendar View</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Performance periods will be displayed in a calendar format here.
                        </p>
                        <div class="mt-6">
                            <p class="text-xs text-gray-400">
                                This feature is under development. For now, please use the list view to see all performance periods.
                            </p>
                        </div>
                    </div>

                    <!-- Period Timeline Preview -->
                    @if(isset($calendarData) && count($calendarData) > 0)
                        <div class="mt-8">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Upcoming Periods</h4>
                            <div class="space-y-3">
                                @foreach($calendarData as $period)
                                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                        <div class="flex-shrink-0">
                                            <div class="w-3 h-3 bg-indigo-500 rounded-full"></div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $period->year }} - {{ ucfirst($period->semester) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $period->start_date->format('M d') }} - {{ $period->end_date->format('M d, Y') }}
                                            </div>
                                        </div>
                                        <div class="ml-auto">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($period->status === 'active')
                                                    bg-green-100 text-green-800
                                                @elseif($period->status === 'upcoming')
                                                    bg-yellow-100 text-yellow-800
                                                @else
                                                    bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst($period->status) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>