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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registerConfirmInterceptors);
} else {
    registerConfirmInterceptors();
}
