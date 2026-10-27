<div
    x-data="{
        open: false,
        excessAmount: 0,
        onConfirmCallback: null,

        init() {
            window.addEventListener('open-budget-override-modal', (e) => {
                this.excessAmount = e.detail?.excess || 0;
                this.onConfirmCallback = e.detail?.onConfirm || null;
                this.open = true;
            });
        },

        confirm() {
            this.open = false;
            if (typeof this.onConfirmCallback === 'function') {
                this.onConfirmCallback();
            }
        },

        cancel() {
            this.open = false;
        }
    }"
    x-show="open"
    x-cloak
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="override-modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="cancel()"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm"
    ></div>

    <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            @click.stop
            class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-md p-6 text-center border-2 border-red-200"
        >
            {{-- Warning Badge (Clean typography, no icon) --}}
            <div class="w-14 h-14 mx-auto rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl font-black mb-4 border border-red-200">
                !
            </div>

            <h3 class="text-xl font-bold text-gray-900 mb-2" id="override-modal-title">
                {{ __('Budget Exceeded Warning') }}
            </h3>

            <p class="text-sm text-gray-500 mb-4">
                {{ __('This activity will exceed your total allocated budget by') }}
                <strong class="text-red-600 font-bold block text-base mt-1" x-text="new Intl.NumberFormat('vi-VN').format(excessAmount) + ' VND'"></strong>
            </p>

            <p class="text-xs text-gray-400 bg-gray-50 p-3 rounded-xl border border-gray-100 mb-6">
                {{ __('Are you sure you want to proceed and override the planned budget? You can still adjust your other activities later.') }}
            </p>

            <div class="flex items-center justify-center gap-3">
                <x-button variant="secondary" type="button" @click="cancel()">
                    {{ __('Cancel') }}
                </x-button>

                <x-button variant="danger" type="button" @click="confirm()">
                    {{ __('Confirm & Override') }}
                </x-button>
            </div>
        </div>
    </div>
</div>
