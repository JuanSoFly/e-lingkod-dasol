<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            OPCR Office Assignments
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Header Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Office Role Assignments</h3>
                            <p class="mt-1 text-sm text-gray-600">Manage Department Heads, Assessors, and Final Approvers for OPCR workflows</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('opcr.offices.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Manage Offices
                            </a>
                            <a href="{{ route('opcr.offices.assignments.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New Assignment
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Office Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($offices ?? [] as $office)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-medium text-gray-900">{{ $office->name }}</h4>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $office->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $office->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <!-- Department Heads -->
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Department Heads:</span>
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-blue-600">{{ $office->department_heads->count() }}</span>
                                        @if($office->department_heads->count() > 0)
                                            <button type="button" onclick="toggleDetails('office-{{ $office->id }}-heads')" class="ml-2 text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Assessors -->
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Assessors:</span>
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-purple-600">{{ $office->assessors->count() }}</span>
                                        @if($office->assessors->count() > 0)
                                            <button type="button" onclick="toggleDetails('office-{{ $office->id }}-assessors')" class="ml-2 text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Final Approvers -->
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Final Approvers:</span>
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-green-600">{{ $office->final_approvers->count() }}</span>
                                        @if($office->final_approvers->count() > 0)
                                            <button type="button" onclick="toggleDetails('office-{{ $office->id }}-approvers')" class="ml-2 text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Expanded Details -->
                            <div id="office-{{ $office->id }}-heads" class="hidden mt-4 pt-4 border-t border-gray-200">
                                <h5 class="text-sm font-medium text-gray-900 mb-2">Department Heads</h5>
                                <div class="space-y-2">
                                    @foreach($office->department_heads as $user)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-700">{{ $user->employee->full_name ?? $user->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $user->email }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div id="office-{{ $office->id }}-assessors" class="hidden mt-4 pt-4 border-t border-gray-200">
                                <h5 class="text-sm font-medium text-gray-900 mb-2">Assessors</h5>
                                <div class="space-y-2">
                                    @foreach($office->assessors as $user)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-700">{{ $user->employee->full_name ?? $user->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $user->email }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div id="office-{{ $office->id }}-approvers" class="hidden mt-4 pt-4 border-t border-gray-200">
                                <h5 class="text-sm font-medium text-gray-900 mb-2">Final Approvers</h5>
                                <div class="space-y-2">
                                    @foreach($office->final_approvers as $user)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-700">{{ $user->employee->full_name ?? $user->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $user->email }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between">
                                <a href="{{ route('opcr.offices.assignments.create', ['office_id' => $office->id]) }}" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Assign User
                                </a>
                                <a href="{{ route('opcr.offices.show', $office) }}" class="inline-flex items-center px-3 py-1 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    View Office
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Recent Assignments Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Recent Assignments</h3>
                        <a href="{{ route('opcr.offices.assignments.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">View All</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Office
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Assigned By
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date Assigned
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
                                @forelse($recentAssignments ?? [] as $assignment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $assignment->user->employee->full_name ?? $assignment->user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $assignment->user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $assignment->office->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($assignment->role === 'Department Head') bg-blue-100 text-blue-800
                                                @elseif($assignment->role === 'Assessor') bg-purple-100 text-purple-800
                                                @elseif($assignment->role === 'Final Approver') bg-green-100 text-green-800
                                                @endif">
                                                {{ $assignment->role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $assignment->assignedBy->employee->full_name ?? $assignment->assignedBy->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $assignment->assigned_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $assignment->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if($assignment->is_active)
                                                <form method="POST" action="{{ route('opcr.offices.assignments.update', $assignment) }}" class="inline" onsubmit="return confirm('Are you sure you want to deactivate this assignment?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="text-orange-600 hover:text-orange-900">Deactivate</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('opcr.offices.assignments.update', $assignment) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit" class="text-green-600 hover:text-green-900">Activate</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <h3 class="mt-2 text-sm font-medium text-gray-900">No assignments found</h3>
                                            <p class="mt-1 text-sm text-gray-500">Get started by assigning users to offices.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDetails(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.toggle('hidden');
            }
        }
    </script>
</x-app-layout>