@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div>
                    <nav class="flex mb-3" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 text-sm text-gray-500">
                            <li>
                                <a href="{{ route('employee-portal.dashboard') }}" class="hover:text-blue-600 transition-colors duration-200">
                                    <i class="fas fa-home"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-chevron-right mx-2 text-gray-400"></i>
                                <a href="{{ route('employee-portal.personal-data-update') }}" class="hover:text-blue-600 transition-colors duration-200">
                                    Personal Data
                                </a>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-chevron-right mx-2 text-gray-400"></i>
                                <span class="text-gray-900 font-medium">Request Change</span>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="text-3xl font-bold text-gray-900">Request Personal Data Change</h1>
                    <p class="text-lg text-gray-600 mt-1">Submit a request to update your personal information</p>
                </div>
                <div>
                    <a href="{{ route('employee-portal.personal-data-update') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Personal Data
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-user-edit text-blue-600 mr-3"></i>
                    Personal Data Change Request Form
                </h2>
                <p class="text-sm text-gray-600 mt-1">Provide justification and supporting documents for verification</p>
            </div>

            <form action="{{ route('employee-portal.change-requests.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                
                <!-- Change Category & Field -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="change_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Change Category *
                        </label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('change_type') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                id="change_type" name="change_type" required>
                            <option value="">Select category...</option>
                            @foreach($changeTypes as $key => $value)
                                <option value="{{ $key }}" {{ old('change_type') === $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                        @error('change_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="field_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Field to Change *
                        </label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('field_name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                id="field_name" name="field_name" required>
                            <option value="">Select field...</option>
                        </select>
                        @error('field_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Current & New Values -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="current_value_display" class="block text-sm font-medium text-gray-700 mb-2">
                            Current Value
                        </label>
                        <input type="text" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-500 cursor-not-allowed" 
                               id="current_value_display" 
                               readonly
                               placeholder="Current value will appear here">
                    </div>

                    <div>
                        <label for="requested_value" class="block text-sm font-medium text-gray-700 mb-2">
                            New Value *
                        </label>
                        <input type="text" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('requested_value') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                               id="requested_value" name="requested_value" 
                               value="{{ old('requested_value') }}"
                               placeholder="Enter the new value"
                               required>
                        @error('requested_value')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Justification -->
                <div>
                    <label for="justification" class="block text-sm font-medium text-gray-700 mb-2">
                        Justification for Change *
                    </label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('justification') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                              id="justification" name="justification" rows="3" 
                              placeholder="Please explain why this change is needed and provide any relevant context..."
                              required>{{ old('justification') }}</textarea>
                    @error('justification')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Priority & Effective Date -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                            Priority Level *
                        </label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('priority') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                id="priority" name="priority" required>
                            <option value="normal" {{ old('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        </select>
                        @error('priority')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="effective_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Effective Date
                            <span class="text-gray-500 font-normal">(Optional)</span>
                        </label>
                        <input type="date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('effective_date') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                               id="effective_date" name="effective_date" 
                               value="{{ old('effective_date') }}" 
                               min="{{ now()->format('Y-m-d') }}">
                        @error('effective_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Supporting Documents -->
                <div>
                    <label for="supporting_documents" class="block text-sm font-medium text-gray-700 mb-2">
                        Supporting Documents
                        <span class="text-gray-500 font-normal">(Optional but recommended)</span>
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors duration-200">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="supporting_documents" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Upload files</span>
                                    <input id="supporting_documents" name="supporting_documents[]" type="file" class="sr-only" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">
                                PDF, DOC, JPG, PNG up to 5MB each
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <p><strong>Recommended documents:</strong> Birth certificate, marriage certificate, diploma, government ID, medical records, or other relevant supporting documents.</p>
                    </div>
                    @error('supporting_documents.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Document Notes -->
                <div>
                    <label for="document_notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Document Notes
                        <span class="text-gray-500 font-normal">(Optional)</span>
                    </label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('document_notes') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                              id="document_notes" name="document_notes" rows="2" 
                              placeholder="Additional notes about the supporting documents...">{{ old('document_notes') }}</textarea>
                    @error('document_notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info Notice -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-amber-400"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-amber-800">Important Notice</h4>
                            <div class="mt-2 text-sm text-amber-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Changes to critical information (names, birth dates, government IDs) require approval from HR</li>
                                    <li>Supporting documents are strongly recommended for verification purposes</li>
                                    <li>Some changes may require additional verification steps or may not be auto-implementable</li>
                                    <li>You will receive notifications about the status of your request</li>
                                    <li>False information may result in disciplinary action</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
                    <button type="submit" 
                            class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Change Request
                    </button>
                    <a href="{{ route('employee-portal.personal-data-update') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const changeTypeSelect = document.getElementById('change_type');
    const fieldNameSelect = document.getElementById('field_name');
    const currentValueDisplay = document.getElementById('current_value_display');
    
    // Field options for each change type
    const editableFields = @json($editableFields);
    
    // Current employee data
    const employeeData = @json($employee->toArray());
    
    changeTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        fieldNameSelect.innerHTML = '<option value="">Select field...</option>';
        currentValueDisplay.value = '';
        
        if (selectedType && editableFields[selectedType]) {
            Object.entries(editableFields[selectedType]).forEach(([key, value]) => {
                const option = new Option(value, key);
                fieldNameSelect.add(option);
            });
        }
    });
    
    fieldNameSelect.addEventListener('change', function() {
        const selectedField = this.value;
        if (selectedField && employeeData[selectedField] !== undefined) {
            currentValueDisplay.value = employeeData[selectedField] || 'Not set';
        } else {
            currentValueDisplay.value = 'Not set';
        }
    });

    // File upload enhancements
    const fileInput = document.getElementById('supporting_documents');
    const fileDropZone = fileInput.closest('.border-dashed');
    
    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileDropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        fileDropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        fileDropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        fileDropZone.classList.add('border-blue-400', 'bg-blue-50');
    }

    function unhighlight(e) {
        fileDropZone.classList.remove('border-blue-400', 'bg-blue-50');
    }

    fileDropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        displaySelectedFiles(files);
    }

    fileInput.addEventListener('change', function() {
        displaySelectedFiles(this.files);
    });

    function displaySelectedFiles(files) {
        if (files.length > 0) {
            const fileList = Array.from(files).map(file => file.name).join(', ');
            const fileInfo = document.createElement('div');
            fileInfo.className = 'mt-2 text-sm text-gray-600';
            fileInfo.innerHTML = `<strong>Selected files:</strong> ${fileList}`;
            
            // Remove existing file info
            const existingInfo = fileDropZone.parentNode.querySelector('.file-info');
            if (existingInfo) {
                existingInfo.remove();
            }
            
            fileInfo.className += ' file-info';
            fileDropZone.parentNode.appendChild(fileInfo);
        }
    }

    // Form validation feedback
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                this.classList.remove('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
            } else {
                this.classList.remove('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                this.classList.add('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
/* Enhanced form styling */
.form-input:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Better mobile responsiveness */
@media (max-width: 640px) {
    .min-h-screen {
        min-height: calc(100vh - 4rem);
    }
}

/* File upload enhancements */
.border-dashed {
    transition: all 0.2s ease-in-out;
}

.border-dashed:hover {
    border-color: #9ca3af;
}

/* Loading state for submit button */
.btn-loading {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-loading::after {
    content: "";
    display: inline-block;
    width: 16px;
    height: 16px;
    margin-left: 8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Better focus states */
input:focus,
select:focus,
textarea:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Enhanced file drop zone */
.border-dashed.border-blue-400 {
    border-color: #60a5fa !important;
    background-color: #eff6ff !important;
}

/* Validation styling */
.border-red-300 {
    border-color: #fca5a5 !important;
}

.focus\:border-red-500:focus {
    border-color: #ef4444 !important;
}

.focus\:ring-red-500:focus {
    --tw-ring-color: rgba(239, 68, 68, 0.5) !important;
}
</style>
@endpush