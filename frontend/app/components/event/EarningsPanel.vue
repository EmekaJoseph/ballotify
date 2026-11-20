<template>
    <div>
        <div v-if="loading && !data" class="row g-3 mb-4"><div v-for="i in 4" :key="i" class="col-6 col-lg-3"><div class="skeleton" style="height: 92px"></div></div></div>
        <template v-else-if="data">
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3"><StatCard label="Votes sold" :value="formatNumber(data.votes_sold)" icon="bi-heart" tone="amber" :hint="`${formatNumber(data.payments)} payments`" /></div>
                <div class="col-6 col-lg-3"><StatCard label="Raised" :value="formatNaira(data.gross_naira)" icon="bi-cash-stack" /></div>
                <div class="col-6 col-lg-3"><StatCard label="Yours" :value="formatNaira(data.net_naira)" icon="bi-wallet2" tone="green" :hint="`after ${data.fee_percent}% platform fee`" /></div>
                <div class="col-6 col-lg-3"><StatCard label="To be paid out" :value="formatNaira(data.balance_naira)" icon="bi-bank" tone="violet" :hint="payoutHint" /></div>
            </div>

            <div v-if="data.late_payments" class="alert alert-warning border-0 rounded-4 small d-flex gap-2">
                <i class="bi bi-exclamation-triangle"></i>
                {{ data.late_payments }} payment{{ data.late_payments === 1 ? '' : 's' }} arrived after voting closed and {{ data.late_payments === 1 ? 'was' : 'were' }} not counted. We’ll refund {{ data.late_payments === 1 ? 'it' : 'them' }} from the payout.
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card-b overflow-hidden">
                        <div class="card-header-b"><h3 class="h6 fw-800 mb-0">Recent votes</h3>
                            <button class="btn btn-ghost btn-icon btn-sm" title="Refresh" aria-label="Refresh" @click="load"><i class="bi bi-arrow-clockwise"></i></button></div>
                        <EmptyState v-if="!data.recent.length" icon="bi-heart" title="No votes yet" text="Share the voting link: votes appear here as fans pay." />
                        <div v-else class="table-responsive">
                            <table class="table-b">
                                <thead><tr><th>Voter</th><th>For</th><th class="text-end">Votes</th><th class="text-end">Paid</th><th>When</th></tr></thead>
                                <tbody>
                                    <tr v-for="(r, i) in data.recent" :key="i" :class="{ 'text-muted': r.status !== 'success' }">
                                        <td>{{ r.buyer }}</td>
                                        <td class="fw-semibold text-ink">{{ r.candidate }}<span v-if="r.status === 'late'" class="pill pill-failed ms-1">Late</span></td>
                                        <td class="text-end">{{ formatNumber(r.quantity) }}</td>
                                        <td class="text-end">{{ formatNaira(r.amount_naira) }}</td>
                                        <td class="small text-nowrap">{{ r.paid_at ? formatDate(r.paid_at, true) : '' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-grid gap-4 align-content-start">
                    <div class="card-b pad">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h3 class="h6 fw-800 mb-0">Payout account</h3>
                            <span v-if="auth.user?.paystack_subaccount_code" class="pill pill-active"><i class="bi bi-lightning-charge"></i> Automatic</span>
                        </div>
                        <!-- Paystack set up: verified account + automatic payouts -->
                        <template v-if="verifiedBanks">
                            <p class="small text-muted mb-3">
                                Paystack pays your share straight to this account after each vote payment (usually the next working day).
                                Used for all your paid-voting events.
                            </p>
                            <form novalidate @submit.prevent="saveVerified">
                                <label for="v-bank" class="form-label">Bank</label>
                                <AppSelect id="v-bank" v-model="vbank.code" :options="banks.map(b => ({ value: b.code, label: b.name }))" class="mb-2"
                                    placeholder="Choose your bank" search-placeholder="Search banks…" searchable @change="vbank.name = ''" />
                                <label for="v-acct" class="form-label">Account number</label>
                                <div class="d-flex gap-2 mb-2">
                                    <input id="v-acct" v-model.trim="vbank.number" class="form-control mono" inputmode="numeric" maxlength="10" placeholder="10 digits" @input="vbank.name = ''" />
                                    <button type="button" class="btn btn-soft flex-shrink-0" :disabled="checking || !vbank.code || vbank.number.length !== 10" @click="checkAccount">
                                        <span v-if="checking" class="spinner-border spinner-border-sm"></span><span v-else>Check</span>
                                    </button>
                                </div>
                                <div v-if="vbank.name" class="acct-name mb-3"><i class="bi bi-person-check-fill"></i> {{ vbank.name }}</div>
                                <button class="btn btn-primary w-100" :disabled="savingBank || !vbank.name">
                                    <span v-if="savingBank" class="spinner-border spinner-border-sm"></span> {{ auth.user?.paystack_subaccount_code ? 'Update payout account' : 'Turn on automatic payouts' }}
                                </button>
                                <p v-if="auth.user?.payout_account_number" class="small text-muted mt-2 mb-0">
                                    Current: {{ auth.user.payout_account_name }} · {{ auth.user.payout_bank_name }} · <span class="mono">{{ auth.user.payout_account_number }}</span>
                                </p>
                            </form>
                        </template>
                        <template v-else>
                        <p class="small text-muted mb-3">Where we send your share by bank transfer. It’s used for all your paid-voting events.</p>
                        <form novalidate @submit.prevent="saveBank">
                            <label for="bank" class="form-label">Bank</label>
                            <input id="bank" v-model.trim="bank.payout_bank_name" class="form-control mb-2" list="ng-banks" placeholder="e.g. GTBank" maxlength="120" />
                            <datalist id="ng-banks"><option v-for="b in BANKS" :key="b" :value="b" /></datalist>
                            <label for="acct" class="form-label">Account number</label>
                            <input id="acct" v-model.trim="bank.payout_account_number" class="form-control mono mb-2" inputmode="numeric" maxlength="10" placeholder="10 digits" />
                            <label for="acct-name" class="form-label">Account name</label>
                            <input id="acct-name" v-model.trim="bank.payout_account_name" class="form-control mb-3" maxlength="120" />
                            <div v-if="bankError" class="field-error mb-2">{{ bankError }}</div>
                            <button class="btn btn-primary w-100" :disabled="savingBank">
                                <span v-if="savingBank" class="spinner-border spinner-border-sm"></span> Save payout account
                            </button>
                        </form>
                        </template>
                    </div>
                    <div v-if="data.payouts.length" class="card-b pad">
                        <h3 class="h6 fw-800 mb-2">Payouts received</h3>
                        <ul class="list-unstyled d-grid gap-2 mb-0 small">
                            <li v-for="(p, i) in data.payouts" :key="i" class="d-flex justify-content-between gap-2">
                                <span class="text-muted">{{ formatDate(p.created_at) }}<template v-if="p.note"> · {{ p.note }}</template></span>
                                <b class="text-ink">{{ formatNaira(p.amount_naira) }}</b>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';
import type { Bank, BallotEvent, Earnings } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const toast = useToast();
const auth = useAuthStore();

const BANKS = ['Access Bank', 'Ecobank', 'Fidelity Bank', 'First Bank', 'FCMB', 'GTBank', 'Kuda', 'Moniepoint', 'OPay', 'PalmPay', 'Polaris Bank',
    'Stanbic IBTC', 'Sterling Bank', 'UBA', 'Union Bank', 'Wema Bank', 'Zenith Bank'];

const data = ref<Earnings | null>(null);
const loading = ref(true);
async function load() {
    loading.value = true;
    try { data.value = (await adminApi.getEarnings(props.event.id)).data; }
    catch (e) { toast.error(apiErrorMessage(e)); }
    finally { loading.value = false; }
}
onMounted(load);

/** What the "To be paid out" card says, for automatic, mixed and manual payouts. */
const payoutHint = computed(() => {
    const d = data.value;
    if (!d) return '';
    if (d.automatic_payouts) {
        return d.balance_naira > 0
            ? `From before automatic payouts: we pay this by transfer`
            : 'Nothing owed: Paystack pays you automatically';
    }
    return d.paid_out_naira ? `${formatNaira(d.paid_out_naira)} paid so far` : 'Nothing paid out yet';
});

const bank = reactive({
    payout_bank_name: auth.user?.payout_bank_name ?? '',
    payout_account_number: auth.user?.payout_account_number ?? '',
    payout_account_name: auth.user?.payout_account_name ?? '',
});
const savingBank = ref(false);

// ----- Verified account + automatic payouts (when Paystack is set up)
const banks = ref<Bank[]>([]);
const verifiedBanks = ref(false);
const vbank = reactive({ code: auth.user?.payout_bank_code ?? '', number: auth.user?.payout_account_number ?? '', name: '' });
const checking = ref(false);
onMounted(async () => {
    try {
        const { data } = await adminApi.banks();
        banks.value = data.banks;
        verifiedBanks.value = data.verified && data.banks.length > 0;
    } catch { /* manual form */ }
});
async function checkAccount() {
    checking.value = true;
    try {
        vbank.name = (await adminApi.resolveAccount({ bank_code: vbank.code, account_number: vbank.number })).data.account_name;
    } catch (e) {
        toast.error(Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e), 'Account not found');
    } finally {
        checking.value = false;
    }
}
async function saveVerified() {
    savingBank.value = true;
    try {
        auth.user = (await adminApi.savePayoutAccount({ bank_code: vbank.code, account_number: vbank.number })).data;
        toast.success('Automatic payouts are on. Paystack will pay your share into this account.');
        await load();
    } catch (e) {
        toast.error(Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e), 'Payout account not saved');
    } finally {
        savingBank.value = false;
    }
}
const bankError = ref('');
async function saveBank() {
    savingBank.value = true;
    bankError.value = '';
    try {
        const { data: user } = await adminApi.updateMe({
            payout_bank_name: bank.payout_bank_name || null,
            payout_account_number: bank.payout_account_number || null,
            payout_account_name: bank.payout_account_name || null,
        });
        auth.user = user;
        toast.success('Payout account saved');
    } catch (e) {
        bankError.value = Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e);
    } finally {
        savingBank.value = false;
    }
}
</script>

<style scoped>
.pill-failed { background: var(--danger-50); color: var(--danger); }
.acct-name { display: flex; gap: .5rem; align-items: center; padding: .55rem .8rem; border-radius: 10px; background: var(--success-50); color: var(--success); font-weight: 700; font-size: .9rem; }
</style>
