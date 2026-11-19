<template>
    <div class="card-b overflow-hidden">
        <div class="card-header-b">
            <div>
                <h3 class="h6 fw-800 mb-0">Payments & receipts</h3>
                <div class="small text-muted">Pro upgrades you’ve paid for.</div>
            </div>
        </div>
        <div v-if="loading" class="p-4 d-grid gap-2"><div v-for="i in 2" :key="i" class="skeleton" style="height: 38px"></div></div>
        <div v-else-if="!payments.length" class="px-4 py-4 small text-muted">No payments yet.</div>
        <ul v-else class="list-unstyled mb-0">
            <li v-for="p in payments" :key="p.reference" class="pay-row">
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold text-ink text-truncate">{{ p.event_name || 'Pro upgrade' }}</div>
                    <div class="small text-muted">{{ formatDate(p.paid_at || p.created_at, true) }} · <span class="mono">{{ p.reference }}</span></div>
                </div>
                <span class="fw-800 text-ink">{{ formatNaira(p.amount_naira) }}</span>
                <NuxtLink v-if="p.status === 'success'" :to="`/receipt/${p.reference}`" target="_blank" class="btn btn-ghost btn-sm">
                    <i class="bi bi-receipt"></i> Receipt
                </NuxtLink>
                <span v-else class="pill" :class="p.status === 'failed' ? 'pill-danger' : 'pill-upcoming'">{{ p.status === 'failed' ? 'Failed' : 'Pending' }}</span>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { billingApi } from '~/api/endpoints/admin';
import type { MyPayment } from '~/types';

const payments = ref<MyPayment[]>([]);
const loading = ref(true);
onMounted(async () => {
    try { payments.value = (await billingApi.myPayments()).data; } catch { /* stays empty */ } finally { loading.value = false; }
});
</script>

<style scoped>
.pay-row { display: flex; align-items: center; gap: .8rem; flex-wrap: wrap; padding: .75rem 1.25rem; border-bottom: 1px solid var(--line); }
.pay-row:last-child { border-bottom: 0; }
</style>
