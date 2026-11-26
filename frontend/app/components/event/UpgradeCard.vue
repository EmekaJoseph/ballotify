<template>
    <div v-if="show" class="card-b pad plan-card" :class="isPro ? 'is-pro' : 'is-free'">
        <!-- Pro: size, voters, SMS -->
        <template v-if="isPro">
            <div class="d-flex align-items-center gap-3">
                <span class="icon-tile pro"><i class="bi bi-lightning-charge-fill"></i></span>
                <div class="min-w-0">
                    <div class="fw-800 text-ink">{{ limits!.tier_name || 'Pro' }} event</div>
                    <div class="small text-muted">{{ proNote }}</div>
                </div>
            </div>

            <div class="usage mt-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted"><i class="bi bi-people"></i> Voters</span>
                    <b v-if="limits!.max_voters === null">{{ formatNumber(voterCount) }} <span class="text-muted fw-normal">· no limit</span></b>
                    <b v-else :class="{ 'text-danger': voterCount >= limits!.max_voters }">{{ formatNumber(voterCount) }} / {{ formatNumber(limits!.max_voters) }}</b>
                </div>
                <div v-if="limits!.max_voters !== null" class="bar slim"><span :style="{ width: Math.min(100, percent(voterCount, limits!.max_voters)) + '%' }"></span></div>
            </div>

            <div v-if="sms && !sms.unlimited" class="usage mt-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted"><i class="bi bi-chat-dots"></i> SMS included</span>
                    <b :class="{ 'text-danger': sms.left === 0 }">{{ formatNumber(sms.left) }} of {{ formatNumber(sms.included) }} left</b>
                </div>
                <div class="bar slim sms"><span :style="{ width: Math.min(100, percent(sms.used, sms.included)) + '%' }"></span></div>
                <div class="d-flex justify-content-between align-items-center gap-2 mt-2 small">
                    <span class="text-muted">SMS credits: <b class="text-ink">{{ formatNumber(sms.credits) }}</b></span>
                    <button class="btn btn-ghost btn-sm" @click="openBuySms({ eventId: event.id })"><i class="bi bi-cart-plus"></i> Buy SMS credits</button>
                </div>
            </div>

            <button v-if="canGrow" class="btn btn-soft w-100 mt-3" @click="openUpgrade({ id: event.id, name: event.name, limits, voters: voterCount })">
                <i class="bi bi-arrow-up-circle"></i> Upgrade to a bigger size
            </button>
        </template>

        <!-- Free: choose a size -->
        <template v-else>
            <div>
                <span class="pill pill-completed">Free plan</span>
                <h3 class="h6 fw-800 mt-2 mb-1">Upgrade this event to Pro</h3>
                <p class="small text-muted mb-0">One-off payment for this event. No subscription.</p>
            </div>

            <div v-if="limits?.max_voters" class="usage mt-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">Voters on Free</span>
                    <b :class="{ 'text-danger': voterCount >= limits.max_voters }">{{ formatNumber(voterCount) }} / {{ formatNumber(limits.max_voters) }}</b>
                </div>
                <div class="bar slim"><span :style="{ width: Math.min(100, percent(voterCount, limits.max_voters)) + '%' }"></span></div>
            </div>

            <div v-if="event.status === 'completed'" class="small text-muted mt-3">
                <i class="bi bi-info-circle"></i> Voting has closed, so this event can no longer be upgraded.
            </div>
            <template v-else>
                <div v-if="!tiers.length" class="skeleton mt-3" style="height: 120px"></div>
                <TierPicker v-else v-model="choice" class="mt-3" :tiers="tiers" :voters="voterCount" />
                <ul class="perks mt-3">
                    <li><i class="bi bi-check2"></i> Email &amp; SMS invitations (1 SMS per voter included)</li>
                    <li><i class="bi bi-check2"></i> No “Powered by Ballotify” on the ballot</li>
                    <li><i class="bi bi-check2"></i> Doesn’t count towards your free event limit</li>
                </ul>
                <template v-if="plans && !plans.payments_enabled">
                    <a v-if="contactEmail" :href="`mailto:${contactEmail}?subject=${encodeURIComponent('Upgrade to Pro: ' + event.name)}`" class="btn btn-soft w-100">
                        <i class="bi bi-envelope"></i> Contact us to upgrade
                    </a>
                    <div class="small text-muted mt-2">Online payment isn’t available yet.</div>
                </template>
                <button v-else class="btn btn-primary w-100" :disabled="redirecting || !plans || !choice" @click="choice && startUpgrade(event.id, choice)">
                    <span v-if="redirecting" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="bi bi-lightning-charge"></i>
                    {{ redirecting ? 'Please wait…' : `Upgrade now · ${formatNaira(tierByKey(choice)?.price_naira ?? 0)}` }}
                </button>
                <div v-if="plans?.payments_enabled" class="secure small text-muted mt-2">
                    <i class="bi bi-lock"></i> Secure payment: card, bank transfer or USSD.
                </div>
            </template>
        </template>
    </div>
</template>

<script setup lang="ts">
import type { BallotEvent, ProTier } from '~/types';

const props = defineProps<{ event: BallotEvent }>();

const { plans, loadPlans, tiers, tierByKey, startUpgrade, redirecting, openUpgrade, openBuySms } = useBilling();
const contactEmail = String(useRuntimeConfig().public.contactEmail || '');
onMounted(() => loadPlans());

const choice = ref<ProTier | null>(null);
const limits = computed(() => props.event.limits);
const isPro = computed(() => limits.value?.plan === 'pro');
const sms = computed(() => limits.value?.sms ?? null);
const voterCount = computed(() => props.event.voters_count ?? 0);
// Hidden when billing is switched off for this installation.
const show = computed(() => !!limits.value && (plans.value ? plans.value.billing_enabled : true) && limits.value.max_voters !== null);
/** A paid size below Large can grow (account-wide Pro is already the largest). */
const canGrow = computed(() => isPro.value && !limits.value?.via_account && limits.value?.tier !== 'large' && limits.value?.tier !== 'unlimited' && props.event.status !== 'completed');

const proNote = computed(() => {
    if (limits.value?.via_account) return 'Your account includes Pro for every event.';
    if (props.event.upgraded_via === 'admin') return 'Upgraded by the Ballotify team.';
    return 'Thanks for upgrading. All Pro features are on.';
});
</script>

<style scoped>
.plan-card.is-free { border-color: var(--brand-200); }
.perks { list-style: none; padding: 0; display: grid; gap: .35rem; font-size: .9rem; color: var(--ink-2); margin-bottom: 1rem; }
.perks .bi { color: var(--brand-600); margin-right: .35rem; }
.secure { text-align: center; }
.bar.sms span { background: var(--pro-icon, #e09400); }
/* The overview column is narrow: sizes stack. */
.plan-card :deep(.tiers) { grid-template-columns: 1fr; }
</style>
