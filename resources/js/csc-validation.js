/**
 * CSC Form No. 212 Validation - Frontend Real-time Validation
 * Provides immediate feedback for CSC compliance
 */

class CSCValidation {
    constructor() {
        this.rules = {
            // Date format validation (mm/dd/yyyy)
            date_format: (value) => {
                if (!value) return { valid: true, message: '' };

                const datePattern = /^(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])\/(19|20)\d{2}$/;
                if (!datePattern.test(value)) {
                    return {
                        valid: false,
                        message: 'Date must be in mm/dd/yyyy format (e.g., "12/25/2023")'
                    };
                }

                const date = new Date(value);
                const today = new Date();
                const minYear = 1900;
                const maxYear = today.getFullYear() + 1;

                if (date.getFullYear() < minYear || date.getFullYear() > maxYear) {
                    return {
                        valid: false,
                        message: `Date must be between ${minYear} and ${maxYear}`
                    };
                }

                if (date > today) {
                    return {
                        valid: false,
                        message: 'Date cannot be in the future'
                    };
                }

                return { valid: true, message: '' };
            },

            // Salary grade format validation (00-0)
            salary_grade: (value) => {
                if (!value) return { valid: true, message: '' };

                const salaryGradePattern = /^\d{2}-\d$/;
                if (!salaryGradePattern.test(value)) {
                    return {
                        valid: false,
                        message: 'Salary grade must follow CSC format "00-0" (e.g., "12-3", "01-0")'
                    };
                }

                const [grade, step] = value.split('-').map(Number);
                if (grade < 1 || grade > 33) {
                    return {
                        valid: false,
                        message: 'Salary grade must be between 01 and 33'
                    };
                }

                if (step < 0 || step > 8) {
                    return {
                        valid: false,
                        message: 'Salary step must be between 0 and 8'
                    };
                }

                return { valid: true, message: '' };
            },

            // Government ID validation
            government_id: (value, type = 'general') => {
                if (!value) return { valid: true, message: '' };

                const cleanValue = value.replace(/[\s\-\.\#]+/g, '').toUpperCase();

                switch (type) {
                    case 'sss':
                        if (!/^\d{10}$/.test(cleanValue)) {
                            return {
                                valid: false,
                                message: 'SSS number must be 10 digits (e.g., "1234567890")'
                            };
                        }
                        break;
                    case 'gsis':
                        if (!/^\d{12}$/.test(cleanValue)) {
                            return {
                                valid: false,
                                message: 'GSIS number must be 12 digits (e.g., "123456789012")'
                            };
                        }
                        break;
                    case 'philhealth':
                        if (!/^\d{12}$/.test(cleanValue)) {
                            return {
                                valid: false,
                                message: 'PhilHealth number must be 12 digits (e.g., "123456789012")'
                            };
                        }
                        break;
                    case 'pagibig':
                        if (!/^\d{12}$/.test(cleanValue)) {
                            return {
                                valid: false,
                                message: 'Pag-IBIG number must be 12 digits (e.g., "123456789012")'
                            };
                        }
                        break;
                    case 'tin':
                        if (!/^\d{9}$/.test(cleanValue) && !/^\d{12}$/.test(cleanValue)) {
                            return {
                                valid: false,
                                message: 'TIN number must be 9 or 12 digits (e.g., "123456789")'
                            };
                        }
                        break;
                    default:
                        if (cleanValue.length < 8 || cleanValue.length > 25) {
                            return {
                                valid: false,
                                message: 'Government ID must be between 8 and 25 characters'
                            };
                        }
                        if (!/^[A-Z0-9]+$/.test(cleanValue)) {
                            return {
                                valid: false,
                                message: 'Government ID must contain only letters and numbers'
                            };
                        }
                        break;
                }

                return { valid: true, message: '' };
            },

            // Telephone number validation
            telephone: (value) => {
                if (!value) return { valid: true, message: '' };

                const cleanNumber = value.replace(/[\s\-\(\)]+/g, '');

                // Philippine telephone number validation
                if (!/^(?:\d{7,11}|09\d{8,9})$/.test(cleanNumber)) {
                    return {
                        valid: false,
                        message: 'The telephone number must be a valid Philippine telephone number (e.g., "02-1234-5678" or "09123456789")'
                    };
                }

                // Check area codes for landlines
                if (cleanNumber.length >= 8 && !cleanNumber.startsWith('09')) {
                    const validAreaCodes = [
                        '02', '032', '033', '034', '035', '036', '041', '042', '043', '044',
                        '045', '046', '047', '048', '049', '052', '053', '054', '055', '056',
                        '061', '062', '063', '064', '065', '066', '067', '068', '069', '072',
                        '073', '074', '075', '076', '077', '078', '082', '083', '084', '085',
                        '086', '087', '088', '089'
                    ];

                    const areaCode = cleanNumber.length >= 9 ?
                        cleanNumber.substring(0, 3) : cleanNumber.substring(0, 2);

                    if (!validAreaCodes.includes(areaCode)) {
                        return {
                            valid: false,
                            message: 'The telephone number contains an invalid Philippine area code'
                        };
                    }
                }

                return { valid: true, message: '' };
            },

            // Required field validation for conditional fields
            conditional_required: (value, condition) => {
                if (condition && (!value || value.trim() === '')) {
                    return {
                        valid: false,
                        message: 'This field is required when the answer is YES'
                    };
                }
                return { valid: true, message: '' };
            }
        };

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupCSCQuestionnaireValidation();
        this.setupPersonalInfoValidation();
        this.setupWorkExperienceValidation();
        this.setupReferenceValidation();
    }

    setupEventListeners() {
        // Auto-format date inputs
        document.addEventListener('input', (e) => {
            if (e.target.matches('[data-format="date"]')) {
                this.autoFormatDate(e.target);
            }

            if (e.target.matches('[data-format="salary-grade"]')) {
                this.autoFormatSalaryGrade(e.target);
            }
        });

        // Real-time validation on blur
        document.addEventListener('blur', (e) => {
            if (e.target.matches('[data-csc-validate]')) {
                this.validateField(e.target);
            }
        }, true);

        // Conditional field validation
        document.addEventListener('change', (e) => {
            if (e.target.matches('[data-csc-conditional]')) {
                this.handleConditionalValidation(e.target);
            }
        });
    }

    setupCSCQuestionnaireValidation() {
        // Validate Fields 34-41 in real-time
        const conditionalFields = [
            { question: 'q34_related', detail: 'field_34_relationship' },
            { question: 'q35_charges', detail: 'field_35_charges' },
            { question: 'q36_candidate', detail: 'field_36_candidate' },
            { question: 'q37_resignation', detail: 'field_37_resignation' },
            { question: 'q38_immigrant', detail: 'field_38_immigrant' }
        ];

        conditionalFields.forEach(({ question, detail }) => {
            const questionRadio = document.querySelector(`input[name="${question}"]`);
            const detailField = document.getElementById(detail);

            if (questionRadio && detailField) {
                // Listen for radio button changes
                document.querySelectorAll(`input[name="${question}"]`).forEach(radio => {
                    radio.addEventListener('change', () => {
                        const isYes = radio.value === '1' || radio.checked && radio.value === '1';
                        this.validateConditionalField(detailField, isYes);
                    });
                });

                // Validate detail field on input
                detailField.addEventListener('input', () => {
                    const isYes = document.querySelector(`input[name="${question}"]:checked`)?.value === '1';
                    this.validateConditionalField(detailField, isYes);
                });
            }
        });

        }

    setupPersonalInfoValidation() {
        const govIdFields = [
            { id: 'sss_number', type: 'sss' },
            { id: 'gsis_number', type: 'gsis' },
            { id: 'philhealth_number', type: 'philhealth' },
            { id: 'pagibig_number', type: 'pagibig' },
            { id: 'tin_number', type: 'tin' }
        ];

        govIdFields.forEach(({ id, type }) => {
            const field = document.getElementById(id);
            if (field) {
                field.setAttribute('data-csc-validate', 'government_id');
                field.setAttribute('data-id-type', type);

                field.addEventListener('blur', () => {
                    const result = this.rules.government_id(field.value, type);
                    this.showValidationFeedback(field, result);
                });
            }
        });

        // Telephone number validation
        const phoneFields = ['telephone_no', 'mobile_no'];
        phoneFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.setAttribute('data-csc-validate', 'telephone');

                field.addEventListener('blur', () => {
                    const result = this.rules.telephone(field.value);
                    this.showValidationFeedback(field, result);
                });
            }
        });

        // Birth date validation
        const birthDateField = document.getElementById('birth_date');
        if (birthDateField) {
            birthDateField.setAttribute('data-csc-validate', 'date');
            birthDateField.setAttribute('data-format', 'date');

            birthDateField.addEventListener('blur', () => {
                const result = this.rules.date_format(birthDateField.value);
                this.showValidationFeedback(birthDateField, result);
            });
        }
    }

    setupWorkExperienceValidation() {
        // Dynamic work experience form validation
        document.addEventListener('DOMNodeInserted', (e) => {
            if (e.target.matches && e.target.matches('.work-experience-form')) {
                this.setupWorkExperienceFormValidation(e.target);
            }
        });

        // Setup existing forms
        document.querySelectorAll('.work-experience-form').forEach(form => {
            this.setupWorkExperienceFormValidation(form);
        });
    }

    setupWorkExperienceFormValidation(form) {
        const salaryGradeField = form.querySelector('input[name*="salary_grade"], input[name*="step"]');
        if (salaryGradeField) {
            salaryGradeField.setAttribute('data-csc-validate', 'salary_grade');
            salaryGradeField.setAttribute('data-format', 'salary-grade');

            salaryGradeField.addEventListener('blur', () => {
                const result = this.rules.salary_grade(salaryGradeField.value);
                this.showValidationFeedback(salaryGradeField, result);
            });
        }

        const dateFields = form.querySelectorAll('input[name*="date"]');
        dateFields.forEach(field => {
            field.setAttribute('data-csc-validate', 'date');
            field.setAttribute('data-format', 'date');

            field.addEventListener('blur', () => {
                const result = this.rules.date_format(field.value);
                this.showValidationFeedback(field, result);
            });
        });
    }

    setupReferenceValidation() {
        // Check reference count when adding new references
        const addReferenceBtn = document.querySelector('[data-action="add-reference"]');
        if (addReferenceBtn) {
            addReferenceBtn.addEventListener('click', () => {
                const currentCount = document.querySelectorAll('.reference-item').length;
                if (currentCount >= 3) {
                    this.showNotification('CSC Form No. 212 requires exactly 3 references', 'warning');
                    return false;
                }
            });
        }
    }

    validateField(field) {
        const validationType = field.getAttribute('data-csc-validate');
        if (!validationType || !this.rules[validationType]) return;

        let result = { valid: true, message: '' };

        switch (validationType) {
            case 'government_id':
                const idType = field.getAttribute('data-id-type') || 'general';
                result = this.rules.government_id(field.value, idType);
                break;
            case 'date':
                result = this.rules.date_format(field.value);
                break;
            case 'salary_grade':
                result = this.rules.salary_grade(field.value);
                break;
            case 'telephone':
                result = this.rules.telephone(field.value);
                break;
            default:
                if (this.rules[validationType]) {
                    result = this.rules[validationType](field.value);
                }
                break;
        }

        this.showValidationFeedback(field, result);
        return result.valid;
    }

    validateConditionalField(field, condition) {
        const result = this.rules.conditional_required(field.value, condition);
        this.showValidationFeedback(field, result);

        // Show/hide required indicator
        const requiredIndicator = field.closest('.form-group')?.querySelector('.required-indicator');
        if (requiredIndicator) {
            requiredIndicator.style.display = condition ? 'inline' : 'none';
        }

        return result.valid;
    }

  
    showValidationFeedback(field, result) {
        // Remove existing feedback
        const existingFeedback = field.parentNode.querySelector('.validation-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }

        // Update field styling
        field.classList.remove('border-red-500', 'border-green-500', 'border-yellow-500');

        if (!result.valid) {
            field.classList.add('border-red-500');
            this.createFeedbackElement(field, result.message, 'error');
        } else if (result.message && result.message !== '') {
            field.classList.add('border-yellow-500');
            this.createFeedbackElement(field, result.message, 'warning');
        } else if (field.value && field.value.trim() !== '') {
            field.classList.add('border-green-500');
        }
    }

    createFeedbackElement(field, message, type) {
        const feedback = document.createElement('div');
        feedback.className = `validation-feedback text-sm mt-1 ${type === 'error' ? 'text-red-600' : 'text-yellow-600'}`;
        feedback.textContent = message;

        field.parentNode.appendChild(feedback);
    }

    autoFormatDate(field) {
        let value = field.value.replace(/[^\d]/g, '');

        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2);
        }
        if (value.length >= 5) {
            value = value.substring(0, 5) + '/' + value.substring(5, 9);
        }

        field.value = value;
    }

    autoFormatSalaryGrade(field) {
        let value = field.value.replace(/[^\d-]/g, '');

        // Auto-add hyphen after 2 digits
        if (value.length >= 3 && !value.includes('-')) {
            value = value.substring(0, 2) + '-' + value.substring(2, 3);
        }

        // Limit to 5 characters (00-0)
        if (value.length > 5) {
            value = value.substring(0, 5);
        }

        field.value = value;
    }

    handleConditionalValidation(triggerField) {
        const targetSelector = triggerField.getAttribute('data-csc-conditional');
        const targetField = document.querySelector(targetSelector);

        if (!targetField) return;

        const condition = triggerField.type === 'checkbox' ?
            triggerField.checked :
            (triggerField.value === '1' || triggerField.value === 'yes');

        this.validateConditionalField(targetField, condition);
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm notification-enter ${
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-black' :
            type === 'success' ? 'bg-green-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Public method to validate entire form
    validateForm(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return true;

        let isValid = true;
        const fields = form.querySelectorAll('[data-csc-validate]');

        fields.forEach(field => {
            const fieldValid = this.validateField(field);
            isValid = isValid && fieldValid;
        });

        return isValid;
    }

    // Public method to get CSC compliance status
    getCSCComplianceStatus() {
        const requiredFields = [
            'field_34_relationship',
            'field_35_charges',
            'field_36_candidate',
            'field_37_resignation',
            'field_38_immigrant'
        ];

        let missingFields = [];
        let isValid = true;

        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && (!field.value || field.value.trim() === '')) {
                missingFields.push(fieldId);
                isValid = false;
            }
        });

        // Check conditional fields
        const conditionalChecks = [
            { question: 'q34_related', detail: 'field_34_relationship' },
            { question: 'q35_charges', detail: 'field_35_charges' },
            { question: 'q36_candidate', detail: 'field_36_candidate' },
            { question: 'q37_resignation', detail: 'field_37_resignation' },
            { question: 'q38_immigrant', detail: 'field_38_immigrant' }
        ];

        conditionalChecks.forEach(({ question, detail }) => {
            const questionField = document.querySelector(`input[name="${question}"]:checked`);
            const detailField = document.getElementById(detail);

            if (questionField && questionField.value === '1' && detailField) {
                if (!detailField.value || detailField.value.trim() === '') {
                    missingFields.push(detail);
                    isValid = false;
                }
            }
        });

        return {
            is_valid: isValid,
            missing_fields: missingFields,
            completeness_percentage: isValid ? 100 : Math.max(0, 100 - (missingFields.length * 10))
        };
    }
}

