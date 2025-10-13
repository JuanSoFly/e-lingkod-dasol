@php
$includeJS = $includeJS ?? true;
$includeStyles = $includeStyles ?? true;
@endphp

@if($includeStyles)
<!-- CSC Validation Styles -->
<style>
.validation-feedback {
    font-size: 0.875rem;
    line-height: 1.25rem;
    margin-top: 0.25rem;
}

.border-green-500 {
    border-color: rgb(34 197 94) !important;
}

.border-red-500 {
    border-color: rgb(239 68 68) !important;
}

.border-yellow-500 {
    border-color: rgb(234 179 8) !important;
}

.csc-compliance-indicator {
    transition: all 0.3s ease;
}

.csc-field-valid {
    position: relative;
}

.csc-field-valid::after {
    content: '✓';
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgb(34 197 94);
    font-weight: bold;
}

.csc-field-invalid::after {
    content: '✗';
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgb(239 68 68);
    font-weight: bold;
}

.required-indicator {
    color: rgb(239 68 68);
    font-weight: bold;
    margin-left: 0.25rem;
}

.csc-help-text {
    font-size: 0.75rem;
    color: rgb(107 114 128);
    margin-top: 0.25rem;
}

.csc-validation-summary {
    background: linear-gradient(135deg, rgb(249 250 251) 0%, rgb(243 244 246) 100%);
    border: 1px solid rgb(229 231 235);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.csc-compliance-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.csc-compliance-badge.compliant {
    background-color: rgb(34 197 94);
    color: white;
}

.csc-compliance-badge.partial {
    background-color: rgb(234 179 8);
    color: black;
}

.csc-compliance-badge.non-compliant {
    background-color: rgb(239 68 68);
    color: white;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.notification-enter {
    animation: slideInRight 0.3s ease-out;
}
</style>
@endif

@if($includeJS)
<script src="{{ asset('js/csc-validation.js') }}" defer></script>

<script>
// Initialize CSC Validation when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Custom initialization for specific pages
    if (window.location.pathname.includes('/pds/') || window.location.pathname.includes('/questionnaire')) {
        initializeCSCValidation();
    }
});

function initializeCSCValidation() {
    // Add data attributes to existing fields
    enhanceFormFields();

    // Setup real-time validation summary
    setupValidationSummary();

    // Setup auto-save for valid data
    setupAutoSave();
}

function enhanceFormFields() {
    // Enhance government ID fields
    const govIdFields = ['sss_number', 'gsis_number', 'philhealth_number', 'pagibig_number', 'tin_number'];
    govIdFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field && !field.hasAttribute('data-csc-validate')) {
            field.setAttribute('data-csc-validate', 'government_id');
            field.setAttribute('data-id-type', fieldId.replace('_number', ''));
            field.classList.add('csc-field');
        }
    });

    // Enhance date fields
    const dateFields = document.querySelectorAll('input[type="date"], input[name*="date"], input[name*="birth_date"]');
    dateFields.forEach(field => {
        if (!field.hasAttribute('data-csc-validate')) {
            field.setAttribute('data-csc-validate', 'date');
            field.setAttribute('data-format', 'date');
            field.classList.add('csc-field');
        }
    });

    // Enhance telephone fields
    const phoneFields = document.querySelectorAll('input[name*="telephone"], input[name*="phone"], input[name*="mobile"]');
    phoneFields.forEach(field => {
        if (!field.hasAttribute('data-csc-validate')) {
            field.setAttribute('data-csc-validate', 'telephone');
            field.classList.add('csc-field');
        }
    });

    // Enhance salary grade fields
    const salaryFields = document.querySelectorAll('input[name*="salary_grade"], input[name*="step"]');
    salaryFields.forEach(field => {
        if (!field.hasAttribute('data-csc-validate')) {
            field.setAttribute('data-csc-validate', 'salary_grade');
            field.setAttribute('data-format', 'salary-grade');
            field.classList.add('csc-field');
        }
    });

    // Enhance CSC questionnaire fields
    const cscFields = [
        'field_34_relationship',
        'field_35_charges',
        'field_36_candidate',
        'field_37_resignation',
        'field_38_immigrant',
        'field_39_yes_no',
        'field_41_indigenous_member',
        'field_41_pwd_member',
        'field_41_solo_parent_member'
    ];

    cscFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('csc-field');
        }
    });

    // Setup conditional validation for radio buttons
    const conditionalPairs = [
        { radio: 'q34_related', field: 'field_34_relationship' },
        { radio: 'q35_charges', field: 'field_35_charges' },
        { radio: 'q36_candidate', field: 'field_36_candidate' },
        { radio: 'q37_resignation', field: 'field_37_resignation' },
        { radio: 'q38_immigrant', field: 'field_38_immigrant' }
    ];

    conditionalPairs.forEach(({ radio, field }) => {
        const radioButtons = document.querySelectorAll(`input[name="${radio}"]`);
        const targetField = document.getElementById(field);

        if (radioButtons.length > 0 && targetField) {
            radioButtons.forEach(radio => {
                radio.setAttribute('data-csc-conditional', `#${field}`);
            });

            // Add required indicator
            const formGroup = targetField.closest('.form-group, .mb-4, .form-group');
            if (formGroup) {
                const label = formGroup.querySelector('label');
                if (label) {
                    const indicator = document.createElement('span');
                    indicator.className = 'required-indicator';
                    indicator.textContent = '*';
                    indicator.style.display = 'none';
                    label.appendChild(indicator);
                }
            }
        }
    });
}

