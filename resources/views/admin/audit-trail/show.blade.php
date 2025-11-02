<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Audit Trail Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        Activity #{{ $activity->id }}
                                    </h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($activity->properties['action_type'] === 'opcr_workflow')
                                            bg-blue-100 text-blue-800
                                        @elseif($activity->properties['action_type'] === 'mfo')
                                            bg-green-100 text-green-800
                                        @elseif($activity->properties['action_type'] === 'success_indicator')
                                            bg-purple-100 text-purple-800
                                        @elseif($activity->properties['action_type'] === 'office')
                                            bg-yellow-100 text-yellow-800
                                        @elseif($activity->properties['action_type'] === 'user')
                                            bg-indigo-100 text-indigo-800
                                        @else
                                            bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $activity->properties['action_type'] ?? 'system')) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $activity->created_at->format('F d, Y \a\t h:i:s A') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.audit-trail.index') }}"
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Activity Details -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Activity Details</h3>
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Description</h4>
                                    <p class="mt-1 text-sm text-gray-900">{{ $activity->description }}</p>
                                </div>

                                @if($activity->properties['action'] ?? null)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Action</h4>
                                        <p class="mt-1 text-sm text-gray-900">{{ $activity->properties['action'] }}</p>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Activity ID</h4>
                                        <p class="mt-1 text-sm text-gray-900">#{{ $activity->id }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Batch UUID</h4>
                                        <p class="mt-1 text-sm text-gray-900">{{ $activity->batch_uuid ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Log Name</h4>
                                        <p class="mt-1 text-sm text-gray-900">{{ $activity->log_name ?? 'default' }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Event</h4>
                                        <p class="mt-1 text-sm text-gray-900">{{ $activity->event ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subject Information -->
                    @if($activity->subject)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Subject Information</h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700">Subject Type</h4>
                                            <p class="mt-1 text-sm text-gray-900">{{ class_basename($activity->subject_type) }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700">Subject ID</h4>
                                            <p class="mt-1 text-sm text-gray-900">#{{ $activity->subject_id }}</p>
                                        </div>
                                    </div>

                                    @if(method_exists($activity->subject, 'getAuditTrailSummary'))
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700">Subject Details</h4>
                                            <div class="mt-1 p-3 bg-gray-50 rounded-md">
                                                <p class="text-sm text-gray-900">{{ $activity->subject->getAuditTrailSummary() }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Properties -->
                    @if($activity->properties && count($activity->properties) > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Properties</h3>
                                <div class="space-y-3">
                                    @foreach($activity->properties as $key => $value)
                                        @if(!in_array($key, ['action', 'action_type']))
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $key)) }}</h4>
                                                </div>
                                                <div class="md:col-span-2">
                                                    @if(is_array($value))
                                                        <pre class="mt-1 text-xs text-gray-900 bg-gray-50 p-2 rounded overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    @elseif(is_bool($value))
                                                        <p class="mt-1 text-sm text-gray-900">{{ $value ? 'Yes' : 'No' }}</p>
                                                    @else
                                                        <p class="mt-1 text-sm text-gray-900">{{ $value }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- User Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">User Information</h3>
                            @if($activity->causer)
                                <div class="space-y-3">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Name</h4>
                                        <p class="mt-1 text-sm text-gray-900">{{ $activity->causer->name }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Email</h4>
                                        <p class="mt-1 text-sm text-gray-900">{{ $activity->causer->email }}</p>
                                    </div>
                                    @if($activity->causer->employee)
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700">Employee</h4>
                                            <p class="mt-1 text-sm text-gray-900">{{ $activity->causer->employee->full_name }}</p>
                                            <p class="text-xs text-gray-500">{{ $activity->causer->employee->employee_number }}</p>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="space-y-3">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Type</h4>
                                        <p class="mt-1 text-sm text-gray-900">System Activity</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Description</h4>
                                        <p class="mt-1 text-sm text-gray-600">This activity was performed by the system automatically.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Technical Details -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Technical Details</h3>
                            <div class="space-y-3">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Timestamp</h4>
                                    <p class="mt-1 text-sm text-gray-900">{{ $activity->created_at->toDateTimeString() }}</p>
                                </div>

                                @if($activity->properties['user_ip'] ?? null)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">IP Address</h4>
                                        <p class="mt-1 text-sm text-gray-900">{{ $activity->properties['user_ip'] }}</p>
                                    </div>
                                @endif

                                @if($activity->properties['user_agent'] ?? null)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">User Agent</h4>
                                        <p class="mt-1 text-xs text-gray-600 break-all">{{ $activity->properties['user_agent'] }}</p>
                                    </div>
                                @endif

                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Database ID</h4>
                                    <p class="mt-1 text-sm text-gray-900">{{ $activity->id }}</p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-medium text-gray-700">Causer ID</h4>
                                    <p class="mt-1 text-sm text-gray-900">{{ $activity->causer_id ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Activities -->
                    @if(auth()->user()->can('audit.view'))
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Related Activities</h3>
                                <div class="space-y-2">
                                    @if($activity->causer)
                                        <a href="{{ route('admin.audit-trail.index', ['causer_id' => $activity->causer_id]) }}"
                                           class="block w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                                            <p class="text-sm font-medium text-gray-900">View all activities by this user</p>
                                            <p class="text-xs text-gray-500">{{ $activity->causer->name }}</p>
                                        </a>
                                    @endif

                                    @if($activity->properties['action_type'] ?? null)
                                        <a href="{{ route('admin.audit-trail.index', ['action_type' => $activity->properties['action_type']]) }}"
                                           class="block w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                                            <p class="text-sm font-medium text-gray-900">View all {{ $activity->properties['action_type'] }} activities</p>
                                            <p class="text-xs text-gray-500">Action type: {{ $activity->properties['action_type'] }}</p>
                                        </a>
                                    @endif

                                    @if($activity->subject)
                                        <a href="{{ route('admin.audit-trail.index') }}?subject_type={{ urlencode($activity->subject_type) }}&subject_id={{ $activity->subject_id }}"
                                           class="block w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                                            <p class="text-sm font-medium text-gray-900">View all activities for this subject</p>
                                            <p class="text-xs text-gray-500">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</p>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>