const shouldInitCSCValidation = () => {
    return Boolean(
        document.querySelector('[data-csc-enabled="true"]') ||
        window.location.pathname.includes('/pds/') ||
        window.location.pathname.includes('/questionnaire')
    );
};

const enhanceFormFields = () => {
    const govIdFields = ['sss_number', 'gsis_number', 'philhealth_number', 'pagibig_number', 'tin_number'];
    govIdFields.forEach((fieldId) => {
        const field = document.getElementById(fieldId);
        if (field && !field.hasAttribute('data-csc-validate')) {
            field.setAttribute('data-csc-validate', 'government_id');
            field.setAttribute('data-id-type', fieldId.replace('_number', ''));
            field.classList.add('csc-field');
        }
    });

    const dateFields = document.querySelectorAll('input[type="date"], input[name*="date"], input[name*="birth_date"]');
    dateFields.forEach((field) => {
        if (!field.hasAttribute('data-csc-validate')) {
            field.setAttribute('data-csc-validate', 'date');
            field.setAttribute('data-format', 'date');
            field.classList.add('csc-field');
        }
    });

    const phoneFields = document.querySelectorAll('input[name*="telephone"], input[name*="phone"], input[name*="mobile"]');
    phoneFields.forEach((field) => {
        if (!field.hasAttribute('data-csc-validate')) {
            field.setAttribute('data-csc-validate', 'telephone');
            field.classList.add('csc-field');
        }
    });

    const salaryFields = document.querySelectorAll('input[name*="salary_grade"], input[name*="step"]');
    salaryFields.forEach((field) => {
        if (!field.hasAttribute('data-csc-validate')) {
            field.setAttribute('data-csc-validate', 'salary_grade');
            field.setAttribute('data-format', 'salary-grade');
            field.classList.add('csc-field');
        }
    });

    const cscFields = [
        'field_34_relationship',
        'field_35_charges',
        'field_36_candidate',
        'field_37_resignation',
        'field_38_immigrant',
        'field_39_yes_no',
        'field_41_indigenous_member',
        'field_41_pwd_member',
        'field_41_solo_parent_member',
    ];

    cscFields.forEach((fieldId) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('csc-field');
        }
    });

    const conditionalPairs = [
        { radio: 'q34_related', field: 'field_34_relationship' },
        { radio: 'q35_charges', field: 'field_35_charges' },
        { radio: 'q36_candidate', field: 'field_36_candidate' },
        { radio: 'q37_resignation', field: 'field_37_resignation' },
        { radio: 'q38_immigrant', field: 'field_38_immigrant' },
    ];

    conditionalPairs.forEach(({ radio, field }) => {
        const radioButtons = document.querySelectorAll(`input[name="${radio}"]`);
        const targetField = document.getElementById(field);

        if (radioButtons.length > 0 && targetField) {
            radioButtons.forEach((radioButton) => {
                radioButton.setAttribute('data-csc-conditional', `#${field}`);
            });

            const formGroup = targetField.closest('.form-group, .mb-4, .form-group');
            if (formGroup) {
                const label = formGroup.querySelector('label');
                if (label && !label.querySelector('.required-indicator')) {
                    const indicator = document.createElement('span');
                    indicator.className = 'required-indicator';
                    indicator.textContent = '*';
                    indicator.style.display = 'none';
                    label.appendChild(indicator);
                }
            }
        }
    });
};

