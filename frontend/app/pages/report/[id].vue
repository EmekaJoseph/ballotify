<template>
    <div class="report-shell">
        <div class="toolbar no-print">
            <NuxtLink :to="`/dashboard/events/${id}?tab=results`" class="tb-btn"><i class="bi bi-arrow-left"></i> Back to event</NuxtLink>
            <span class="tb-hint">Choose “Save as PDF” in the print window to keep a copy.</span>
            <button class="tb-btn primary" :disabled="!results" @click="print"><i class="bi bi-printer"></i> Print / Save as PDF</button>
        </div>

        <div v-if="loading" class="paper"><div class="muted">Preparing the report…</div></div>
        <div v-else-if="error" class="paper"><div class="muted">{{ error }}</div></div>

        <article v-else-if="results && event" class="paper">
            <header class="r-head">
                <div class="brand"><img src="/ballotify-logo.png" alt="" width="28" height="28" /> Ballotify</div>
                <span class="badge" :class="final ? 'final' : 'prov'">{{ final ? 'Final results' : 'Provisional: voting still open' }}</span>
            </header>

            <h1>{{ results.event.name }}</h1>
            <p class="sub">Official results report</p>

            <table class="details">
                <tbody>
                    <tr><th>Voting window</th><td>{{ formatWindow(results.event.starts_at, results.event.ends_at) }}</td></tr>
                    <tr><th>Voting style</th><td>{{ event.selection_mode === 'multiple' ? 'Multiple choice' : 'One choice per category' }}</td></tr>
                    <tr><th>Generated</th><td>{{ generatedAt }}</td></tr>
                    <tr><th>Reference</th><td class="mono">EV-{{ results.event.id }} · {{ event.link_token.slice(0, 8) }}</td></tr>
                </tbody>
            </table>

            <div class="stats">
                <div><span>Ballots cast</span><b>{{ formatNumber(results.stats.voted) }}</b></div>
                <div><span>Registered voters</span><b>{{ formatNumber(results.stats.total_voters) }}</b></div>
                <div><span>Turnout</span><b>{{ results.stats.total_voters ? percent(results.stats.voted, results.stats.total_voters) + '%' : '—' }}</b></div>
                <div><span>Changes after voting began</span><b>{{ changes }}</b></div>
            </div>

            <section v-for="cat in results.categories" :key="cat.id" class="cat">
                <div class="cat-head">
                    <h2>{{ cat.name }}</h2>
                    <span class="cat-total">{{ formatNumber(categoryTotal(cat)) }} vote{{ categoryTotal(cat) === 1 ? '' : 's' }}</span>
                </div>
                <p class="outcome">
                    <template v-if="!categoryWinners(cat).length">No votes cast.</template>
                    <template v-else-if="categoryWinners(cat).length > 1">Tied: {{ categoryWinners(cat).map(c => c.name).join(' & ') }}</template>
                    <template v-else>{{ final ? 'Winner' : 'Leading' }}: <b>{{ categoryWinners(cat)[0]!.name }}</b></template>
                </p>
                <table class="tally">
                    <thead><tr><th class="num">#</th><th>Candidate</th><th class="num">Votes</th><th class="num">Share</th></tr></thead>
                    <tbody>
                        <tr v-for="(c, i) in cat.candidates" :key="c.id" :class="{ top: categoryWinners(cat).includes(c) }">
                            <td class="num">{{ i + 1 }}</td>
                            <td>
                                <div class="cname">{{ c.name }}</div>
                                <div v-if="c.description" class="cdesc">{{ c.description }}</div>
                            </td>
                            <td class="num">{{ formatNumber(c.votes) }}</td>
                            <td class="num">{{ percent(c.votes, categoryTotal(cat)) }}%</td>
                        </tr>
                        <tr v-if="!cat.candidates.length"><td colspan="4" class="muted">No candidates</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="statement">
                <h2>Integrity statement</h2>
                <p>
                    This vote used one-time voter codes: each code could be used once, and only inside the voting window.
                    It was a secret ballot. Only a running total per candidate was stored, so no individual’s choices can be identified.
                    <template v-if="changes === 0">No changes were made to the ballot after voting began.</template>
                    <template v-else>{{ changes }} change{{ changes === 1 ? ' was' : 's were' }} made to the ballot after voting began (name, description or photo corrections); each is recorded in the event’s activity log.</template>
                </p>
            </section>

            <section class="signatures">
                <div v-for="role in ['Returning officer', 'Witness']" :key="role" class="sig">
                    <div class="sig-role">{{ role }}</div>
                    <div class="line"><span>Name</span></div>
                    <div class="line"><span>Signature</span></div>
                    <div class="line"><span>Date</span></div>
                </div>
            </section>

            <footer class="r-foot">Generated by Ballotify · {{ generatedAt }}</footer>
        </article>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, EventResults } from '~/types';

definePageMeta({ layout: false, middleware: ['auth'] });

const route = useRoute();
const id = String(route.params.id);
const event = ref<BallotEvent | null>(null);
const results = ref<EventResults | null>(null);
const loading = ref(true);
const error = ref('');
const generatedAt = new Date().toLocaleString(undefined, { dateStyle: 'long', timeStyle: 'short' });

