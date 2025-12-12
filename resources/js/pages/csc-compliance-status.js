const getButton = (evt) => {
    if (evt?.currentTarget instanceof HTMLElement) {
        return evt.currentTarget;
    }
    if (evt?.target instanceof HTMLElement) {
        return evt.target;
    }
    return null;
};

const setButtonLoading = (button, html) => {
    if (!button) return () => {};
    const original = button.innerHTML;
    button.innerHTML = html;
    button.disabled = true;
    return () => {
        button.innerHTML = original;
        button.disabled = false;
    };
};

window.validateAndGeneratePDF = async (employeeId, evt) => {
    if (!employeeId) return;

    if (!window.CSCValidation) {
        window.location.href = `/pds/${employeeId}/generate-pdf`;
        return;
    }

    const complianceStatus = window.CSCValidation.getCSCComplianceStatus();

    if (!complianceStatus.is_valid) {
        window.showCSCNotification?.('Please fix validation errors before generating PDF', 'error');
        return;
    }

    if (complianceStatus.completeness_percentage < 100) {
        const message = 'Your CSC Form No. 212 is not fully compliant. Some required fields are missing. Continue anyway?';
        const confirmed = window.confirmDialog
            ? await window.confirmDialog({
                title: 'Proceed with Incomplete CSC Form',
                message,
                confirmLabel: 'Continue',
                cancelLabel: 'Review First',
            })
            : window.confirm(message);

        if (!confirmed) return;
    }

    const button = getButton(evt);
    const restore = setButtonLoading(
        button,
        '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating PDF...'
    );

    window.location.href = `/pds/${employeeId}/generate-pdf`;

    setTimeout(restore, 3000);
};

window.refreshCSCStatus = (employeeId, evt) => {
    if (!employeeId) return;

    const button = getButton(evt);
    const restore = setButtonLoading(
        button,
        '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Refreshing...'
    );

    fetch(`/pds/${employeeId}/validate-csc`)
        .then((response) => response.json())
        .then((data) => {
            if (data.is_csc_compliant) {
                window.showCSCNotification?.('CSC compliance status updated successfully!', 'success');
            } else {
                window.showCSCNotification?.('CSC compliance status updated. Please check missing fields.', 'warning');
            }

            setTimeout(() => window.location.reload(), 1000);
        })
        .catch((error) => {
            console.error('Error refreshing CSC status:', error);
            window.showCSCNotification?.('Error refreshing CSC status. Please try again.', 'error');
        })
        .finally(restore);
};

