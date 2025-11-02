/**
 * Archive Management JavaScript Module
 * Handles employee restoration and permanent deletion functionality
 */

window.ArchiveManager = (function() {
    'use strict';

    /**
     * Restore an archived employee
     * @param {number} employeeId - The employee ID
     * @param {string} employeeName - The employee's full name
     * @param {string} context - The context: 'index', 'detail', or 'auto'
     * @param {function} onSuccess - Optional callback for success
     * @param {function} onError - Optional callback for errors
     */
    function restoreEmployee(employeeId, employeeName, context, onSuccess, onError) {
        // Handle backward compatibility
        if (typeof context === 'function') {
            // Old signature: restoreEmployee(id, name, onSuccess, onError)
            onSuccess = context;
            onError = onSuccess;
            context = 'auto';
        } else if (!context) {
            context = 'auto';
        }

        if (!confirm(`Are you sure you want to restore ${employeeName} to active status?`)) {
            return;
        }

        // Show loading state
        showLoadingState(employeeId, 'restore');

        fetch(`/employees/archive/${employeeId}/restore`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                } else {
                    // Smart redirect based on context and server response
                    handleSmartRedirect(data, employeeId, context);
                }
            } else {
                hideLoadingState(employeeId, 'restore');
                const errorMsg = data.message || 'Failed to restore employee.';
                if (typeof onError === 'function') {
                    onError(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        })
        .catch(error => {
            hideLoadingState(employeeId, 'restore');
            console.error('Restore Error:', error);
            const errorMsg = 'An error occurred while restoring the employee.';
            if (typeof onError === 'function') {
                onError(errorMsg);
            } else {
                alert(errorMsg);
            }
        });
    }

    /**
     * Permanently delete an archived employee
     * @param {number} employeeId - The employee ID
     * @param {string} employeeName - The employee's full name
     * @param {function} onSuccess - Optional callback for success
     * @param {function} onError - Optional callback for errors
     */
    function forceDeleteEmployee(employeeId, employeeName, onSuccess, onError) {
        const confirmation1 = confirm(`⚠️ WARNING: This will permanently delete ${employeeName} and all their data. This action cannot be undone.\n\nDo you want to continue?`);

        if (!confirmation1) {
            return;
        }

        const nameConfirmation = prompt(`Type the employee's name exactly to confirm permanent deletion:\n\n"${employeeName}"`);

        if (nameConfirmation !== employeeName) {
            alert('Employee name confirmation does not match. Deletion cancelled.');
            return;
        }

        const finalConfirmation = confirm(`🚨 FINAL WARNING: This is your last chance to cancel.\n\nDeleting: ${employeeName}\nEmployee ID: ${employeeId}\n\nThis action cannot be undone.\n\nAre you absolutely sure?`);

        if (!finalConfirmation) {
            return;
        }

        // Show loading state
        showLoadingState(employeeId, 'delete');

        fetch(`/employees/archive/${employeeId}/force-delete`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                confirmation: 'PERMANENTLY_DELETE_' + employeeId,
                employee_name: employeeName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                } else {
                    // Default behavior: reload page or redirect
                    location.reload();
                }
            } else {
                hideLoadingState(employeeId, 'delete');
                const errorMsg = data.message || 'Failed to permanently delete employee.';
                if (typeof onError === 'function') {
                    onError(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        })
        .catch(error => {
            hideLoadingState(employeeId, 'delete');
            console.error('Delete Error:', error);
            const errorMsg = 'An error occurred while permanently deleting the employee.';
            if (typeof onError === 'function') {
                onError(errorMsg);
            } else {
                alert(errorMsg);
            }
        });
    }

    /**
     * Show loading state for a button
     * @param {number} employeeId - The employee ID
     * @param {string} action - The action type ('restore' or 'delete')
     */
    function showLoadingState(employeeId, action) {
        const button = document.querySelector(`[data-employee-id="${employeeId}"][data-action="${action}"]`);
        if (button) {
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');

            // Find the text container (button text or .button-text span)
            const textContainer = button.querySelector('.button-text') || button;

            // Store original text
            const originalText = textContainer.innerText;
            button.setAttribute('data-original-text', originalText);

            // Show loading text
            if (action === 'restore') {
                textContainer.innerText = 'Restoring...';
            } else if (action === 'delete') {
                textContainer.innerText = 'Deleting...';
            }
        }
    }

    /**
     * Hide loading state for a button
     * @param {number} employeeId - The employee ID
     * @param {string} action - The action type ('restore' or 'delete')
     */
    function hideLoadingState(employeeId, action) {
        const button = document.querySelector(`[data-employee-id="${employeeId}"][data-action="${action}"]`);
        if (button) {
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');

            // Find the text container (button text or .button-text span)
            const textContainer = button.querySelector('.button-text') || button;

            // Restore original text
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                textContainer.innerText = originalText;
                button.removeAttribute('data-original-text');
            }
        }
    }

    /**
     * Handle smart redirect based on context and server response
     * @param {Object} data - The server response data
     * @param {number} employeeId - The employee ID
     * @param {string} context - The context: 'index', 'detail', or 'auto'
     */
    function handleSmartRedirect(data, employeeId, context) {
        // Prefer server-provided redirect URL if available
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
            return;
        }

        // Auto-detect context based on current URL
        if (context === 'auto') {
            const currentPath = window.location.pathname;
            if (currentPath.includes('/employees/archive/') && currentPath.match(/\/\d+$/)) {
                context = 'detail';
            } else if (currentPath.includes('/employees/archive')) {
                context = 'index';
            } else {
                context = 'index'; // Default fallback
            }
        }

        // Handle different contexts
        switch (context) {
            case 'detail':
                // From detail page: redirect to employee's active page
                window.location.href = `/employees/${employeeId}`;
                break;
            case 'index':
                // From index page: reload archive list
                location.reload();
                break;
            default:
                // Default fallback: go to employee list
                window.location.href = '/employees';
                break;
        }
    }

    /**
     * Initialize archive functionality on page load
     */
    function initialize() {
        // Add data attributes to existing buttons for better targeting
        const restoreButtons = document.querySelectorAll('button[onclick*="restoreEmployee"]');
        const deleteButtons = document.querySelectorAll('button[onclick*="forceDeleteEmployee"]');

        restoreButtons.forEach(button => {
            const onclick = button.getAttribute('onclick');
            const matches = onclick.match(/restoreEmployee\((\d+),\s*['"]([^'"]+)['"]\)/);
            if (matches) {
                const employeeId = matches[1];
                button.setAttribute('data-employee-id', employeeId);
                button.setAttribute('data-action', 'restore');
            }
        });

        deleteButtons.forEach(button => {
            const onclick = button.getAttribute('onclick');
            const matches = onclick.match(/forceDeleteEmployee\((\d+),\s*['"]([^'"]+)['"]\)/);
            if (matches) {
                const employeeId = matches[1];
                button.setAttribute('data-employee-id', employeeId);
                button.setAttribute('data-action', 'delete');
            }
        });
    }

    // Public API
    return {
        restoreEmployee: restoreEmployee,
        forceDeleteEmployee: forceDeleteEmployee,
        showLoadingState: showLoadingState,
        hideLoadingState: hideLoadingState,
        initialize: initialize
    };
})();

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.ArchiveManager.initialize();
});

// Make functions available globally for backward compatibility
window.restoreEmployee = window.ArchiveManager.restoreEmployee;
window.forceDeleteEmployee = window.ArchiveManager.forceDeleteEmployee;