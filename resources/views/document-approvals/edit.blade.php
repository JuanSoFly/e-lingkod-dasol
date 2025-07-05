<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit Document Approval Request') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $documentApprovalRequest->reference_number }}</p>
            </div>
            <x-secondary-button onclick="window.location.href='{{ route('document-approvals.show', $documentApprovalRequest) }}'">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                </svg>
                Back to Request
            </x-secondary-button>
        </div>
    </x-slot>

    <div class="space-y-6">
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

        <form action="{{ route('document-approvals.update', $documentApprovalRequest) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Request Information</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="employee_id" value="Employee" />
                            <select id="employee_id" name="employee_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected(old('employee_id', $documentApprovalRequest->employee_id) == $employee->id)>
                                        {{ $employee->last_name }}, {{ $employee->first_name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="workflow_id" value="Approval Workflow" />
                            <select id="workflow_id" name="workflow_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">Select Workflow</option>
                                @foreach($workflows as $workflow)
                                    <option value="{{ $workflow->id }}" @selected(old('workflow_id', $documentApprovalRequest->workflow_id) == $workflow->id)>
                                        {{ $workflow->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('workflow_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="document_type" value="Document Type" />
                            <x-text-input id="document_type" name="document_type" type="text" value="{{ old('document_type', $documentApprovalRequest->document_type) }}" required autocomplete="document_type" placeholder="e.g. Certificate of Employment" />
                            <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="priority" value="Priority" />
                            <select id="priority" name="priority" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="low" @selected(old('priority', $documentApprovalRequest->priority) === 'low')>Low</option>
                                <option value="medium" @selected(old('priority', $documentApprovalRequest->priority) === 'medium')>Medium</option>
                                <option value="high" @selected(old('priority', $documentApprovalRequest->priority) === 'high')>High</option>
                            </select>
                            <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="purpose" value="Purpose (Optional)" />
                            <x-text-input id="purpose" name="purpose" type="text" value="{{ old('purpose', $documentApprovalRequest->purpose) }}" autocomplete="purpose" placeholder="e.g. Job application, visa processing" />
                            <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="deadline" value="Deadline (Optional)" />
                            <x-text-input id="deadline" name="deadline" type="date" value="{{ old('deadline', $documentApprovalRequest->deadline?->format('Y-m-d')) }}" />
                            <x-input-error :messages="$errors->get('deadline')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="Description" />
                        <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Provide additional details about the request...">{{ old('description', $documentApprovalRequest->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Existing Attachments -->
            @if($documentApprovalRequest->attachments->count() > 0)
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Current Attachments</h3>
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
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('document-approvals.download', [$documentApprovalRequest, $attachment]) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-md hover:bg-blue-200 transition-colors duration-150">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Download
                                        </a>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                                            <span class="ml-1 text-xs text-red-600">Remove</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- New Attachments -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Add New Attachments</h3>
                    <p class="text-sm text-gray-600 mt-1">Upload additional supporting documents (optional)</p>
                </div>
                <div class="p-6">
                    <div>
                        <x-input-label for="attachments" value="Files" />
                        <input id="attachments" name="attachments[]" type="file" multiple 
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt">
                        <p class="mt-2 text-xs text-gray-500">
                            Accepted formats: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, TXT. Maximum 10MB per file.
                        </p>
                        <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                        <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <x-secondary-button type="button" onclick="window.location.href='{{ route('document-approvals.show', $documentApprovalRequest) }}'">
                    Cancel
                </x-secondary-button>
                <x-primary-button type="submit">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Request
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>