<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ request('status') === 'pending' ? __('Leave Approvals') : __('My Leave Applications') }}
            </h2>
            @can('leave.create')
            <a href="{{ route('leave-applications.create') }}">
                <x-primary-button>
                    {{ __('Apply for Leave') }}
                </x-primary-button>
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @can('leave.approve')<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>@endcan
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($applications as $app)
                                    <tr>
                                        @can('leave.approve')<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $app->employee->first_name }} {{ $app->employee->last_name }}</td>@endcan
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $app->leaveType->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $app->start_date->format('M d, Y') }} - {{ $app->end_date->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $app->days_requested }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if($app->status == 'approved') bg-green-100 text-green-800 @elseif($app->status == 'rejected') bg-red-100 text-red-800 @else bg-yellow-100 text-yellow-800 @endif">
                                                {{ ucfirst($app->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end">
                                                <a href="{{ route('leave-applications.show', $app) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors duration-150">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No leave applications found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>