const final = computed(() => results.value?.event.status === 'completed');
const changes = computed(() => results.value?.integrity?.changes_during_voting ?? 0);

useHead({ title: computed(() => (results.value ? `${results.value.event.name} · Results report` : 'Results report')) });

onMounted(async () => {
    try {
        const [e, r] = await Promise.all([adminApi.getEvent(id), adminApi.getResults(id)]);
        event.value = e.data;
        results.value = r.data;
    } catch (e) {
        error.value = apiStatus(e) === 404 ? 'This event was not found.' : apiErrorMessage(e, 'Could not load the results.');
    } finally {
        loading.value = false;
    }
});

function print() {
    window.print();
}
</script>

<style scoped>
/* A printed document: always light, whatever the app theme. */
.report-shell {
    min-height: 100vh; background: #eef2f6; color: #0f172a; padding: 1.5rem 1rem 3rem;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color-scheme: light;
}
.toolbar { max-width: 820px; margin: 0 auto 1rem; display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.tb-hint { flex-grow: 1; color: #64748b; font-size: .85rem; }
.tb-btn {
    display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: 10px; font-weight: 600; font-size: .9rem;
    border: 1px solid #d5dbe5; background: #fff; color: #0f172a; text-decoration: none; cursor: pointer;
}
.tb-btn.primary { background: #0e7c77; border-color: #0e7c77; color: #fff; }
.tb-btn:disabled { opacity: .6; cursor: not-allowed; }

.paper {
    max-width: 820px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 2.5rem 2.75rem;
    box-shadow: 0 20px 50px -25px rgba(15, 23, 42, .35);
}
@media (max-width: 575.98px) { .paper { padding: 1.5rem 1.25rem; } }
.muted { color: #64748b; }
.mono { font-family: ui-monospace, Consolas, monospace; }

.r-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.75rem; }
.brand { display: inline-flex; align-items: center; gap: .5rem; font-weight: 800; color: #0e7c77; }
.badge { font-size: .75rem; font-weight: 700; padding: .3rem .7rem; border-radius: 999px; }
.badge.final { background: #ecfdf3; color: #15803d; }
.badge.prov { background: #fffaeb; color: #92400e; }
h1 { font-size: 1.75rem; font-weight: 800; margin: 0; letter-spacing: -.01em; }
.sub { color: #64748b; margin: .25rem 0 1.5rem; }

.details { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: .9rem; }
.details th { text-align: left; font-weight: 600; color: #64748b; width: 180px; padding: .35rem 0; vertical-align: top; }
.details td { padding: .35rem 0; }

.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-bottom: 2rem; }
@media (max-width: 575.98px) { .stats { grid-template-columns: repeat(2, 1fr); } }
.stats div { border: 1px solid #e5e9f0; border-radius: 10px; padding: .75rem .9rem; }
.stats span { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
.stats b { font-size: 1.35rem; font-weight: 800; }

.cat { margin-bottom: 1.75rem; break-inside: avoid; }
.cat-head { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; border-bottom: 2px solid #0f172a; padding-bottom: .35rem; }
h2 { font-size: 1.1rem; font-weight: 800; margin: 0; }
.cat-total { color: #64748b; font-size: .85rem; white-space: nowrap; }
.outcome { margin: .6rem 0; font-size: .92rem; }
.tally { width: 100%; border-collapse: collapse; font-size: .9rem; }
.tally th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; padding: .4rem .5rem; border-bottom: 1px solid #e5e9f0; }
.tally td { padding: .5rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.tally .num { text-align: right; width: 70px; font-variant-numeric: tabular-nums; }
.tally th.num:first-child, .tally td.num:first-child { text-align: left; width: 36px; color: #64748b; }
.tally tr.top td { background: #ecf8f7; font-weight: 700; }
.cname { font-weight: 600; }
.cdesc { color: #64748b; font-size: .8rem; font-weight: 400; }

.statement { margin: 2rem 0; padding: 1rem 1.2rem; background: #f8fafc; border-radius: 10px; break-inside: avoid; }
.statement h2 { margin-bottom: .4rem; font-size: 1rem; }
.statement p { margin: 0; font-size: .88rem; line-height: 1.6; color: #334155; }

.signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; margin-top: 2.5rem; break-inside: avoid; }
@media (max-width: 575.98px) { .signatures { grid-template-columns: 1fr; } }
.sig-role { font-weight: 800; margin-bottom: 1rem; }
.line { border-bottom: 1px solid #94a3b8; height: 2.2rem; display: flex; align-items: flex-end; margin-bottom: .6rem; }
.line span { font-size: .72rem; color: #64748b; transform: translateY(1.1rem); }

.r-foot { margin-top: 3rem; text-align: center; font-size: .75rem; color: #94a3b8; }

@media print {
    @page { margin: 16mm 14mm; }
    .no-print { display: none !important; }
    .report-shell { background: #fff; padding: 0; min-height: 0; }
    .paper { box-shadow: none; border-radius: 0; padding: 0; max-width: none; }
    .tally tr.top td, .statement, .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
