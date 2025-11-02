<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $mfo->code }} &mdash; {{ $mfo->title }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">{{ $mfo->office->name }} &middot; Role: {{ $userRole }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('opcr.mfos.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Back to List</a>
                @if($canEdit)
                    <a href="{{ route('opcr.mfos.edit', $mfo) }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Edit MFO</a>
                @endif
                @if($canDelete)
                    <form method="POST" action="{{ route('opcr.mfos.destroy', $mfo) }}" onsubmit="return confirm('Delete this MFO? This action cannot be undone.');">
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
                <div class="lg:col-span-2">
                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Overview</h3>
                            <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <dt class="font-semibold text-gray-600">Office</dt>
                                    <dd class="mt-1 text-gray-900">{{ $mfo->office->name }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Hierarchy Path</dt>
                                    <dd class="mt-1 text-gray-900">{{ $mfo->full_code_path }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Level</dt>
                                    <dd class="mt-1 text-gray-900">Level {{ $mfo->level }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mfo->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $mfo->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </dd>
                                </div>
                                @if($mfo->parent)
                                    <div>
                                        <dt class="font-semibold text-gray-600">Parent MFO</dt>
                                        <dd class="mt-1 text-indigo-700">
                                            <a href="{{ route('opcr.mfos.show', $mfo->parent) }}" class="hover:underline">
                                                {{ $mfo->parent->code }} &mdash; {{ $mfo->parent->title }}
                                            </a>
                                        </dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="font-semibold text-gray-600">Created</dt>
                                    <dd class="mt-1 text-gray-900">{{ $mfo->created_at?->timezone('Asia/Manila')->format('F d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Last Updated</dt>
                                    <dd class="mt-1 text-gray-900">{{ $mfo->updated_at?->timezone('Asia/Manila')->format('F d, Y') }}</dd>
                                </div>
                            </dl>

                            @if($mfo->description)
                                <div class="mt-6">
                                    <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Description</h4>
                                    <p class="mt-2 text-gray-700 leading-relaxed">{{ $mfo->description }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Performance Snapshot</h3>
                            <dl class="mt-4 grid grid-cols-1 gap-y-4 text-sm">
                                <div>
                                    <dt class="font-semibold text-gray-600">Total Indicators</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-indigo-600">{{ $performanceStats['total_indicators'] }}</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <dt class="font-semibold text-gray-600">Rated</dt>
                                        <dd class="mt-1 text-lg text-gray-900">{{ $performanceStats['rated_indicators'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-gray-600">Met Targets</dt>
                                        <dd class="mt-1 text-lg text-gray-900">{{ $performanceStats['met_targets'] }}</dd>
                                    </div>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Rating Completion</dt>
                                    <dd class="mt-1 text-lg text-gray-900">{{ number_format($performanceStats['rating_completion_rate'], 2) }}%</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Average Rating</dt>
                                    <dd class="mt-1 text-lg text-gray-900">{{ number_format($performanceStats['average_rating'], 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-gray-600">Target Completion</dt>
                                    <dd class="mt-1 text-lg text-gray-900">{{ number_format($performanceStats['target_completion_rate'], 2) }}%</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Hierarchy Preview</h3>
                            <div class="mt-3 text-sm text-gray-700 space-y-2">
                                @php
                                    $renderBranch = function($nodes) use (&$renderBranch) {
                                        if ($nodes->isEmpty()) {
                                            return '';
                                        }
                                        $html = '<ul class="ml-4 border-l border-gray-200 pl-4 space-y-2">';
                                        foreach ($nodes as $node) {
                                            $html .= '<li>';
                                            $html .= '<span class="font-medium text-gray-800">' . e($node['code']) . '</span> &mdash; ' . e($node['title']);
                                            if (!empty($node['children'])) {
                                                $html .= $renderBranch(collect($node['children']));
                                            }
                                            $html .= '</li>';
                                        }
                                        $html .= '</ul>';
                                        return $html;
                                    };
                                @endphp
                                {!! $renderBranch(collect($mfoHierarchy)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Success Indicators</h3>
                        <p class="mt-1 text-sm text-gray-600">Track quantity, efficiency, and timeliness targets linked to this MFO.</p>
                    </div>
                    @can('si.create')
                        <a href="{{ route('opcr.success-indicators.create', ['mfo_id' => $mfo->id]) }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">New Indicator</a>
                    @endcan
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Targets</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($mfo->successIndicators as $indicator)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $indicator->code }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="font-medium text-gray-900">{{ $indicator->title }}</div>
                                        @if($indicator->description)
                                            <p class="mt-1 text-gray-500 text-xs">{{ Str::limit($indicator->description, 120) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div><span class="font-semibold">Quantity:</span> {{ $indicator->target_quantity ?? 'N/A' }}</div>
                                        <div><span class="font-semibold">Efficiency:</span> {{ $indicator->target_efficiency ?? 'N/A' }}</div>
                                        <div><span class="font-semibold">Timeliness:</span> {{ $indicator->target_timeliness ?? 'N/A' }}</div>
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
                                        <a href="{{ route('opcr.success-indicators.show', $indicator) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                        No success indicators defined yet. @can('si.create')<a href="{{ route('opcr.success-indicators.create', ['mfo_id' => $mfo->id]) }}" class="text-indigo-600 hover:text-indigo-900">Create the first indicator</a>@endcan.
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
