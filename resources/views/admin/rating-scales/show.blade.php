<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rating Scale Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ $ratingScale->name }}
                                    </h3>
                                    @if($ratingScale->is_default)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Default Scale
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
                        <div class="flex space-x-3">
                            @if(!$ratingScale->is_default && auth()->user()->can('opcr.manage'))
                                <a href="{{ route('admin.rating-scales.edit', $ratingScale) }}"
                                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </a>
                            @endif
                            @if(!$ratingScale->is_default && auth()->user()->can('opcr.manage'))
                                @if(!$ratingScale->is_default)
                                    <form action="{{ route('admin.rating-scales.set-default', $ratingScale) }}"
                                          method="POST"
                                          onsubmit="return confirm('Set this rating scale as the default? This will unset the current default.')"
                                          class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-2 border border-yellow-300 shadow-sm text-sm leading-4 font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                            </svg>
                                            Set as Default
                                        </button>
                                    </form>
                                @endif
                            @endif
                            <a href="{{ route('admin.rating-scales.index') }}"
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- QET Weights -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- QET Weight Configuration -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">QET Weight Configuration</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                @foreach($ratingScale->getQETWeights() as $component => $weight)
                                    <div class="text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-2">
                                            <span class="text-2xl font-bold text-indigo-600">
                                                {{ round($weight * 100) }}%
                                            </span>
                                        </div>
                                        <h4 class="text-sm font-medium text-gray-900 capitalize">{{ $component }}</h4>
                                        <p class="text-xs text-gray-500">Weight: {{ $weight }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Weight Distribution Chart -->
                            <div class="mt-6">
                                <div class="relative">
                                    <div class="overflow-hidden h-8 text-xs flex rounded bg-gray-200">
                                        @foreach($ratingScale->getQETWeights() as $component => $weight)
                                            <div class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $component === 'quantity' ? 'bg-blue-500' : ($component === 'efficiency' ? 'bg-green-500' : 'bg-yellow-500') }}"
                                                 style="width: {{ $weight * 100 }}%">
                                                {{ round($weight * 100) }}%
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex justify-between mt-2 text-xs text-gray-600">
                                    <span>Quantity</span>
                                    <span>Efficiency</span>
                                    <span>Timeliness</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rating Values -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Rating Scale Values</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Rating Value
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Rating Label
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Percentage Range
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Color
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Order
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($ratingScale->ratingValues()->ordered()->get() as $value)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $value->rating_value }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $value->rating_label }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $value->min_percentage }}% - {{ $value->max_percentage }}%
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="inline-block w-4 h-4 rounded" style="background-color: {{ $value->color_code }}"></span>
                                                        <span class="text-xs">{{ $value->color_code }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $value->display_order }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Usage Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Usage Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-500">Office Usage</span>
                                        <span class="text-sm font-bold text-gray-900">{{ $ratingScale->offices_count }}</span>
                                    </div>
                                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($ratingScale->offices_count * 10, 100) }}%"></div>
                                    </div>
                                </div>

                                <div class="text-sm text-gray-600">
                                    <p class="font-medium">Created:</p>
                                    <p>{{ $ratingScale->created_at->format('M d, Y \a\t h:i A') }}</p>
                                    <p class="mt-2 font-medium">Last Updated:</p>
                                    <p>{{ $ratingScale->updated_at->format('M d, Y \a\t h:i A') }}</p>
                                </div>

                                @if($ratingScale->created_by)
                                    <div class="text-sm text-gray-600">
                                        <p class="font-medium">Created By:</p>
                                        <p>{{ $ratingScale->created_by }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Office Usage -->
                    @if($officeUsage->count() > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Current Office Usage</h3>
                                <div class="space-y-3">
                                    @foreach($officeUsage as $usage)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $usage->office->name }}</p>
                                                <p class="text-xs text-gray-500">
                                                    Since: {{ $usage->effective_from->format('M d, Y') }}
                                                </p>
                                            </div>
                                            <div class="text-xs text-green-600 font-medium">
                                                Active
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Configuration Export -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Configuration</h3>
                            <div class="space-y-3">
                                <button onclick="copyConfiguration(this)" class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Copy Configuration
                                </button>
                                <div id="configuration-json" class="hidden">
                                    {{ json_encode($ratingScale->getConfigurationArray(), JSON_PRETTY_PRINT) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyConfiguration(buttonElement, copyEvent) {
            const configElement = document.getElementById('configuration-json');
            if (!configElement) {
                console.error('Configuration element not found.');
                return;
            }

            const configText = configElement.textContent;
            const button = buttonElement instanceof HTMLElement
                ? buttonElement
                : (copyEvent || window.event)?.currentTarget || (copyEvent || window.event)?.target?.closest('button');

            navigator.clipboard.writeText(configText).then(function() {
                // Show success message
                if (!button) {
                    return;
                }
                const originalHTML = button.innerHTML;
                button.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Copied!
                `;
                button.classList.add('bg-green-50', 'text-green-700', 'border-green-300');

                setTimeout(function() {
                    button.innerHTML = originalHTML;
                    button.classList.remove('bg-green-50', 'text-green-700', 'border-green-300');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy configuration: ', err);
                alert('Failed to copy configuration. Please try again.');
            });
        }
    </script>
</x-app-layout>
