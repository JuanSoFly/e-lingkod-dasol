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
                                <a href="{{ route('employee-portal.document-requests') }}" class="hover:text-blue-600 transition-colors duration-200">
                                    HR Document Services
                                </a>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-chevron-right mx-2 text-gray-400"></i>
                                <span class="text-gray-900 font-medium">New Request</span>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="text-3xl font-bold text-gray-900">Request HR Document</h1>
                    <p class="text-lg text-gray-600 mt-1">Request official documents and certificates</p>
                </div>
                <div>
                    <a href="{{ route('employee-portal.document-requests') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Requests
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
                    <i class="fas fa-file-plus text-blue-600 mr-3"></i>
                    HR Document Request Form
                </h2>
                <p class="text-sm text-gray-600 mt-1">Complete all required fields to submit your request</p>
            </div>

            <form action="{{ route('employee-portal.document-requests.store') }}" method="POST" class="p-6 space-y-6">
                @csrf
                
                <!-- Document Type & Name -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="document_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Document Type *
                        </label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('document_type') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                id="document_type" name="document_type" required>
                            <option value="">Select document type...</option>
                            @foreach($availableDocuments as $key => $value)
                                <option value="{{ $key }}" {{ old('document_type') === $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                        @error('document_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="document_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Document Name *
                        </label>
                        <input type="text" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('document_name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                               id="document_name" name="document_name" 
                               value="{{ old('document_name') }}" 
                               placeholder="e.g., Certificate of Employment"
                               required>
                        @error('document_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Purpose -->
                <div>
                    <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">
                        Purpose *
                    </label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('purpose') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                              id="purpose" name="purpose" rows="3" 
                              placeholder="Please specify the purpose for this document request..."
                              required>{{ old('purpose') }}</textarea>
                    @error('purpose')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Additional Details -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Additional Details
                        <span class="text-gray-500 font-normal">(Optional)</span>
                    </label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('description') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                              id="description" name="description" rows="2"
                              placeholder="Any additional details or special instructions...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Priority & Needed By -->
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
                        <label for="needed_by" class="block text-sm font-medium text-gray-700 mb-2">
                            Needed By
                            <span class="text-gray-500 font-normal">(Optional)</span>
                        </label>
                        <input type="date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('needed_by') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                               id="needed_by" name="needed_by" 
                               value="{{ old('needed_by') }}" 
                               min="{{ now()->addDay()->format('Y-m-d') }}">
                        @error('needed_by')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Delivery Method & Contact -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label for="delivery_method" class="block text-sm font-medium text-gray-700 mb-2">
                            Delivery Method *
                        </label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('delivery_method') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                id="delivery_method" name="delivery_method" required>
                            @foreach($deliveryMethods as $key => $value)
                                <option value="{{ $key }}" {{ old('delivery_method') === $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                        @error('delivery_method')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="delivery_contact" class="block text-sm font-medium text-gray-700 mb-2">
                            Contact Number *
                        </label>
                        <input type="text" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('delivery_contact') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                               id="delivery_contact" name="delivery_contact" 
                               value="{{ old('delivery_contact', $employee->contact_number) }}"
                               placeholder="Your contact number">
                        @error('delivery_contact')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Delivery Address (shown conditionally) -->
                <div id="delivery_address_group" class="hidden">
                    <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-2">
                        Delivery Address *
                    </label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('delivery_address') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                              id="delivery_address" name="delivery_address" rows="2"
                              placeholder="Complete delivery address">{{ old('delivery_address') }}</textarea>
                    @error('delivery_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info Notice -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800">Processing Information</h4>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Standard processing time is 3-5 business days</li>
                                    <li>High priority requests are processed within 1-2 business days</li>
                                    <li>Urgent requests require supervisor approval and may have additional fees</li>
                                    <li>You will receive notifications when your document is ready</li>
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
                        Submit Request
                    </button>
                    <a href="{{ route('employee-portal.document-requests') }}" 
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
    // Show/hide delivery address based on delivery method
    const deliveryMethodSelect = document.getElementById('delivery_method');
    const deliveryAddressGroup = document.getElementById('delivery_address_group');
    
    function toggleDeliveryAddress() {
        if (deliveryMethodSelect.value === 'courier') {
            deliveryAddressGroup.classList.remove('hidden');
            deliveryAddressGroup.classList.add('block');
        } else {
            deliveryAddressGroup.classList.add('hidden');
            deliveryAddressGroup.classList.remove('block');
        }
    }
    
    deliveryMethodSelect.addEventListener('change', toggleDeliveryAddress);
    toggleDeliveryAddress(); // Initial check
    
    // Auto-fill document name based on type
    const documentTypeSelect = document.getElementById('document_type');
    const documentNameInput = document.getElementById('document_name');
    
    documentTypeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value && !documentNameInput.value) {
            documentNameInput.value = selectedOption.text;
        }
    });

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

/* Enhanced file upload styling */
input[type="file"] {
    transition: all 0.2s ease-in-out;
}

input[type="file"]:hover {
    background-color: #f9fafb;
}

/* Better focus states */
input:focus,
select:focus,
textarea:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
@endpush