<template>
    <BaseModal :open="!!target" :title="current ? 'Upgrade to a bigger size' : 'Upgrade this event to Pro'"
        :subtitle="target?.name" size="lg" @close="target = null">
        <p class="small text-muted mb-3">
            One-off payment for this event, no subscription.
            <template v-if="current">You only pay the difference from {{ tierByKey(current)?.name }}.</template>
        </p>
        <div v-if="!tiers.length" class="skeleton" style="height: 150px"></div>
        <TierPicker v-else v-model="choice" :tiers="tiers" :current="current" :voters="target?.voters ?? 0" />
        <ul class="perks small mt-3 mb-0">
            <li><i class="bi bi-check2"></i> Send codes and reminders by email and SMS (SMS included: 1 per voter)</li>
            <li><i class="bi bi-check2"></i> Your logo and colour, no Ballotify branding</li>
            <li><i class="bi bi-check2"></i> Co-organisers, USSD voting and voter self-registration</li>
            <li v-if="plans"><i class="bi bi-check2"></i> More SMS any time with SMS credits ({{ formatNaira(plans.sms_price_naira) }} each)</li>
        </ul>
        <template #footer>
            <template v-if="plans && !plans.payments_enabled">
                <a v-if="contactEmail" :href="`mailto:${contactEmail}?subject=${encodeURIComponent('Upgrade to Pro: ' + (target?.name ?? ''))}`" class="btn btn-soft">
                    <i class="bi bi-envelope"></i> Contact us to upgrade
                </a>
                <span class="small text-muted">Online payment isn’t available yet.</span>
            </template>
            <template v-else>
                <button class="btn btn-ghost" @click="target = null">Cancel</button>
                <button class="btn btn-primary" :disabled="redirecting || !choice || !plans" @click="pay">
                    <span v-if="redirecting" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-lightning-charge"></i>
                    {{ redirecting ? 'Please wait…' : `Pay ${payLabel}` }}
                </button>
            </template>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import type { ProTier } from '~/types';

const { plans, tiers, tierByKey, upgradeTarget: target, startUpgrade, redirecting } = useBilling();
const contactEmail = String(useRuntimeConfig().public.contactEmail || '');
const choice = ref<ProTier | null>(null);

/** The event's paid size (account-wide Pro can't be upgraded here). */
const current = computed<ProTier | null>(() => (target.value?.limits?.plan === 'pro' ? (target.value.limits.tier ?? null) : null));
const payLabel = computed(() => {
    const t = tierByKey(choice.value);
    if (!t) return '';
    const already = tierByKey(current.value)?.price_naira ?? 0;
    return formatNaira(Math.max(0, t.price_naira - already));
});

watch(target, () => { choice.value = null; });

function pay() {
    if (target.value && choice.value) startUpgrade(target.value.id, choice.value);
}
</script>

<style scoped>
.perks { list-style: none; padding: 0; display: grid; gap: .35rem; }
.perks .bi { color: var(--brand-600); margin-right: .35rem; }
</style>
