<div
    x-data="{
        open: false,
        message: '',
        pendingForm: null,
        show(detail) {
            this.message = detail?.message ?? '';
            this.pendingForm = detail?.form ?? null;
            this.open = true;
            document.body.classList.add('overflow-hidden');
            this.$nextTick(() => {
                this.$refs?.cancelButton?.focus();
            });
        },
        hide() {
            this.open = false;
            this.message = '';
            this.pendingForm = null;
            document.body.classList.remove('overflow-hidden');
        },
        confirm() {
            if (this.pendingForm) {
                this.pendingForm.dataset.confirmed = 'true';
                this.pendingForm.submit();
            }
            this.hide();
        }
    }"
    x-init="window.addEventListener('confirm-dialog:open', event => show(event.detail)); window.addEventListener('confirm-dialog:close', () => hide());"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center px-4 py-8 sm:px-6"
    aria-live="assertive"
    role="dialog"
    aria-modal="true"
    x-on:keydown.escape.window="hide()"
>
    <div class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm" @click="hide"></div>

    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full max-w-md md:max-w-lg bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 divide-y divide-gray-200"
    >
        <div class="px-6 py-5">
            <div class="flex items-start space-x-4">
                <div class="shrink-0 w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Please Confirm</h3>
                    <p class="mt-2 text-sm text-gray-600" x-text="message"></p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
            <button type="button" x-ref="cancelButton" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-200 shadow-sm hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @click="hide">Cancel</button>
            <button type="button" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-white bg-indigo-600 shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @click="confirm">Yes, Continue</button>
        </div>
    </div>
</div>
