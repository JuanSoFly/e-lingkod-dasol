<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Evaluate OPCR: {{ $workflow->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">OPCR Evaluation - No Targets Found</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $workflow->office->name }} • {{ $workflow->period->year }} - {{ $workflow->period->semester }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('opcr.workflows.show', $workflow) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Back to Details
                            </a>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                No Performance Targets
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Warning Message -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    No Performance Targets Found
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>This OPCR workflow does not have any performance targets assigned to it. Evaluation cannot proceed without performance targets to assess.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Information -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Workflow Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Committed By:</span>
                                <span class="text-sm font-medium ml-2">{{ $workflow->committedBy->employee->full_name ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Current State:</span>
                                <span class="text-sm font-medium ml-2">{{ $workflow->workflow_state }}</span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Performance Period:</span>
                                <span class="text-sm font-medium ml-2">{{ $workflow->period->year }} - {{ $workflow->period->semester }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Possible Actions -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Recommended Actions</h3>

                        @if($canReturn)
                            <!-- Return for Revision Option -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-blue-900 mb-2">Option 1: Return for Revision</h4>
                                <p class="text-sm text-blue-800 mb-4">
                                    Return this OPCR to the Department Head to add performance targets before evaluation.
                                </p>

                                <form method="POST" action="{{ route('opcr.workflows.return', $workflow) }}">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="return_reason" value="Return Reason" />
                                            <textarea id="return_reason" name="return_reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Please specify why this OPCR is being returned..." required></textarea>
                                            <x-input-error :messages="$errors->get('return_reason')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="assessor_remarks" value="Additional Remarks (Optional)" />
                                            <textarea id="assessor_remarks" name="assessor_remarks" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Any additional comments or guidance for the Department Head..."></textarea>
                                            <x-input-error :messages="$errors->get('assessor_remarks')" class="mt-2" />
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                Return for Revision
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <!-- Contact Department Head Option -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Option 2: Contact Department Head</h4>
                            <p class="text-sm text-gray-800 mb-4">
                                Reach out to the Department Head to add performance targets to this OPCR workflow before proceeding with evaluation.
                            </p>
                            <div class="text-sm text-gray-600">
                                <p><strong>Office:</strong> {{ $workflow->office->name }}</p>
                                <p><strong>Workflow ID:</strong> #{{ $workflow->id }}</p>
                                <p><strong>Title:</strong> {{ $workflow->title }}</p>
                            </div>
                        </div>

                        <!-- System Administrator Option -->
                        @auth()->user()->hasRole(['Super Admin', 'HR Admin'])
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-orange-900 mb-2">Option 3: Administrative Action</h4>
                                <p class="text-sm text-orange-800 mb-4">
                                    As an administrator, you can investigate this data integrity issue and potentially add targets manually or contact the system administrator.
                                </p>
                                <div class="text-sm text-orange-700">
                                    <p><strong>Note:</strong> This appears to be a data integrity issue where OPCR workflows exist without associated performance targets.</p>
                                </div>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>