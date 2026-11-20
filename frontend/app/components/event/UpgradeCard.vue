<template>
    <div v-if="show" class="card-b pad plan-card" :class="isPro ? 'is-pro' : 'is-free'">
        <!-- Pro -->
        <template v-if="isPro">
            <div class="d-flex align-items-center gap-3">
                <span class="icon-tile violet"><i class="bi bi-gem"></i></span>
                <div class="min-w-0">
                    <div class="fw-800 text-ink">Pro event</div>
                    <div class="small text-muted">{{ proNote }}</div>
                </div>
            </div>
            <ul class="perks mt-3 mb-0">
                <li><i class="bi bi-check2"></i> Up to {{ formatNumber(limits!.max_voters ?? 0) }} voters</li>
                <li><i class="bi bi-check2"></i> Email &amp; SMS invitations</li>
                <li><i class="bi bi-check2"></i> No Ballotify branding on the ballot</li>
            </ul>
        </template>

        <!-- Free -->
        <template v-else>
            <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                <div>
                    <span class="pill pill-completed">Free plan</span>
                    <h3 class="h6 fw-800 mt-2 mb-1">Upgrade this event to Pro</h3>
                    <p class="small text-muted mb-0">One-off payment for this event. No subscription.</p>
                </div>
                <div v-if="plans" class="price">{{ priceLabel }}<span>/event</span></div>
            </div>

            <div v-if="limits?.max_voters" class="usage mt-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">Voters on Free</span>
                    <b :class="{ 'text-danger': voterCount >= limits.max_voters }">{{ formatNumber(voterCount) }} / {{ formatNumber(limits.max_voters) }}</b>
                </div>
                <div class="bar slim"><span :style="{ width: Math.min(100, percent(voterCount, limits.max_voters)) + '%' }"></span></div>
            </div>

            <ul v-if="plans" class="perks mt-3">
                <li><i class="bi bi-check2"></i> Up to {{ formatNumber(plans.pro.max_voters) }} voters (Free: {{ formatNumber(plans.free.max_voters) }})</li>
                <li><i class="bi bi-check2"></i> Send codes by email &amp; SMS</li>
                <li><i class="bi bi-check2"></i> No “Powered by Ballotify” on the ballot</li>
                <li><i class="bi bi-check2"></i> Doesn’t count towards your free event limit</li>
            </ul>

            <div v-if="event.status === 'completed'" class="small text-muted">
                <i class="bi bi-info-circle"></i> Voting has closed, so this event can no longer be upgraded.
            </div>
            <template v-else-if="plans && !plans.payments_enabled">
                <a v-if="contactEmail" :href="`mailto:${contactEmail}?subject=${encodeURIComponent('Upgrade to Pro: ' + event.name)}`" class="btn btn-soft w-100">
                    <i class="bi bi-envelope"></i> Contact us to upgrade
                </a>
                <div class="small text-muted mt-2">Online payment isn’t available yet.</div>
            </template>
            <button v-else class="btn btn-primary w-100" :disabled="redirecting || !plans" @click="startUpgrade(event.id)">
                <span v-if="redirecting" class="spinner-border spinner-border-sm"></span>
                <i v-else class="bi bi-lightning-charge"></i>
                {{ redirecting ? 'Please wait…' : 'Upgrade now' }}
            </button>
            <div v-if="plans?.payments_enabled && event.status !== 'completed'" class="secure small text-muted mt-2">
                <i class="bi bi-lock"></i> Secure payment: card, bank transfer or USSD.
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import type { BallotEvent } from '~/types';

const props = defineProps<{ event: BallotEvent }>();

const { plans, loadPlans, priceLabel, startUpgrade, redirecting } = useBilling();
const contactEmail = String(useRuntimeConfig().public.contactEmail || '');
onMounted(() => loadPlans());

const limits = computed(() => props.event.limits);
const isPro = computed(() => limits.value?.plan === 'pro');
const voterCount = computed(() => props.event.voters_count ?? 0);
// Hidden when billing is switched off for this installation.
const show = computed(() => !!limits.value && (plans.value ? plans.value.billing_enabled : true) && limits.value.max_voters !== null);

const proNote = computed(() => {
    if (limits.value?.via_account) return 'Your account includes Pro for every event.';
    if (props.event.upgraded_via === 'admin') return 'Upgraded by the Ballotify team.';
    return 'Thanks for upgrading. All Pro features are on.';
});
</script>

<style scoped>
.plan-card.is-free { border-color: var(--brand-200); }
.price { font-size: 1.5rem; font-weight: 800; color: var(--ink); line-height: 1; }
.price span { font-size: .8rem; font-weight: 600; color: var(--muted); margin-left: .15rem; }
.perks { list-style: none; padding: 0; display: grid; gap: .35rem; font-size: .9rem; color: var(--ink-2); margin-bottom: 1rem; }
.perks .bi { color: var(--brand-600); margin-right: .35rem; }
.secure { text-align: center; }
</style>
