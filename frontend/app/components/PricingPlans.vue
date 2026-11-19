<template>
    <section v-if="info && info.billing_enabled" id="pricing" class="py-6 bg-surface border-top border-bottom">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eyebrow mb-3">Pricing</span>
                <h2 class="section-title mb-2">Start free. Pay only for the events that need more.</h2>
                <p class="lead-muted mx-auto" style="max-width: 600px">No subscription. Upgrade a single event to Pro with a one-off payment.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="plan card-b h-100">
                        <div class="small-caps mb-2">Free</div>
                        <div class="amount">₦0</div>
                        <p class="text-muted small mb-4">Try Ballotify properly on a small vote.</p>
                        <ul class="perks">
                            <li><i class="bi bi-check2"></i> Unlimited drafts; {{ info.free.max_active_events === 1 ? 'one live event at a time' : `${info.free.max_active_events} live events at a time` }}</li>
                            <li><i class="bi bi-check2"></i> Up to {{ formatNumber(info.free.max_voters) }} voters per event</li>
                            <li><i class="bi bi-check2"></i> Live results, secret ballot, voter receipts</li>
                            <li><i class="bi bi-check2"></i> Share codes on WhatsApp, CSV or printed slips</li>
                            <li class="off"><i class="bi bi-x"></i> Email &amp; SMS invitations</li>
                            <li class="off"><i class="bi bi-x"></i> Ballot without Ballotify branding</li>
                        </ul>
                        <NuxtLink :to="ctaLink" class="btn btn-ghost w-100 mt-auto">Start free</NuxtLink>
                    </div>
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="plan card-b h-100 featured">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small-caps text-brand">Pro</span>
                            <span class="pill pill-violet"><i class="bi bi-gem"></i> Per event</span>
                        </div>
                        <div class="amount">{{ formatNaira(info.pro_price_naira) }}<span>/event</span></div>
                        <p class="text-muted small mb-4">For elections and award nights that matter.</p>
                        <ul class="perks">
                            <li><i class="bi bi-check2"></i> Up to {{ formatNumber(info.pro.max_voters) }} voters</li>
                            <li><i class="bi bi-check2"></i> Send codes and reminders by email &amp; SMS</li>
                            <li><i class="bi bi-check2"></i> Your logo and colour, no Ballotify branding</li>
                            <li><i class="bi bi-check2"></i> Up to 10 co-organisers with roles</li>
                            <li><i class="bi bi-check2"></i> Doesn’t count towards your free live event</li>
                            <li><i class="bi bi-check2"></i> Everything in Free</li>
                        </ul>
                        <NuxtLink :to="ctaLink" class="btn btn-primary w-100 mt-auto">Create an event</NuxtLink>
                        <div class="small text-muted text-center mt-2">Upgrade from the event’s page · Paystack: card, transfer or USSD</div>
                    </div>
                </div>
            </div>
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
