<template>
    <section v-if="info && info.billing_enabled" id="pricing" class="py-6 bg-surface border-top border-bottom">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eyebrow mb-3">Pricing</span>
                <h2 class="section-title mb-2">Start free. Pay only for the events that need more.</h2>
                <p class="lead-muted mx-auto" style="max-width: 600px">No subscription. Upgrade a single event to Pro with a one-off payment.</p>
            </div>

            <div class="row g-3 justify-content-center">
                <div class="col-sm-6 col-xl-3">
                    <div class="plan card-b h-100">
                        <div class="small-caps mb-2">Free</div>
                        <div class="amount">₦0</div>
                        <p class="text-muted small mb-4">Try Ballotify properly on a small vote.</p>
                        <ul class="perks">
                            <li><i class="bi bi-check2"></i> Up to {{ formatNumber(info.free.max_voters) }} voters per event</li>
                            <li><i class="bi bi-check2"></i> Unlimited drafts; {{ info.free.max_active_events === 1 ? 'one live event at a time' : `${info.free.max_active_events} live events at a time` }}</li>
                            <li><i class="bi bi-check2"></i> Live results, secret ballot, receipts</li>
                            <li><i class="bi bi-check2"></i> Share codes on WhatsApp, CSV or slips</li>
                            <li class="off"><i class="bi bi-x"></i> Email &amp; SMS invitations</li>
                        </ul>
                        <NuxtLink :to="ctaLink" class="btn btn-ghost w-100 mt-auto">Start free</NuxtLink>
                    </div>
                </div>
                <div v-for="t in info.tiers" :key="t.key" class="col-sm-6 col-xl-3">
                    <div class="plan card-b h-100" :class="{ featured: t.key === 'standard' }">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small-caps text-brand">{{ t.name }}</span>
                            <span v-if="t.key === 'standard'" class="pill pill-pro"><i class="bi bi-lightning-charge-fill"></i> Popular</span>
                        </div>
                        <div class="amount">{{ formatNaira(t.price_naira) }}<span>/event</span></div>
                        <p class="text-muted small mb-4">{{ blurb[t.key] }}</p>
                        <ul class="perks">
                            <li><i class="bi bi-check2"></i> Up to <b>{{ formatNumber(t.max_voters) }}</b> voters</li>
                            <li><i class="bi bi-check2"></i> <b>{{ formatNumber(t.sms_included) }}</b> SMS included, plus email</li>
                            <li><i class="bi bi-check2"></i> Your logo, no Ballotify branding</li>
                            <li><i class="bi bi-check2"></i> Co-organisers, USSD, self-registration</li>
                            <li><i class="bi bi-check2"></i> Everything in Free</li>
                        </ul>
                        <NuxtLink :to="ctaLink" class="btn w-100 mt-auto" :class="t.key === 'standard' ? 'btn-primary' : 'btn-ghost'">Create an event</NuxtLink>
                    </div>
                </div>
            </div>
            <p class="text-center small text-muted mt-4 mb-1">
                <i class="bi bi-chat-dots"></i> One-off payment per event; move up a size any time by paying the difference.
                Need more SMS? <b class="text-ink">SMS credits</b> are {{ formatNaira(info.sms_price_naira) }} each. Email and WhatsApp are always free.
            </p>
            <p v-if="info.paid_votes" class="text-center small text-muted mt-4 mb-1">
                <i class="bi bi-trophy"></i> <b class="text-ink">Award nights:</b> paid public voting is free to set up. We keep {{ info.paid_votes.fee_percent }}% of each vote payment; the rest is yours.
            </p>
            <p class="text-center small text-muted mt-2 mb-0">Running something bigger? <a v-if="contactEmail" :href="`mailto:${contactEmail}`">Talk to us</a><span v-else>Talk to us</span> about larger events.</p>
        </div>
    </section>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import { useAuthStore } from '~/stores/auth';
import type { PlanInfo } from '~/types';

const auth = useAuthStore();
const ctaLink = computed(() => (auth.isAuthenticated ? '/dashboard/events/create' : '/signup'));
const contactEmail = String(useRuntimeConfig().public.contactEmail || '');

// Hidden if the API is unreachable or billing is switched off.
const info = ref<PlanInfo | null>(null);
onMounted(async () => {
    try { info.value = (await publicApi.plans()).data; } catch { /* no pricing section */ }
});

const blurb: Record<string, string> = {
    small: 'Committees, clubs and small congregations.',
    standard: 'Associations, churches and unions.',
    large: 'Large elections, AGMs and award nights.',
};
</script>

<style scoped>
.plan { display: flex; flex-direction: column; padding: 2rem; }
.plan.featured { border: 2px solid var(--brand-500); box-shadow: var(--shadow-lg); }
.amount { font-size: 2.4rem; font-weight: 800; letter-spacing: -.03em; line-height: 1.1; color: var(--ink); }
.amount span { font-size: .95rem; font-weight: 600; color: var(--muted); margin-left: .2rem; }
.perks { list-style: none; padding: 0; margin: 0 0 1.75rem; display: grid; gap: .6rem; color: var(--ink-2); }
.perks .bi { color: var(--brand-600); margin-right: .45rem; font-size: 1.05rem; }
.perks .off { color: var(--muted); }
.perks .off .bi { color: var(--icon-muted); }
</style>