const getComplianceClass = (percentage) => {
    if (percentage === 100) return 'compliant';
    if (percentage >= 70) return 'partial';
    return 'non-compliant';
};

const formatFieldName = (fieldId) => {
    const fieldNames = {
        field_34_relationship: 'Relationship to appointing authority',
        field_35_charges: 'Administrative/criminal charges details',
        field_36_candidate: 'Candidacy details',
        field_37_resignation: 'Resignation to campaign details',
        field_38_immigrant: 'Immigrant status details',
        field_39_yes_no: 'Immigrant status (Field 39)',
        field_41_indigenous_member: 'Indigenous group membership',
        field_41_pwd_member: 'PWD membership',
        field_41_solo_parent_member: 'Solo parent membership',
    };

    return (
        fieldNames[fieldId] ||
        fieldId.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())
    );
};

const updateValidationSummary = () => {
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
};

const setupValidationSummary = () => {
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

    const cscFields = document.querySelectorAll('.csc-field');
    cscFields.forEach((field) => {
        field.addEventListener('blur', updateValidationSummary);
        field.addEventListener('input', updateValidationSummary);
    });

    updateValidationSummary();
};

const setupAutoSave = () => {
    const cscFields = document.querySelectorAll('.csc-field');

    cscFields.forEach((field) => {
        let saveTimeout;

        field.addEventListener('blur', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                if (field.value && field.value.trim() !== '') {
                    const isValid = field.classList.contains('border-green-500') ||
                        !field.classList.contains('border-red-500');

                    if (isValid) {
                        // Placeholder for future auto-save.
                    }
                }
            }, 2000);
        });
    });
};

