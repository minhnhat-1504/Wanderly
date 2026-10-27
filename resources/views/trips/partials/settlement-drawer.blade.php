<div
    x-data="{
        open: false,
        tripId: {{ $trip->id }},
        loading: false,
        plan: null,
        supportedBanks: {},
        selectedTx: null,
        qrModalOpen: false,
        qrLoading: false,
        qrUrl: '',
        bankBin: '970422', // Default MBBank
        accountNumber: '',
        accountName: '',
        activeTab: 'transactions', // 'transactions' or 'balances'
        confirming: false,
        successMsg: '',

        init() {
            window.addEventListener('open-settlement-drawer', () => {
                this.open = true;
                this.loadSettlementPlan();
            });
        },

        async loadSettlementPlan() {
            this.loading = true;
            this.successMsg = '';
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/settlements`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.plan = data.plan;
                    this.supportedBanks = data.supported_banks || {};
                }
            } catch (err) {
                console.error('Failed to load settlement plan', err);
            } finally {
                this.loading = false;
            }
        },

        openVietQr(tx) {
            this.selectedTx = tx;
            this.accountName = tx.to_user_name || '';
            this.qrModalOpen = true;
            this.fetchQrUrl();
        },

        async fetchQrUrl() {
            if (!this.selectedTx) return;
            this.qrLoading = true;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/settlements/vietqr`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        bank_bin: this.bankBin,
                        account_number: this.accountNumber || '0000000000',
                        amount: this.selectedTx.amount,
                        from_user_id: this.selectedTx.from_user_id,
                        to_user_id: this.selectedTx.to_user_id,
                        account_name: this.accountName
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.qrUrl = data.qr_url;
                }
            } catch (err) {
                console.error('Failed to generate VietQR', err);
            } finally {
                this.qrLoading = false;
            }
        },

        async confirmSettlement(tx) {
            if (!confirm('{{ __('Mark this payment as completed?') }}')) return;

            this.confirming = true;
            try {
                const res = await fetch(`/ajax/trips/${this.tripId}/settlements/confirm`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        from_user_id: tx.from_user_id,
                        to_user_id: tx.to_user_id,
                        amount: tx.amount,
                        bank_bin: this.bankBin,
                        bank_account_number: this.accountNumber
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.successMsg = '{{ __('Settlement payment recorded successfully!') }}';
                    this.qrModalOpen = false;
                    this.loadSettlementPlan();
                }
            } catch (err) {
                console.error('Failed to confirm settlement', err);
            } finally {
                this.confirming = false;
            }
        }
    }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="settlement-drawer-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
        @click="open = false"
    ></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div
            x-show="open"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen max-w-md bg-white shadow-2xl flex flex-col"
        >
            {{-- Header --}}
            <div class="p-6 bg-gradient-to-r from-teal-700 via-teal-800 to-emerald-800 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-teal-300">
                            {{ __('Sprint 4 Innovation') }}
                        </span>
                        <h2 class="text-xl font-bold tracking-tight mt-0.5" id="settlement-drawer-title">
                            {{ __('Debt Settlement & VietQR') }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="p-2 rounded-xl text-teal-200 hover:text-white hover:bg-white/10 transition"
                        @click="open = false"
                    >
                        <span class="sr-only">{{ __('Close drawer') }}</span>
                        ✕
                    </button>
                </div>
                <p class="text-xs text-teal-100/90 mt-2">
                    {{ __('Min-Cash-Flow engine simplifies all group debts into direct 1-to-1 bank transfers.') }}
                </p>

                {{-- Tabs --}}
                <div class="mt-4 flex space-x-2 bg-black/20 p-1 rounded-xl text-xs font-medium">
                    <button
                        type="button"
                        class="flex-1 py-1.5 rounded-lg transition text-center"
                        :class="activeTab === 'transactions' ? 'bg-white text-teal-900 font-bold shadow' : 'text-teal-100 hover:text-white'"
                        @click="activeTab = 'transactions'"
                    >
                        {{ __('Settlement Plan') }}
                    </button>
                    <button
                        type="button"
                        class="flex-1 py-1.5 rounded-lg transition text-center"
                        :class="activeTab === 'balances' ? 'bg-white text-teal-900 font-bold shadow' : 'text-teal-100 hover:text-white'"
                        @click="activeTab = 'balances'"
                    >
                        {{ __('Member Balances') }}
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                {{-- Success Flash Alert --}}
                <div x-show="successMsg" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs flex items-center justify-between">
                    <span x-text="successMsg"></span>
                    <button type="button" @click="successMsg = ''" class="text-emerald-600 hover:text-emerald-900">✕</button>
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="text-center py-12 text-gray-500 text-sm">
                    {{ __('Computing Min-Cash-Flow balances...') }}
                </div>

                <div x-show="!loading && plan">
                    {{-- TAB 1: Min-Cash-Flow Transactions --}}
                    <div x-show="activeTab === 'transactions'" class="space-y-4">
                        {{-- Summary Banner --}}
                        <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-4 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-gray-500 uppercase font-semibold">{{ __('Total Transfers') }}</span>
                                <div class="text-lg font-bold text-gray-800">
                                    <span x-text="plan.total_transactions"></span> {{ __('transfers') }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-500 uppercase font-semibold">{{ __('Total Volume') }}</span>
                                <div class="text-lg font-bold text-teal-700" x-text="plan.total_settlement_amount.toLocaleString('vi-VN') + ' đ'"></div>
                            </div>
                        </div>

                        {{-- If All Settled --}}
                        <template x-if="plan.is_settled">
                            <div class="text-center py-10 px-4 bg-emerald-50/60 border border-emerald-200 rounded-2xl">
                                <div class="w-12 h-12 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-xl mb-2">
                                    ✓
                                </div>
                                <h4 class="font-bold text-emerald-900 text-sm">{{ __('All Settled Up!') }}</h4>
                                <p class="text-xs text-emerald-700 mt-1">
                                    {{ __('There are no outstanding debts in this trip.') }}
                                </p>
                            </div>
                        </template>

                        {{-- Transaction Cards --}}
                        <div class="space-y-3">
                            <template x-for="(tx, idx) in plan.transactions" :key="'tx-' + idx">
                                <div class="bg-white border rounded-2xl p-4 shadow-sm hover:shadow transition space-y-3" :class="tx.status === 'completed' ? 'border-emerald-200 bg-emerald-50/20' : 'border-gray-200'">
                                    <div class="flex items-center justify-between">
                                        {{-- Debtor -> Creditor Flow --}}
                                        <div class="flex items-center space-x-2 text-xs">
                                            <span class="font-bold text-rose-700 bg-rose-50 px-2 py-1 rounded-md" x-text="tx.from_user_name"></span>
                                            <span class="text-gray-400 font-bold">➔</span>
                                            <span class="font-bold text-teal-700 bg-teal-50 px-2 py-1 rounded-md" x-text="tx.to_user_name"></span>
                                        </div>

                                        {{-- Status Badge --}}
                                        <template x-if="tx.status === 'completed'">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                                ✓ {{ __('Paid') }}
                                            </span>
                                        </template>
                                        <template x-if="tx.status !== 'completed'">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                                                {{ __('Pending') }}
                                            </span>
                                        </template>
                                    </div>

                                    <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                                        <div>
                                            <span class="text-[11px] text-gray-400 uppercase font-semibold">{{ __('Amount') }}</span>
                                            <div class="text-base font-extrabold text-gray-900" x-text="tx.formatted_amount"></div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex items-center space-x-2">
                                            <button
                                                type="button"
                                                class="px-3 py-1.5 rounded-xl bg-teal-600 text-white text-xs font-semibold shadow hover:bg-teal-500 transition flex items-center space-x-1"
                                                @click="openVietQr(tx)"
                                            >
                                                <span>{{ __('VietQR') }}</span>
                                            </button>

                                            <template x-if="tx.status !== 'completed'">
                                                <button
                                                    type="button"
                                                    class="px-2.5 py-1.5 rounded-xl bg-emerald-100 text-emerald-800 text-xs font-semibold hover:bg-emerald-200 transition"
                                                    title="{{ __('Mark as paid') }}"
                                                    @click="confirmSettlement(tx)"
                                                >
                                                    ✓
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- TAB 2: Member Balances (Net Balances) --}}
                    <div x-show="activeTab === 'balances'" class="space-y-3">
                        <template x-for="m in plan.members_summary" :key="'mem-' + m.user_id">
                            <div class="p-3.5 rounded-2xl border border-gray-200/80 bg-white hover:bg-gray-50/50 transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-800 font-bold flex items-center justify-center text-xs">
                                            <span x-text="m.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-800" x-text="m.name"></div>
                                            <div class="text-[11px] text-gray-400">
                                                {{ __('Spent') }}: <span class="font-medium text-gray-600" x-text="m.total_spent.toLocaleString('vi-VN') + ' đ'"></span> •
                                                {{ __('Owed') }}: <span class="font-medium text-gray-600" x-text="m.total_owed.toLocaleString('vi-VN') + ' đ'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <template x-if="m.net_balance > 0">
                                            <div class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg">
                                                +<span x-text="m.net_balance.toLocaleString('vi-VN')"></span> đ
                                            </div>
                                        </template>
                                        <template x-if="m.net_balance < 0">
                                            <div class="text-xs font-bold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-lg">
                                                <span x-text="m.net_balance.toLocaleString('vi-VN')"></span> đ
                                            </div>
                                        </template>
                                        <template x-if="m.net_balance == 0">
                                            <div class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-lg">
                                                {{ __('Settled') }}
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>{{ __('Auto-refreshed with timeline changes') }}</span>
                <button
                    type="button"
                    class="font-semibold text-teal-700 hover:underline"
                    @click="loadSettlementPlan()"
                >
                    {{ __('Refresh') }}
                </button>
            </div>
        </div>
    </div>

    {{-- VIETQR POPUP MODAL --}}
    <div
        x-show="qrModalOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm" @click="qrModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div
                class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all sm:w-full sm:max-w-sm border border-gray-100 space-y-4"
                @click.away="qrModalOpen = false"
            >
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-base">
                        {{ __('VietQR Napas Quick Pay') }}
                    </h3>
                    <button type="button" @click="qrModalOpen = false" class="text-gray-400 hover:text-gray-700">✕</button>
                </div>

                <template x-if="selectedTx">
                    <div class="space-y-3">
                        <div class="text-xs bg-gray-50 p-2.5 rounded-xl space-y-1">
                            <div>{{ __('From') }}: <strong class="text-gray-900" x-text="selectedTx.from_user_name"></strong></div>
                            <div>{{ __('Pay to') }}: <strong class="text-teal-700" x-text="selectedTx.to_user_name"></strong></div>
                            <div>{{ __('Amount') }}: <strong class="text-rose-600 text-sm" x-text="selectedTx.formatted_amount"></strong></div>
                        </div>

                        {{-- Bank Selector & Account Number --}}
                        <div class="space-y-2">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 uppercase">{{ __('Receiver Bank') }}</label>
                                <select
                                    x-model="bankBin"
                                    @change="fetchQrUrl()"
                                    class="w-full text-xs rounded-xl border-gray-300 py-1.5 focus:border-teal-500 focus:ring-teal-500"
                                >
                                    <template x-for="(b, code) in supportedBanks" :key="code">
                                        <option :value="code" x-text="b.code + ' - ' + b.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 uppercase">{{ __('Receiver Account Number') }}</label>
                                <input
                                    type="text"
                                    x-model="accountNumber"
                                    @input.debounce.500ms="fetchQrUrl()"
                                    placeholder="{{ __('Enter bank account number...') }}"
                                    class="w-full text-xs rounded-xl border-gray-300 py-1.5 focus:border-teal-500 focus:ring-teal-500"
                                >
                            </div>
                        </div>

                        {{-- QR Display --}}
                        <div class="text-center py-2">
                            <template x-if="qrLoading">
                                <div class="py-12 text-xs text-gray-400">{{ __('Generating QR...') }}</div>
                            </template>
                            <template x-if="!qrLoading && qrUrl">
                                <div class="inline-block p-2 bg-white rounded-2xl border-2 border-teal-500/30 shadow-md">
                                    <img :src="qrUrl" alt="VietQR" class="w-56 h-auto mx-auto rounded-xl">
                                </div>
                            </template>
                            <p class="text-[11px] text-gray-500 mt-2">
                                {{ __('Open any Banking app or MoMo to scan and pay.') }}
                            </p>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-2 flex space-x-2">
                            <button
                                type="button"
                                class="flex-1 py-2 text-xs font-semibold rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition"
                                @click="qrModalOpen = false"
                            >
                                {{ __('Close') }}
                            </button>
                            <button
                                type="button"
                                class="flex-1 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 transition"
                                @click="confirmSettlement(selectedTx)"
                            >
                                ✓ {{ __('Confirm Paid') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
