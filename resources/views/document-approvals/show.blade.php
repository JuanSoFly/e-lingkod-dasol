<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Document Approval Request
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $documentApprovalRequest->reference_number }}</p>
            </div>
            <div class="flex items-center space-x-3">
                @include('document-approvals.partials.status-badge', ['status' => $documentApprovalRequest->status])
                @can('update', $documentApprovalRequest)
                    @if($documentApprovalRequest->status === 'draft')
                        <x-secondary-button onclick="window.location.href='{{ route('document-approvals.edit', $documentApprovalRequest) }}'">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit
                        </x-secondary-button>
                    @endif
                @endcan
            </div>
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

        @if ($errors->any())
            <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <div class="flex">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Request Details -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Request Details</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Employee</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $documentApprovalRequest->employee->last_name }}, {{ $documentApprovalRequest->employee->first_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Document Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $documentApprovalRequest->document_type }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Purpose</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $documentApprovalRequest->purpose ?? 'Not specified' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Priority</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($documentApprovalRequest->priority === 'high') bg-red-100 text-red-800 
                                        @elseif($documentApprovalRequest->priority === 'medium') bg-yellow-100 text-yellow-800 
                                        @else bg-green-100 text-green-800 @endif">
                                        {{ ucfirst($documentApprovalRequest->priority) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Submitted</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $documentApprovalRequest->submitted_at ? $documentApprovalRequest->submitted_at->format('F j, Y g:i A') : 'Not submitted' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Deadline</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $documentApprovalRequest->deadline ? $documentApprovalRequest->deadline->format('F j, Y') : 'No deadline' }}</dd>
                            </div>
                        </div>
                        
                        @if($documentApprovalRequest->description)
                            <div class="mt-6">
                                <dt class="text-sm font-medium text-gray-500">Description</dt>
                                <dd class="mt-2 text-sm text-gray-900 whitespace-pre-wrap">{{ $documentApprovalRequest->description }}</dd>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Attachments -->
                @if($documentApprovalRequest->attachments->count() > 0)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Attachments</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @foreach($documentApprovalRequest->attachments as $attachment)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $attachment->file_name }}</p>
                                                <p class="text-xs text-gray-500">{{ number_format($attachment->file_size / 1024, 1) }} KB</p>
                                            </div>
                                        </div>
                                        @can('view', $documentApprovalRequest)
                                            <a href="{{ route('document-approvals.download', [$documentApprovalRequest, $attachment]) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-md hover:bg-blue-200 transition-colors duration-150">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Download
                                            </a>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Comments -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Comments</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse($documentApprovalRequest->comments as $comment)
                            <div class="p-6">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-700">{{ substr($comment->user->name, 0, 2) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-gray-900">{{ $comment->user->name }}</h4>
                                            <div class="flex items-center space-x-2">
                                                @if($comment->is_internal)
                                                    <span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded-full">Internal</span>
                                                @endif
                                                <time class="text-xs text-gray-500">{{ $comment->created_at->format('M j, Y g:i A') }}</time>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-700 whitespace-pre-wrap">{{ $comment->comment }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-gray-500">
                                No comments yet.
                            </div>
                        @endforelse

                        <!-- Add Comment Form -->
                        @can('comment', $documentApprovalRequest)
                            <div class="p-6 bg-gray-50">
                                <form action="{{ route('document-approvals.add-comment', $documentApprovalRequest) }}" method="POST">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="comment" value="Add Comment" />
                                            <textarea id="comment" name="comment" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your comment..."></textarea>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="is_internal" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                <span class="ml-2 text-sm text-gray-600">Internal comment (not visible to employee)</span>
                                            </label>
                                            <x-primary-button type="submit">Add Comment</x-primary-button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Approval Actions -->
                @include('document-approvals.partials.approval-actions')

                <!-- Approval Progress -->
                @if($documentApprovalRequest->workflow)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Approval Progress</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                @foreach($documentApprovalRequest->workflow->steps as $step)
                                    @php
                                        $approvalStep = $documentApprovalRequest->approvalSteps->where('step_order', $step->step_order)->first();
                                        $isCurrent = $currentStep && $currentStep->step_order === $step->step_order;
                                        $isCompleted = $approvalStep && $approvalStep->status === 'approved';
                                        $isRejected = $approvalStep && $approvalStep->status === 'rejected';
                                    @endphp
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            @if($isCompleted)
                                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            @elseif($isRejected)
                                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            @elseif($isCurrent)
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                                </div>
                                            @else
                                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                    <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $step->name }}</p>
                                            <p class="text-xs text-gray-500">
                                                @if($approvalStep)
                                                    {{ $approvalStep->user->name ?? 'Unassigned' }}
                                                    @if($approvalStep->approved_at)
                                                        • {{ $approvalStep->approved_at->format('M j, Y') }}
                                                    @endif
                                                @else
                                                    Pending
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>