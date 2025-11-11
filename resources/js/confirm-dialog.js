const registerConfirmInterceptors = () => {
    const handleSubmit = (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = form.dataset.confirm;
        if (!message || form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();
        window.dispatchEvent(new CustomEvent('confirm-dialog:open', {
            detail: { message, form },
        }));
    };

    document.addEventListener('submit', handleSubmit, true);
};

const registerConfirmHelper = () => {
    if (window.confirmDialog) {
        return;
    }

    window.confirmDialog = (options = {}) => {
        const {
            message = '',
            title,
            confirmLabel,
            cancelLabel,
            icon,
        } = options;

        return new Promise((resolve) => {
            window.dispatchEvent(new CustomEvent('confirm-dialog:open', {
                detail: {
                    message,
                    title,
                    confirmLabel,
                    cancelLabel,
                    icon,
                    resolve,
                },
            }));
        });
    };
};

const boot = () => {
    registerConfirmInterceptors();
    registerConfirmHelper();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
