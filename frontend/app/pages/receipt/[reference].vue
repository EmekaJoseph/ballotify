<template>
    <div class="receipt-page">
        <div class="toolbar no-print">
            <NuxtLink to="/dashboard/profile" class="btn btn-ghost btn-sm">Account</NuxtLink>
            <button class="btn btn-primary btn-sm" :disabled="!data" @click="print">Print / Save as PDF</button>
        </div>

        <div v-if="loading" class="sheet"><div class="skeleton mb-3" style="height: 40px; width: 40%"></div><div class="skeleton" style="height: 200px"></div></div>
        <div v-else-if="!data" class="sheet text-center">
            <h1 class="h5 fw-800">Receipt not found</h1>
            <p class="text-muted mb-0">It may belong to another account, or the payment didn’t complete.</p>
        </div>
        <article v-else class="sheet">
            <header class="d-flex justify-content-between align-items-start gap-3 mb-5">
                <AppLogo />
                <div class="text-end">
                    <div class="h4 fw-800 mb-0">Receipt</div>
                    <div class="small text-muted mono">{{ data.reference }}</div>
                </div>
            </header>

            <div class="row g-4 mb-4 small">
                <div class="col-6">
                    <div class="small-caps mb-1">Billed to</div>
                    <div class="fw-bold">{{ data.billed_to.name }}</div>
                    <div class="text-muted">{{ data.billed_to.email }}</div>
                </div>
                <div class="col-6 text-end">
                    <div class="small-caps mb-1">Paid</div>
                    <div class="fw-bold">{{ formatDate(data.paid_at, true) }}</div>
                    <div class="text-muted">{{ channel }}</div>
                </div>
            </div>

            <table class="w-100 lines mb-4">
                <thead><tr><th>Item</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    <tr>
                        <td><div class="fw-semibold">{{ data.item }}</div><div class="small text-muted">{{ data.event_name }}</div></td>
                        <td class="text-end">{{ money }}</td>
                    </tr>
                </tbody>
                <tfoot><tr><th>Total paid</th><th class="text-end">{{ money }}</th></tr></tfoot>
            </table>

            <div class="paid-stamp">Paid in full</div>
            <p class="small text-muted mt-4 mb-0">
                {{ data.seller.name }}<template v-if="data.seller.email"> · {{ data.seller.email }}</template> · Payment processed by Paystack.
            </p>
        </article>
    </div>
</template>

<script setup lang="ts">
import { billingApi } from '~/api/endpoints/admin';
import type { PaymentReceiptInfo } from '~/types';

definePageMeta({ middleware: ['auth'] });
useHead({ title: 'Receipt · Ballotify' });

const route = useRoute();
const data = ref<PaymentReceiptInfo | null>(null);
const loading = ref(true);

const money = computed(() => (data.value ? `₦${data.value.amount_naira.toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : ''));
const channel = computed(() => (data.value?.channel ? data.value.channel.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase()) : 'Paystack'));

onMounted(async () => {
    try { data.value = (await billingApi.receipt(String(route.params.reference))).data; } catch { /* not found */ } finally { loading.value = false; }
});

function print() { window.print(); }
</script>

<style scoped>
.receipt-page { min-height: 100vh; background: var(--bg); padding: 1.5rem 1rem 3rem; }
.toolbar { max-width: 720px; margin: 0 auto 1rem; display: flex; justify-content: space-between; gap: .5rem; }
.sheet { max-width: 720px; margin: 0 auto; background: var(--surface); border: 1px solid var(--line); border-radius: 20px; padding: 2.5rem; }
.lines th, .lines td { padding: .75rem 0; border-bottom: 1px solid var(--line); }
.lines thead th { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
.lines tfoot th { border-bottom: 0; font-size: 1.1rem; }
.paid-stamp { display: inline-flex; gap: .5rem; align-items: center; font-weight: 800; color: var(--success); background: var(--success-50); padding: .4rem .9rem; border-radius: 999px; }
@media print {
    .receipt-page { background: #fff; padding: 0; }
    .sheet { border: 0; padding: 0; }
}
</style>
