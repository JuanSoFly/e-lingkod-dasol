<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Leave Application Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><dt class="text-sm font-medium text-gray-500">Employee</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->employee->first_name }} {{ $leaveApplication->employee->last_name }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Leave Type</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->leaveType->name }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Dates Requested</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->start_date->format('M d, Y') }} to {{ $leaveApplication->end_date->format('M d, Y') }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Days</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->days_requested }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Applied On</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->applied_date->format('M d, Y') }}</dd></div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $leaveApplication->status == 'approved' ? 'bg-green-100 text-green-800' : ($leaveApplication->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">{{ ucfirst($leaveApplication->status) }}</span></dd>
                        </div>
                        <div class="md:col-span-2"><dt class="text-sm font-medium text-gray-500">Reason</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->reason }}</dd></div>
                        @if($leaveApplication->status != 'pending')
                            <div class="md:col-span-2"><dt class="text-sm font-medium text-gray-500">Remarks</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->remarks }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Action By</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->approver->name ?? 'N/A' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Action Date</dt><dd class="mt-1 text-sm text-gray-900">{{ $leaveApplication->approved_date?->format('M d, Y') ?? 'N/A' }}</dd></div>
                        @endif
                    </div>

                    @can('leave.approve')
                        @if($leaveApplication->status === 'pending')
                        <div class="mt-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900">Take Action</h3>
                            <div class="mt-4 flex items-center space-x-4">
                                <!-- Approve Form -->
                                <form action="{{ route('leave-applications.approve', $leaveApplication) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button>Approve</x-primary-button>
                                </form>
                                <!-- Reject Form -->
                                <form action="{{ route('leave-applications.reject', $leaveApplication) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex items-center space-x-2">
                                        <x-text-input name="remarks" placeholder="Reason for rejection..." required class="block w-full sm:w-64"/>
                                        <x-danger-button>Reject</x-danger-button>
                                    </div>
                                    <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                                </form>
                            </div>
                        </div>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>