function setupValidationSummary() {
    // Create validation summary container if it doesn't exist
    let summaryContainer = document.getElementById('csc-validation-summary');

    if (!summaryContainer) {
        summaryContainer = document.createElement('div');
        summaryContainer.id = 'csc-validation-summary';
        summaryContainer.className = 'csc-validation-summary';

        const form = document.querySelector('form');
        if (form) {
            form.insertBefore(summaryContainer, form.firstChild);
        }
    }

    // Update summary when fields change
    const cscFields = document.querySelectorAll('.csc-field');
    cscFields.forEach(field => {
        field.addEventListener('blur', updateValidationSummary);
        field.addEventListener('input', updateValidationSummary);
    });

    // Initial update
    updateValidationSummary();
}

function updateValidationSummary() {
    const summaryContainer = document.getElementById('csc-validation-summary');
    if (!summaryContainer || !window.CSCValidation) return;

    const complianceStatus = window.CSCValidation.getCSCComplianceStatus();

    summaryContainer.innerHTML = `
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">CSC Form No. 212 Compliance Status</h3>
            <span class="csc-compliance-badge ${getComplianceClass(complianceStatus.completeness_percentage)}">
                ${complianceStatus.completeness_percentage}% Complete
            </span>
        </div>

        <div class="mb-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-300"
                     style="width: ${complianceStatus.completeness_percentage}%"></div>
            </div>
        </div>

        ${complianceStatus.missing_fields.length > 0 ? `
            <div class="text-sm text-red-600">
                <p class="font-semibold mb-2">Missing Required Fields:</p>
                <ul class="list-disc list-inside space-y-1">
                    ${complianceStatus.missing_fields.map(field =>
                        `<li>${formatFieldName(field)}</li>`
                    ).join('')}
                </ul>
            </div>
        ` : `
            <div class="text-sm text-green-600">
                <p class="font-semibold">✅ All required CSC fields are complete!</p>
            </div>
        `}

        <div class="mt-4 text-xs text-gray-500">
            <p>CSC Form No. 212 compliance ensures your Personal Data Sheet meets Civil Service Commission requirements.</p>
        </div>
    `;
}

function getComplianceClass(percentage) {
    if (percentage === 100) return 'compliant';
    if (percentage >= 70) return 'partial';
    return 'non-compliant';
}

function formatFieldName(fieldId) {
    const fieldNames = {
        'field_34_relationship': 'Relationship to appointing authority',
        'field_35_charges': 'Administrative/criminal charges details',
        'field_36_candidate': 'Candidacy details',
        'field_37_resignation': 'Resignation to campaign details',
        'field_38_immigrant': 'Immigrant status details',
        'field_39_yes_no': 'Immigrant status (Field 39)',
        'field_41_indigenous_member': 'Indigenous group membership',
        'field_41_pwd_member': 'PWD membership',
        'field_41_solo_parent_member': 'Solo parent membership'
    };

    return fieldNames[fieldId] || fieldId.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function setupAutoSave() {
    // Auto-save valid data to prevent data loss
    const cscFields = document.querySelectorAll('.csc-field');

    cscFields.forEach(field => {
        let saveTimeout;

        field.addEventListener('blur', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                if (field.value && field.value.trim() !== '') {
                    // Check if field is valid before saving
                    const isValid = field.classList.contains('border-green-500') ||
                                  !field.classList.contains('border-red-500');

                    if (isValid) {
                        // Auto-save logic could be implemented here
                        console.log('Auto-saving valid field:', field.id || field.name, field.value);
                    }
                }
            }, 2000); // 2 second delay
        });
    });
}

// Helper function to validate form before submission
function validateCSCFormBeforeSubmit(formSelector) {
    if (!window.CSCValidation) return true;

    const isValid = window.CSCValidation.validateForm(formSelector);
    const complianceStatus = window.CSCValidation.getCSCComplianceStatus();

    if (!isValid) {
        showNotification('Please fix validation errors before submitting', 'error');
        return false;
    }

    if (complianceStatus.completeness_percentage < 100) {
        showNotification('Form is not fully CSC compliant. Missing required fields.', 'warning');
        return false;
    }

    return true;
}

// Helper function to show CSC compliance notifications
function showCSCNotification(message, type = 'info', duration = 5000) {
    if (!window.CSCValidation) return;

    window.CSCValidation.showNotification(message, type, duration);
}

// Make functions available globally
window.validateCSCFormBeforeSubmit = validateCSCFormBeforeSubmit;
window.showCSCNotification = showCSCNotification;
window.updateValidationSummary = updateValidationSummary;
</script>
@endif

<!-- CSC Validation Component -->
<div class="csc-validation-container" data-csc-enabled="true">
    @if(isset($title))
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-gray-800">{{ $title }}</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $description ?? 'CSC Form No. 212 compliance validation' }}</p>
        </div>
    @endif

    {{ $slot }}
</div>