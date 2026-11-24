<template>
    <BaseModal :open="!!target" title="Buy SMS credits" subtitle="Used once an event’s included SMS run out. They don’t expire and work on all your events."
        size="md" @close="target = null">
        <div v-if="target?.suggest" class="short mb-3">
            <i class="bi bi-info-circle"></i>
            <span>You need at least <b>{{ formatNumber(target.suggest) }}</b> more SMS for that send.</span>
        </div>
        <div class="packs mb-3">
            <button v-for="p in packs" :key="p" type="button" class="pack" :class="{ on: qty === p }" @click="qty = p">
                <span class="n">{{ formatNumber(p) }}</span>
                <span class="small text-muted">SMS</span>
                <span class="price">{{ formatNaira(p * price) }}</span>
            </button>
        </div>
        <label for="sms-qty" class="form-label">Or enter an amount</label>
        <div class="d-flex gap-2 align-items-center">
            <input id="sms-qty" v-model.number="qty" type="number" min="50" max="100000" step="50" class="form-control" style="max-width: 160px" />
            <span class="small text-muted">× {{ formatNaira(price) }} = <b class="text-ink">{{ formatNaira(total) }}</b></span>
        </div>
        <div v-if="qty < 50" class="field-error mt-1">The smallest pack is 50 SMS.</div>
        <p class="small text-muted mt-3 mb-0"><i class="bi bi-envelope"></i> Email and WhatsApp messages are always free.</p>
        <template #footer>
            <button class="btn btn-ghost" @click="target = null">Cancel</button>
            <button class="btn btn-primary" :disabled="redirecting || qty < 50 || qty > 100000 || !plans?.payments_enabled" @click="buy">
                <span v-if="redirecting" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-cart-check"></i>
                {{ redirecting ? 'Please wait…' : `Pay ${formatNaira(total)}` }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
const { plans, smsTarget: target, buySms, redirecting } = useBilling();
const packs = [100, 500, 1000, 5000];
const qty = ref(500);
const price = computed(() => plans.value?.sms_price_naira ?? 8);
const total = computed(() => Math.max(0, Math.floor(qty.value || 0)) * price.value);

// Pre-select the smallest pack that covers what's missing.
watch(target, (t) => {
    if (!t) return;
    const need = t.suggest ?? 0;
    qty.value = packs.find(p => p >= need) ?? Math.ceil(need / 50) * 50;
});

function buy() {
    if (target.value) buySms(Math.floor(qty.value), target.value.eventId);
}
</script>

<style scoped>
.short { display: flex; gap: .6rem; padding: .7rem .9rem; border-radius: 12px; background: var(--warning-50); color: var(--warning-text, var(--warning)); font-size: .9rem; }
.packs { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; }
@media (max-width: 575.98px) { .packs { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.pack { display: flex; flex-direction: column; align-items: center; gap: .1rem; padding: .7rem .4rem; border-radius: 12px; border: 1.5px solid var(--line); background: var(--surface); }
.pack:hover { border-color: var(--brand-500); }
.pack.on { border-color: var(--brand-500); background: var(--brand-50); }
.pack .n { font-size: 1.15rem; font-weight: 800; color: var(--ink); }
.pack .price { font-size: .82rem; font-weight: 700; color: var(--brand-600); }
</style>