const initializeCSCValidation = () => {
    enhanceFormFields();
    setupValidationSummary();
    setupAutoSave();
};

const validateCSCFormBeforeSubmit = (formSelector) => {
    if (!window.CSCValidation) return true;

    const isValid = window.CSCValidation.validateForm(formSelector);
    const complianceStatus = window.CSCValidation.getCSCComplianceStatus();

    if (!isValid) {
        window.CSCValidation.showNotification('Please fix validation errors before submitting', 'error');
        return false;
    }

    if (complianceStatus.completeness_percentage < 100) {
        window.CSCValidation.showNotification('Form is not fully CSC compliant. Missing required fields.', 'warning');
        return false;
    }

    return true;
};

const showCSCNotification = (message, type = 'info', duration = 5000) => {
    if (!window.CSCValidation) return;
    window.CSCValidation.showNotification(message, type, duration);
};

const bootCSCValidation = () => {
    if (!shouldInitCSCValidation()) {
        return;
    }

    if (!window.CSCValidation) {
        window.CSCValidation = new CSCValidation();
    }

    initializeCSCValidation();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCSCValidation);
} else {
    bootCSCValidation();
}

window.validateCSCFormBeforeSubmit = validateCSCFormBeforeSubmit;
window.showCSCNotification = showCSCNotification;
window.updateValidationSummary = updateValidationSummary;

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CSCValidation;
}
