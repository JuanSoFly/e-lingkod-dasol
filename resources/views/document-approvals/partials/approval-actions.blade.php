@can('approve', $documentApprovalRequest)
    @if($documentApprovalRequest->status === 'submitted' || $documentApprovalRequest->status === 'under_review')
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Take Action</h3>
            </div>
            <div class="p-6">
                <form action="{{ route('document-approvals.approve', $documentApprovalRequest) }}" method="POST" id="approval-form" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action" id="action-input">
                    
                    <div>
                        <x-input-label for="comments" value="Comments (Optional)" />
                        <textarea id="comments" name="comments" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Add comments about your decision..."></textarea>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_internal_comment" value="1" id="is_internal_comment" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="is_internal_comment" class="ml-2 text-sm text-gray-600">Internal comment (not visible to employee)</label>
                    </div>
                    
                    <div class="flex flex-col space-y-3">
                        <x-success-button type="button" onclick="submitAction('approve')" class="w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Approve Request
                        </x-success-button>
                        
                        <x-secondary-button type="button" onclick="submitAction('request_changes')" class="w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Request Changes
                        </x-secondary-button>
                        
                        <x-danger-button type="button" onclick="submitAction('reject')" class="w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Reject Request
                        </x-danger-button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function submitAction(action) {
                const form = document.getElementById('approval-form');
                const actionInput = document.getElementById('action-input');
                
                actionInput.value = action;
                
                if (action === 'approve') {
                    form.action = '{{ route("document-approvals.approve", $documentApprovalRequest) }}';
                } else if (action === 'reject') {
                    form.action = '{{ route("document-approvals.reject", $documentApprovalRequest) }}';
                } else if (action === 'request_changes') {
                    form.action = '{{ route("document-approvals.request-changes", $documentApprovalRequest) }}';
                }
                
                form.submit();
            }
        </script>
    @endif
@endcan

@can('submit', $documentApprovalRequest)
    @if($documentApprovalRequest->status === 'draft')
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Submit Request</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Ready to submit your request for approval?</p>
                <form action="{{ route('document-approvals.submit', $documentApprovalRequest) }}" method="POST">
                    @csrf
                    <x-primary-button type="submit" class="w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Submit Request
                    </x-primary-button>
                </form>
            </div>
        </div>
    @endif
@endcan

@can('manage', $documentApprovalRequest)
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Admin Actions</h3>
        </div>
        <div class="p-6 space-y-3">
            @if($documentApprovalRequest->status === 'under_review' && $currentStep)
                <x-secondary-button type="button" onclick="document.getElementById('reassign-modal').classList.remove('hidden')" class="w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    Reassign Approver
                </x-secondary-button>
            @endif
            
            @if($currentStep && $currentStep->deadline && $currentStep->deadline->isPast())
                <x-secondary-button type="button" onclick="document.getElementById('escalate-modal').classList.remove('hidden')" class="w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    Escalate (Overdue)
                </x-secondary-button>
            @endif
        </div>
    </div>
@endcan