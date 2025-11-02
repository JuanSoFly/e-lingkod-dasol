<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $indicator->code }} &mdash; {{ $indicator->title }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">{{ $indicator->mfo->code }} &middot; {{ $indicator->mfo->title }} &middot; Role: {{ $userRole }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('opcr.success-indicators.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Back to List</a>
                @if($canEdit)
                    <a href="{{ route('opcr.success-indicators.edit', $indicator) }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Edit</a>
                @endif
                @if($canDelete)
                    <form method="POST" action="{{ route('opcr.success-indicators.destroy', $indicator) }}" onsubmit="return confirm('Delete this success indicator?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">Delete</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Overview</h3>
                            <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <dt class="font-semibold text-gray-600">Office</dt>
                                    <dd class="mt-1 text-gray-900">{{ $indicator->mfo->office->name }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Measurement Unit</dt>
                                    <dd class="mt-1 text-gray-900">{{ $indicator->measurement_unit ?? 'Not specified' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $indicator->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $indicator->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Target Achieved</dt>
                                    <dd class="mt-1 text-gray-900">{{ $indicator->is_target_met ? 'Yes' : 'No' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Created</dt>
                                    <dd class="mt-1 text-gray-900">{{ $indicator->created_at?->timezone('Asia/Manila')->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Last Updated</dt>
                                    <dd class="mt-1 text-gray-900">{{ $indicator->updated_at?->timezone('Asia/Manila')->format('F d, Y') }}</dd>
                                </div>
                            </dl>

                            @if($indicator->description)
                                <div class="mt-6">
                                    <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Description</h4>
                                    <p class="mt-2 text-gray-700 leading-relaxed">{{ $indicator->description }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Targets & Accomplishments</h3>
                                    <p class="mt-1 text-sm text-gray-600">Compare planned metrics against reported results.</p>
                                </div>
                                @if($canEdit)
                                    <a href="{{ route('opcr.success-indicators.edit', $indicator) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Update</a>
                                @endif
                            </div>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                                <div>
                                    <h4 class="font-semibold text-gray-700">Target Metrics</h4>
                                    <dl class="mt-2 space-y-2">
                                        <div>
                                            <dt class="text-gray-500">Quantity</dt>
                                            <dd class="text-gray-900">{{ $indicator->target_quantity ?? 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500">Efficiency</dt>
                                            <dd class="text-gray-900">{{ $indicator->target_efficiency ?? 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500">Timeliness</dt>
                                            <dd class="text-gray-900">{{ $indicator->target_timeliness ?? 'N/A' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-700">Reported Accomplishments</h4>
                                    <dl class="mt-2 space-y-2">
                                        <div>
                                            <dt class="text-gray-500">Quantity</dt>
                                            <dd class="text-gray-900">{{ $indicator->accomplished_quantity ?? 'Pending' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500">Efficiency</dt>
                                            <dd class="text-gray-900">{{ $indicator->accomplished_efficiency ?? 'Pending' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500">Timeliness</dt>
                                            <dd class="text-gray-900">{{ $indicator->accomplished_timeliness ?? 'Pending' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500">Performance %</dt>
                                            <dd class="text-gray-900">{{ $indicator->performance_percentage ? number_format($indicator->performance_percentage, 2) . '%' : 'Pending' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            @if($indicator->accomplishment_notes)
                                <div class="mt-6">
                                    <h4 class="font-semibold text-gray-700">Accomplishment Notes</h4>
                                    <p class="mt-2 text-gray-700 leading-relaxed">{{ $indicator->accomplishment_notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Performance Targets</h3>
                            <p class="mt-1 text-sm text-gray-600">Targets aligned to individual employees or periods.</p>
                            @if($indicator->performanceTargets->isEmpty())
                                <p class="mt-4 text-sm text-gray-500">No performance targets assigned yet.</p>
                            @else
                                <div class="mt-4 space-y-4 text-sm">
                                    @foreach($indicator->performanceTargets as $target)
                                        <div class="border border-gray-200 rounded-md p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">
                                                        {{ $target->performancePeriod?->name ?? 'Period not set' }}
                                                    </h4>
                                                    <p class="text-xs text-gray-500">Assigned to: {{ $target->employee?->full_name ?? 'Unassigned' }}</p>
                                                </div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $target->is_target_met ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $target->is_target_met ? 'Met' : 'Pending' }}
                                                </span>
                                            </div>
                                            @if($target->notes)
                                                <p class="mt-3 text-gray-700">{{ $target->notes }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Performance Snapshot</h3>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-600">Total Ratings</dt>
                                    <dd class="text-gray-900 font-semibold">{{ $performanceStats['total_ratings'] }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-600">Average Rating</dt>
                                    <dd class="text-gray-900 font-semibold">{{ number_format($performanceStats['average_rating'], 2) }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-600">Total Targets</dt>
                                    <dd class="text-gray-900 font-semibold">{{ $performanceStats['total_targets'] }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-600">Targets Met</dt>
                                    <dd class="text-gray-900 font-semibold">{{ $performanceStats['met_targets'] }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-600">Target Completion</dt>
                                    <dd class="text-gray-900 font-semibold">{{ number_format($performanceStats['target_completion_rate'], 2) }}%</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-600">Last Update</dt>
                                    <dd class="text-gray-900 font-semibold">{{ \Carbon\Carbon::parse($performanceStats['last_accomplishment_update'])->timezone('Asia/Manila')->format('M d, Y H:i') }}</dd>
                                </div>
                            </dl>

                            <div class="mt-6">
                                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Rating Distribution</h4>
                                <div class="mt-3 space-y-2 text-sm">
                                    @foreach($performanceStats['rating_distribution'] as $rating => $count)
                                        <div class="flex items-center">
                                            <span class="w-10 text-gray-600">{{ $rating }}★</span>
                                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-2 bg-indigo-500" style="width: {{ $performanceStats['total_ratings'] ? ($count / $performanceStats['total_ratings']) * 100 : 0 }}%"></div>
                                            </div>
                                            <span class="ml-3 text-gray-700">{{ $count }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">Recent Ratings</h3>
                                @if($canRate)
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900 text-sm">Record Rating</a>
                                @endif
                            </div>
                            @if($recentRatings->isEmpty())
                                <p class="mt-4 text-sm text-gray-500">No ratings submitted yet.</p>
                            @else
                                <ul class="mt-4 space-y-4 text-sm">
                                    @foreach($recentRatings as $rating)
                                        <li class="border border-gray-200 rounded-md p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="font-semibold text-gray-900">{{ $rating->performancePeriod?->name ?? 'Unassigned Period' }}</p>
                                                    <p class="text-xs text-gray-500">Rated by {{ $rating->ratedBy?->name ?? 'System' }} on {{ $rating->created_at->timezone('Asia/Manila')->format('M d, Y H:i') }}</p>
                                                </div>
                                                <span class="text-lg font-bold text-indigo-600">{{ number_format($rating->average_rating, 2) }}</span>
                                            </div>
                                            @if($rating->remarks)
                                                <p class="mt-3 text-gray-700">{{ $rating->remarks }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
