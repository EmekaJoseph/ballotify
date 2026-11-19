<template>
    <div>
        <p class="small text-muted">
            Money from paid public voting is collected in your Paystack account. Send each organiser their share by bank transfer,
            then record it here so their Earnings page stays correct.
        </p>
        <div class="card-b overflow-hidden">
            <div v-if="loading" class="p-4 d-grid gap-2"><div v-for="i in 3" :key="i" class="skeleton" style="height: 44px"></div></div>
            <EmptyState v-else-if="!rows.length" icon="bi-bank" title="No paid-voting events yet" text="Events with paid voting appear here once fans start paying." />
            <div v-else class="table-responsive">
                <table class="table-b">
                    <thead>
                        <tr><th>Event</th><th>Organiser’s bank</th><th class="text-end">Raised</th><th class="text-end">Fee</th><th class="text-end">Paid out</th><th class="text-end">Owed</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in rows" :key="r.id">
                            <td style="min-width: 180px">
                                <div class="fw-semibold text-ink">{{ r.name }}</div>
                                <div class="small text-muted">{{ r.organiser?.name }} · <StatusBadge :status="r.status" /></div>
                                <div v-if="r.late_payments" class="small text-warning-emphasis"><i class="bi bi-exclamation-triangle"></i> {{ r.late_payments }} late payment(s) to refund</div>
                            </td>
                            <td class="small" style="min-width: 170px">
                                <template v-if="r.bank">
                                    <div class="fw-semibold">{{ r.bank.account_name }}</div>
                                    <div class="text-muted">{{ r.bank.bank_name }} · <span class="mono">{{ r.bank.account_number }}</span>
                                        <button class="btn btn-link btn-sm p-0 ms-1 text-muted" title="Copy account number" aria-label="Copy account number" @click="copyText(r.bank.account_number, 'Account number copied')"><i class="bi bi-copy"></i></button>
                                    </div>
                                </template>
                                <span v-else class="text-muted">Not set yet</span>
                            </td>
                            <td class="text-end">{{ formatNaira(r.gross_naira) }}</td>
                            <td class="text-end text-muted">{{ formatNaira(r.fee_naira) }}</td>
                            <td class="text-end">{{ formatNaira(r.paid_out_naira) }}</td>
                            <td class="text-end fw-800" :class="r.balance_naira > 0 ? 'text-ink' : 'text-muted'">{{ formatNaira(r.balance_naira) }}</td>
                            <td class="text-end">
                                <button class="btn btn-soft btn-sm" :disabled="r.balance_naira <= 0" @click="openRecord(r)">Record payout</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <BaseModal :open="!!recording" :title="recording ? `Payout for ${recording.name}` : ''" size="sm" @close="recording = null">
            <form id="payout-form" @submit.prevent="save">
                <label for="po-amount" class="form-label">Amount sent (₦)</label>
                <input id="po-amount" v-model.number="amount" type="number" min="1" step="0.01" class="form-control mb-3" />
                <label for="po-note" class="form-label">Note <span class="text-muted fw-normal">(optional)</span></label>
                <input id="po-note" v-model.trim="note" class="form-control" maxlength="255" placeholder="e.g. Transfer ref 0012345" />
            </form>
            <template #footer>
                <button class="btn btn-ghost" @click="recording = null">Cancel</button>
                <button type="submit" form="payout-form" class="btn btn-primary" :disabled="saving || !(amount > 0)">
                    <span v-if="saving" class="spinner-border spinner-border-sm"></span> Record
                </button>
            </template>
        </BaseModal>
    </div>
</template>

<script setup lang="ts">
import { platformApi } from '~/api/endpoints/admin';
import type { PlatformPayout } from '~/types';

const toast = useToast();
const rows = ref<PlatformPayout[]>([]);
const loading = ref(true);
const recording = ref<PlatformPayout | null>(null);
const amount = ref(0);
const note = ref('');
const saving = ref(false);

async function load() {
    try { rows.value = (await platformApi.payouts()).data; } catch (e) { toast.error(apiErrorMessage(e)); } finally { loading.value = false; }
}
onMounted(load);

function openRecord(r: PlatformPayout) {
    recording.value = r;
    amount.value = r.balance_naira;
    note.value = '';
}

async function save() {
    if (!recording.value) return;
    saving.value = true;
    try {
        await platformApi.recordPayout(recording.value.id, { amount_naira: amount.value, note: note.value || undefined });
        toast.success(`Payout of ${formatNaira(amount.value)} recorded`);
        recording.value = null;
        await load();
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        saving.value = false;
    }
}
</script>
