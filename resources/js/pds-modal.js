document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('pds-modal');
    const backdrop = document.getElementById('pds-modal-backdrop');
    const panel = document.getElementById('pds-modal-panel');
    const closeBtn = document.getElementById('pds-modal-close');
    const titleEl = document.getElementById('pds-modal-title');
    const loader = document.getElementById('pds-modal-loader');
    const formContainer = document.getElementById('pds-modal-form-container');

    let currentModalUrl = '';
    let currentModalTitle = '';
    let shouldReloadPageOnClose = false;

    if (!modal) return;

    // Listen to PDS edit links
    document.querySelectorAll('.pds-edit-link').forEach(link => {
        link.addEventListener('click', function (e) {
            // Only intercept normal left clicks without modifier keys
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                return;
            }
            e.preventDefault();
            const url = this.getAttribute('href');
            const title = this.getAttribute('data-title') || 'Edit Details';
            openModal(url, title);
        });
    });

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    function openModal(url, title) {
        currentModalUrl = url;
        currentModalTitle = title;
        shouldReloadPageOnClose = false;
        titleEl.textContent = title;

        // Show Modal & run transitions
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
            panel.classList.remove('translate-y-4', 'opacity-0', 'lg:scale-95');
            panel.classList.add('translate-y-0', 'opacity-100', 'lg:scale-100');
        }, 10);

        loadModalContent(url);
    }

    async function loadModalContent(url) {
        loader.classList.remove('hidden');
        formContainer.classList.add('hidden');
        formContainer.innerHTML = '';

        try {
            // Append modal=1 query to request clean modal markup
            const fetchUrl = url + (url.includes('?') ? '&' : '?') + 'modal=1';
            const response = await fetch(fetchUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) throw new Error('Failed to load content.');

            const html = await response.text();
            formContainer.innerHTML = html;
            
            // Execute scripts inside injected HTML
            executeScripts(formContainer);

            // Hook up forms and controls
            setupFormInterceptors();

            loader.classList.add('hidden');
            formContainer.classList.remove('hidden');
        } catch (error) {
            formContainer.innerHTML = `<div class="text-center text-red-600 py-8">${error.message || 'An error occurred loading the form. Please try again.'}</div>`;
            loader.classList.add('hidden');
            formContainer.classList.remove('hidden');
        }
    }

    function setupFormInterceptors() {
        // Intercept all links inside the modal to prevent page navigation
        formContainer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                // Only intercept normal left clicks without modifier keys
                if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                    return;
                }

                const href = this.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
                    return;
                }

                // Skip target="_blank", download, and export/pdf routes
                if (this.getAttribute('target') === '_blank' || 
                    this.hasAttribute('download') || 
                    href.includes('/download') || 
                    href.includes('/export') || 
                    href.includes('/pdf')) {
                    return;
                }

                e.preventDefault();

                // If it is a "Back" button pointing to the dashboard (e.g. /employees/8 or /pds/8)
                const url = new URL(this.href, window.location.origin);
                const path = url.pathname;
                const isDashboard = /^\/(employees|pds)\/\d+\/?$/.test(path) || 
                                    /^\/employees\/?$/.test(path) || 
                                    /^\/admin\/announcements\/?$/.test(path) || 
                                    /^\/leave-types\/?$/.test(path) || 
                                    /^\/leave-card-view\/?$/.test(path) || 
                                    /^\/benefits\/?$/.test(path) || 
                                    /^\/performance-periods\/?$/.test(path) || 
                                    /^\/employee-portal\/leave-applications\/?$/.test(path);

                if (isDashboard) {
                    const normPath = path.replace(/\/$/, '');
                    const normLoc = window.location.pathname.replace(/\/$/, '');
                    if (normPath !== normLoc) {
                        window.location.href = this.href;
                    } else {
                        closeModal();
                    }
                } else {
                    // Load the content inside the modal
                    loadModalContent(href);
                }
            });
        });

        // Intercept submissions of forms inside the modal
        formContainer.querySelectorAll('form').forEach(form => {
            // Override the standard submit() method.
            // This ensures if a global confirm-dialog triggers a form.submit(),
            // it will route through our AJAX method instead of navigating.
            form.submit = function() {
                submitModalForm(form);
            };

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                // If this form triggers the global confirm-dialog, let confirm-dialog intercept it first
                if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
                    return; // confirm-dialog.js will handle this and call form.submit() once approved
                }
                submitModalForm(form);
            });
        });
    }

    async function submitModalForm(form) {
        const formData = new FormData(form);
        const method = form.querySelector('input[name="_method"]')?.value || form.method || 'POST';
        const action = form.action;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg> Saving...
            `;
        }

        clearErrors(form);

        try {
            const response = await fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    try {
                        const data = await response.json();
                        if (data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }
                    } catch (e) {
                        console.error('Error parsing JSON redirect:', e);
                    }
                }

                const isDelete = method.toUpperCase() === 'DELETE';
                const isMultiItem = action.includes('/eligibility') || 
                                    action.includes('/work-experience') || 
                                    action.includes('/voluntary-work') || 
                                    action.includes('/learning-development') || 
                                    action.includes('/other-information') || 
                                    action.includes('/education') || 
                                    action.includes('/references');

                if (isDelete || isMultiItem) {
                    // Refresh modal internally for lists and deletes
                    await loadModalContent(currentModalUrl);
                    shouldReloadPageOnClose = true;
                } else {
                    // Full page reload for single forms (Personal Info, Family Background, Questionnaire)
                    window.location.reload();
                }
            } else if (response.status === 422) {
                const data = await response.json();
                showValidationErrors(form, data.errors);
            } else {
                alert('An error occurred. Please verify your inputs and try again.');
            }
        } catch (error) {
            console.error(error);
            alert('A network error occurred. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        }
    }

    window.submitModalForm = submitModalForm;
    window.loadModalContent = loadModalContent;

    function clearErrors(form) {
        form.querySelectorAll('.pds-validation-error').forEach(el => el.remove());
        form.querySelectorAll('.border-red-500').forEach(input => {
            input.classList.remove('border-red-500', 'focus:ring-red-500');
            input.classList.add('border-gray-300', 'focus:ring-blue-500');
        });
    }

    function showValidationErrors(form, errors) {
        let firstErrorInput = null;

        for (const [key, messages] of Object.entries(errors)) {
            const input = findInputByErrorKey(form, key);
            if (input) {
                input.classList.remove('border-gray-300', 'focus:ring-blue-500');
                input.classList.add('border-red-500', 'focus:ring-red-500');

                const errorMsg = document.createElement('p');
                errorMsg.className = 'text-xs text-red-600 mt-1 pds-validation-error';
                errorMsg.textContent = messages[0];
                input.parentNode.appendChild(errorMsg);

                if (!firstErrorInput) firstErrorInput = input;
            }
        }

        if (firstErrorInput) {
            firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorInput.focus();
        }
    }

    function findInputByErrorKey(form, key) {
        let input = form.querySelector(`[name="${key}"]`);
        if (input) return input;

        // Convert dot notation to bracket notation (e.g. "children.0.name" -> "children[0][name]")
        const parts = key.split('.');
        let bracketName = parts[0];
        for (let i = 1; i < parts.length; i++) {
            bracketName += `[${parts[i]}]`;
        }

        input = form.querySelector(`[name="${bracketName}"]`);
        if (input) return input;

        // Fallback to array notation (e.g. "name[]")
        input = form.querySelector(`[name="${parts[0]}[]"]`);
        return input;
    }

    function executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function closeModal() {
        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');
        panel.classList.remove('translate-y-0', 'opacity-100', 'lg:scale-100');
        panel.classList.add('translate-y-4', 'opacity-0', 'lg:scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            formContainer.innerHTML = '';
            
            if (shouldReloadPageOnClose) {
                window.location.reload();
            }
        }, 300);
    }
});
