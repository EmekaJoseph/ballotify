<template>
    <div class="report-shell">
        <div class="toolbar no-print">
            <NuxtLink :to="`/dashboard/events/${id}?tab=results`" class="tb-btn"><i class="bi bi-arrow-left"></i> Back to event</NuxtLink>
            <div class="tb-options">
                <label v-if="canSeeRegister"><input v-model="opts.register" type="checkbox" /> {{ paid ? 'Payments summary' : 'Voter register' }}</label>
                <label><input v-model="opts.signatures" type="checkbox" /> Signature lines</label>
            </div>
            <button class="tb-btn primary" :disabled="!results" @click="print"><i class="bi bi-file-earmark-pdf"></i> Save as PDF / Print</button>
        </div>
        <p class="tb-hint no-print">In the print window, choose <b>Save as PDF</b> as the printer. Turn on “Background graphics” for the coloured bars.</p>

        <div v-if="loading" class="paper"><div class="muted">Preparing the report…</div></div>
        <div v-else-if="error" class="paper"><div class="muted">{{ error }}</div></div>

        <article v-else-if="results && event" class="paper">
            <!-- ===================== Page 1: summary ===================== -->
            <header class="r-head">
                <div class="brand">
                    <img v-if="results.event.brand?.logo_url" :src="results.event.brand.logo_url" alt="" class="org-logo" />
                    <template v-else><BrandMark :size="28" tone="on-light" /> Ballotify</template>
                </div>
                <span class="badge" :class="final ? 'final' : 'prov'">{{ final ? 'Final results' : 'Provisional: voting still open' }}</span>
            </header>

            <div class="title-block">
                <div class="kicker">Official results report</div>
                <h1>{{ results.event.name }}</h1>
                <p class="sub">
                    {{ formatWindow(results.event.starts_at, results.event.ends_at) }} ·
                    {{ paid ? `Paid public voting (₦${formatNumber(event.vote_price_naira ?? 0)} per vote)` : 'Secret ballot with one-time codes' }}
                </p>
            </div>

            <div class="stats">
                <template v-if="paid">
                    <div><span>Votes</span><b>{{ formatNumber(totalVotes) }}</b></div>
                    <div><span>Payments</span><b>{{ formatNumber(results.stats.voted) }}</b></div>
                </template>
                <template v-else>
                    <div><span>Ballots cast</span><b>{{ formatNumber(results.stats.voted) }}</b></div>
                    <div><span>Registered voters</span><b>{{ formatNumber(results.stats.total_voters) }}</b></div>
                    <div class="accent"><span>Turnout</span><b>{{ turnout }}</b></div>
                </template>
                <div><span>Categories</span><b>{{ formatNumber(results.categories.length) }}</b></div>
                <div><span>Candidates</span><b>{{ formatNumber(results.stats.total_candidates) }}</b></div>
                <div v-if="!paid"><span>Votes counted</span><b>{{ formatNumber(totalVotes) }}</b></div>
            </div>

            <!-- Winners at a glance -->
            <section class="block">
                <h2>{{ final ? 'Winners' : 'Currently leading' }}</h2>
                <table class="winners">
                    <thead><tr><th>Category</th><th>{{ final ? 'Winner' : 'Leading' }}</th><th class="num">Votes</th><th class="num">Share</th><th class="num">Margin</th></tr></thead>
                    <tbody>
                        <tr v-for="cat in results.categories" :key="cat.id">
                            <td class="cat-name">{{ cat.name }}</td>
                            <td>
                                <template v-if="!winnersOf(cat).length"><span class="muted">No votes</span></template>
                                <template v-else>
                                    <span class="trophy">★</span> <b>{{ winnersOf(cat).map(c => c.name).join(' & ') }}</b>
                                    <span v-if="winnersOf(cat).length > 1" class="tie">tie</span>
                                </template>
                            </td>
                            <td class="num">{{ winnersOf(cat).length ? formatNumber(decidingVotes(cat, winnersOf(cat)[0]!)) : '–' }}</td>
                            <td class="num">{{ winnersOf(cat).length ? decidingShare(cat, winnersOf(cat)[0]!) + '%' : '–' }}</td>
                            <td class="num">{{ margin(cat) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section v-if="hasTimeline" class="block chart-block">
                <h2>{{ paid ? 'Votes over time' : 'Turnout over time' }}</h2>
                <TurnoutChart :timeline="results.timeline" :unit="paid ? 'votes' : 'ballots'" :height="170" />
            </section>

            <section class="meta-grid">
                <table class="details">
                    <tbody>
                        <tr><th>Organiser</th><td>{{ organiser || '–' }}</td></tr>
                        <tr><th>Results visibility</th><td>{{ visibilityLabel }}</td></tr>
                        <tr><th>Changes after voting began</th><td>{{ changes }}</td></tr>
                        <tr><th>Generated</th><td>{{ generatedAt }}</td></tr>
                        <tr><th>Reference</th><td class="mono">EV-{{ results.event.id }} · {{ event.link_token.slice(0, 8) }}</td></tr>
                    </tbody>
                </table>
                <div v-if="certificate" class="cert">
                    <img v-if="certQr" :src="certQr" alt="QR code to verify these results" width="96" height="96" />
                    <div>
                        <div class="cert-title">Certified results</div>
                        <div class="mono">{{ certificate.serial }}</div>
                        <div class="muted small">Scan to verify, or visit<br /><span class="mono">{{ certificate.verify_url }}</span></div>
                    </div>
                </div>
            </section>

            <!-- ===================== Detailed results ===================== -->
            <div class="page-break"></div>
            <h2 class="section-title">Detailed results</h2>
            <section v-for="cat in results.categories" :key="cat.id" class="cat">
                <div class="cat-head">
                    <h3>{{ cat.name }}</h3>
                    <span class="cat-total">{{ methodName(cat) }} · {{ formatNumber(categoryTotal(cat)) }} {{ isRanked(cat) ? 'first choices' : `vote${categoryTotal(cat) === 1 ? '' : 's'}` }} · {{ cat.candidates.length }} candidate{{ cat.candidates.length === 1 ? '' : 's' }}</span>
                </div>
                <table class="tally">
                    <thead><tr><th class="rank">#</th><th>Candidate</th><th class="bar-col">Share</th><th class="num">Votes</th><th class="num">%</th></tr></thead>
                    <tbody>
                        <tr v-for="(c, i) in cat.candidates" :key="c.id" :class="{ top: winnersOf(cat).includes(c) }">
                            <td class="rank">{{ i + 1 }}</td>
                            <td>
                                <div class="cname">{{ c.name }}<span v-if="winnersOf(cat).includes(c)" class="trophy"> ★</span></div>
                                <div v-if="c.description" class="cdesc">{{ c.description }}</div>
                            </td>
                            <td class="bar-col"><div class="bar"><span :style="{ width: share(c.votes, cat) + '%' }"></span></div></td>
                            <td class="num">{{ formatNumber(c.votes) }}</td>
                            <td class="num">{{ share(c.votes, cat) }}%</td>
                        </tr>
                        <tr v-if="!cat.candidates.length"><td colspan="5" class="muted">No candidates</td></tr>
                    </tbody>
                </table>
                <RoundsTable v-if="isRanked(cat)" :category="cat" class="report-rounds" />
                <p v-else-if="isApproval(cat) && !paid && categoryTotal(cat)" class="muted small mt-1 mb-0">Approval vote: each voter could pick several, so shares are of voters and can add up to more than 100%.</p>
            </section>

            <section class="statement">
                <h2>Integrity statement</h2>
                <p v-if="paid">
                    This was public paid voting: each vote was a payment confirmed by Paystack before it was counted, and payments
                    received after voting closed were not counted. Anyone who paid can check their payment reference on the results page.
                </p>
                <p v-else>
                    This vote used one-time voter codes: each code could be used once, and only inside the voting window.
                    It was a secret ballot. Only a running total per candidate was stored, so no individual’s choices can be identified;
                    voters received a receipt to check that their ballot was counted.
                </p>
                <p>
                    <template v-if="changes === 0">No changes were made to the ballot after voting began.</template>
                    <template v-else>{{ changes }} change{{ changes === 1 ? ' was' : 's were' }} made to the ballot after voting began (name, description or photo corrections); each is recorded in the event’s activity log.</template>
                    <template v-if="certificate"> These results were certified as {{ certificate.serial }} on {{ formatDate(certificate.issued_at, true) }}.</template>
                </p>
            </section>

            <section v-if="opts.signatures" class="signatures">
                <div v-for="role in ['Returning officer', 'Witness']" :key="role" class="sig">
                    <div class="sig-role">{{ role }}</div>
                    <div class="line"><span>Name</span></div>
                    <div class="line"><span>Signature</span></div>
                    <div class="line"><span>Date</span></div>
                </div>
            </section>

            <!-- ===================== Appendix ===================== -->
            <template v-if="opts.register && canSeeRegister">
                <div class="page-break"></div>
                <template v-if="!paid">
                    <h2 class="section-title">Appendix: voter register</h2>
                    <p class="muted small mb">
                        Who voted, and when. It never shows how anyone voted: Ballotify doesn’t record that.
                        {{ formatNumber(votedCount) }} of {{ formatNumber(voters.length) }} voters voted.
                    </p>
                    <div class="table-wrap"><table class="register">
                        <thead><tr><th>#</th><th>Voter</th><th v-if="hasIds">{{ event.member_id_label || 'ID' }}</th><th>Status</th><th>Voted at</th></tr></thead>
                        <tbody>
                            <tr v-for="(v, i) in register" :key="v.id">
                                <td class="muted">{{ i + 1 }}</td>
                                <td>{{ v.name || 'Anonymous code' }}</td>
                                <td v-if="hasIds" class="mono">{{ v.member_id || '' }}</td>
                                <td><span class="status" :class="v.used_at ? 'yes' : 'no'">{{ v.used_at ? 'Voted' : 'Did not vote' }}</span></td>
                                <td>{{ v.used_at ? formatDate(v.used_at, true) : '' }}</td>
                            </tr>
                        </tbody>
                    </table></div>
                </template>
                <template v-else-if="earnings">
                    <h2 class="section-title">Appendix: payments summary</h2>
                    <div class="stats compact">
                        <div><span>Payments</span><b>{{ formatNumber(earnings.payments) }}</b></div>
                        <div><span>Votes sold</span><b>{{ formatNumber(earnings.votes_sold) }}</b></div>
                        <div><span>Raised</span><b>{{ formatNaira(earnings.gross_naira) }}</b></div>
                        <div><span>Platform fee ({{ earnings.fee_percent }}%)</span><b>{{ formatNaira(earnings.fee_naira) }}</b></div>
                        <div class="accent"><span>Organiser’s share</span><b>{{ formatNaira(earnings.net_naira) }}</b></div>
                    </div>
                    <div class="table-wrap"><table class="register">
                        <thead><tr><th>Voter</th><th>For</th><th class="num">Votes</th><th class="num">Paid</th><th>When</th></tr></thead>
                        <tbody>
                            <tr v-for="(r, i) in earnings.recent" :key="i">
                                <td>{{ r.buyer }}</td><td>{{ r.candidate }}</td>
                                <td class="num">{{ formatNumber(r.quantity) }}</td><td class="num">{{ formatNaira(r.amount_naira) }}</td>
                                <td>{{ r.paid_at ? formatDate(r.paid_at, true) : '' }}</td>
                            </tr>
                        </tbody>
                    </table></div>
                    <p v-if="earnings.payments > earnings.recent.length" class="muted small">Showing the latest {{ earnings.recent.length }} payments.</p>
                </template>
            </template>

            <footer class="r-foot">{{ results.event.name }} · Generated by Ballotify · {{ generatedAt }}</footer>
        </article>
    </div>
</template>

<script setup lang="ts">
import QRCode from 'qrcode';
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, Earnings, EventResults, ResultCandidate, ResultCategory, ResultCertificateInfo, Voter } from '~/types';

definePageMeta({ layout: false, middleware: ['auth'] });

const route = useRoute();
const id = String(route.params.id);
const event = ref<BallotEvent | null>(null);
const results = ref<EventResults | null>(null);
const voters = ref<Voter[]>([]);
const earnings = ref<Earnings | null>(null);
const certificate = ref<ResultCertificateInfo | null>(null);
const certQr = ref('');
const loading = ref(true);
const error = ref('');
const generatedAt = new Date().toLocaleString(undefined, { dateStyle: 'long', timeStyle: 'short' });
const opts = reactive({ register: true, signatures: true });

const paid = computed(() => event.value?.voting_mode === 'paid');
const final = computed(() => results.value?.event.status === 'completed');
const changes = computed(() => results.value?.integrity?.changes_during_voting ?? 0);
const organiser = ref('');
/** The register needs voter access (paid: earnings need manage access). */
const canSeeRegister = computed(() => (paid.value ? !!earnings.value : voters.value.length > 0));

const categoryTotal = (cat: ResultCategory) => cat.candidates.reduce((s, c) => s + c.votes, 0);
const totalVotes = computed(() => (results.value?.categories ?? []).reduce((s, c) => s + categoryTotal(c), 0));
const share = (votes: number, cat: ResultCategory) => categoryShare(votes, cat);
const winnersOf = (cat: ResultCategory): ResultCandidate[] => categoryWinners(cat);
/** Ranked choice is decided by the final round; other methods by the votes. */
const decidingVotes = (cat: ResultCategory, c: ResultCandidate) => (isRanked(cat) ? finalVotes(cat, c.id) : c.votes);
const decidingShare = (cat: ResultCategory, c: ResultCandidate) =>
    (isRanked(cat) ? percent(finalVotes(cat, c.id), finalRound(cat)?.continuing ?? 0) : share(c.votes, cat));
function margin(cat: ResultCategory): string {
    const w = winnersOf(cat);
    if (w.length !== 1) return '–';
    const runnerUp = cat.candidates.filter(c => c !== w[0]).reduce((m, c) => Math.max(m, decidingVotes(cat, c)), 0);
    return `+${formatNumber(decidingVotes(cat, w[0]!) - runnerUp)}`;
}
const turnout = computed(() => {
    const s = results.value?.stats;
    return s?.total_voters ? `${Math.round((s.voted / s.total_voters) * 1000) / 10}%` : '–';
});
const hasTimeline = computed(() => (results.value?.timeline?.points.length ?? 0) > 1);
const visibilityLabel = computed(() => ({
    live: 'Public, live as votes arrived', after_close: 'Public after voting closed', private: 'Organiser only',
}[results.value?.event.results_visibility ?? 'after_close'] ?? ''));

// Voted first, then by name.
const register = computed(() => [...voters.value].sort((a, b) =>
    (a.used_at ? 0 : 1) - (b.used_at ? 0 : 1) || (a.name ?? '~').localeCompare(b.name ?? '~', undefined, { numeric: true, sensitivity: 'base' })));
const votedCount = computed(() => voters.value.filter(v => v.used_at).length);
const hasIds = computed(() => voters.value.some(v => v.member_id));

useHead({ title: computed(() => (results.value ? `${results.value.event.name} · Results report` : 'Results report')) });

// A printed document is always light, whatever the app theme (the chart uses theme colours).
let previousTheme: string | null = null;
onMounted(async () => {
    previousTheme = document.documentElement.getAttribute('data-theme');
    document.documentElement.setAttribute('data-theme', 'light');
    try {
        const [e, r] = await Promise.all([adminApi.getEvent(id), adminApi.getResults(id)]);
        event.value = e.data;
        results.value = r.data;
        organiser.value = e.data.owner_name ?? '';
        // Optional parts: each depends on the viewer's role, so failures just leave them out.
        await Promise.allSettled([
            e.data.voting_mode === 'paid'
                ? adminApi.getEarnings(id).then(x => { earnings.value = x.data; })
                : adminApi.getVoters(id).then(x => {
                    voters.value = x.data;
                    // Before anyone votes the register is just a list of "did not vote": off unless asked for.
                    opts.register = x.data.some(v => v.used_at);
                }),
            adminApi.getCertificates(id).then(async (x) => {
                certificate.value = x.data[0] ?? null;
                if (certificate.value) certQr.value = await QRCode.toDataURL(certificate.value.verify_url, { width: 200, margin: 1 });
            }),
        ]);
    } catch (e) {
        error.value = apiStatus(e) === 404 ? 'This event was not found.' : apiErrorMessage(e, 'Could not load the results.');
    } finally {
        loading.value = false;
    }
});
onBeforeUnmount(() => {
    if (previousTheme) document.documentElement.setAttribute('data-theme', previousTheme);
    else document.documentElement.removeAttribute('data-theme');
});

function print() {
    window.print();
}
</script>

<style scoped>
.report-rounds { margin-top: .5rem; }
.report-rounds :deep(.table-rounds) { font-size: .8rem; }
/* A printed document: always light, whatever the app theme. */
.report-shell {
    min-height: 100vh; background: #eef2f6; color: #0f172a; padding: 1.5rem 1rem 3rem;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color-scheme: light;
}
.toolbar { max-width: 860px; margin: 0 auto .5rem; display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.tb-options { flex-grow: 1; display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; font-size: .88rem; font-weight: 600; color: #334155; }
.tb-options label { display: inline-flex; gap: .4rem; align-items: center; cursor: pointer; }
.tb-hint { max-width: 860px; margin: 0 auto 1rem; color: #64748b; font-size: .82rem; text-align: center; }
.tb-btn {
    display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: 10px; font-weight: 600; font-size: .9rem;
    border: 1px solid #d5dbe5; background: #fff; color: #0f172a; text-decoration: none; cursor: pointer;
}
.tb-btn.primary { background: #0e7c77; border-color: #0e7c77; color: #fff; }
.tb-btn:disabled { opacity: .6; cursor: not-allowed; }

.paper {
    max-width: 860px; margin: 0 auto; background: #fff; border-radius: 14px; padding: 2.75rem 3rem;
    box-shadow: 0 20px 50px -25px rgba(15, 23, 42, .35);
}
@media (max-width: 575.98px) { .paper { padding: 1.5rem 1.1rem; } }
.muted { color: #64748b; }
.small { font-size: .82rem; }
.mb { margin-bottom: 1rem; }
.mono { font-family: ui-monospace, Consolas, monospace; }

.r-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-bottom: 1.25rem; border-bottom: 3px solid #0e7c77; margin-bottom: 1.75rem; }
.brand { display: inline-flex; align-items: center; gap: .5rem; font-weight: 800; color: #0e7c77; font-size: 1.1rem; }
.org-logo { max-height: 44px; max-width: 200px; object-fit: contain; }
.badge { font-size: .75rem; font-weight: 700; padding: .3rem .75rem; border-radius: 999px; }
.badge.final { background: #ecfdf3; color: #15803d; }
.badge.prov { background: #fffaeb; color: #92400e; }

.title-block { margin-bottom: 1.5rem; }
.kicker { text-transform: uppercase; letter-spacing: .12em; font-size: .72rem; font-weight: 800; color: #0e7c77; }
h1 { font-size: 2rem; font-weight: 800; margin: .2rem 0 .25rem; letter-spacing: -.02em; line-height: 1.15; }
.sub { color: #64748b; margin: 0; }

.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .65rem; margin-bottom: 2rem; }
.stats div { border: 1px solid #e5e9f0; border-radius: 12px; padding: .75rem .9rem; }
.stats div.accent { background: #ecf8f7; border-color: #bfe8e4; }
.stats span { display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
.stats b { font-size: 1.45rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.stats.compact { margin-bottom: 1.25rem; }

.block { margin-bottom: 1.75rem; break-inside: avoid; }
h2 { font-size: 1.05rem; font-weight: 800; margin: 0 0 .7rem; }
.section-title { font-size: 1.3rem; margin-bottom: 1rem; }
h3 { font-size: 1.05rem; font-weight: 800; margin: 0; }

.winners, .tally, .register { width: 100%; border-collapse: collapse; font-size: .88rem; }
.winners th, .tally th, .register th { text-align: left; font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; padding: .45rem .5rem; border-bottom: 1.5px solid #cbd5e1; }
.winners td, .register td { padding: .5rem; border-bottom: 1px solid #eef2f6; vertical-align: top; }
.winners .cat-name { font-weight: 700; color: #334155; }
.num { text-align: right !important; font-variant-numeric: tabular-nums; white-space: nowrap; }
.trophy { color: #d97706; }
.tie { font-size: .7rem; font-weight: 800; background: #fffaeb; color: #92400e; padding: .05rem .4rem; border-radius: 6px; margin-left: .25rem; }

.chart-block { padding: 1rem 1.1rem .5rem; border: 1px solid #e5e9f0; border-radius: 12px; }

.meta-grid { display: grid; grid-template-columns: 1fr auto; gap: 1.5rem; align-items: start; break-inside: avoid; }
@media (max-width: 575.98px) { .meta-grid { grid-template-columns: 1fr; } }
.details { width: 100%; border-collapse: collapse; font-size: .86rem; }
.details th { text-align: left; font-weight: 600; color: #64748b; width: 200px; padding: .3rem 0; vertical-align: top; }
.details td { padding: .3rem 0; }
.cert { display: flex; gap: .8rem; align-items: center; padding: .75rem; border: 1.5px dashed #94a3b8; border-radius: 12px; max-width: 320px; }
.cert-title { font-weight: 800; color: #15803d; }
.cert .small span { word-break: break-all; }

.page-break { break-after: page; height: 0; }
@media screen { .page-break { margin: 2.25rem 0; border-top: 1px dashed #cbd5e1; } }

.cat { margin-bottom: 1.6rem; break-inside: avoid; }
.cat-head { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; border-bottom: 2px solid #0f172a; padding-bottom: .35rem; margin-bottom: .2rem; }
.cat-total { color: #64748b; font-size: .82rem; white-space: nowrap; }
.tally td { padding: .5rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.tally .rank { width: 32px; color: #64748b; }
.tally .bar-col { width: 32%; }
.bar { height: 9px; border-radius: 5px; background: #eef2f6; overflow: hidden; }
.bar span { display: block; height: 100%; border-radius: 5px; background: linear-gradient(90deg, #0e7c77, #14b8a6); }
.tally tr.top td { background: #f0fbf9; }
.tally tr.top .bar span { background: linear-gradient(90deg, #d97706, #fbbf24); }
.tally tr.top .cname { font-weight: 800; }
.cname { font-weight: 600; }
.cdesc { color: #64748b; font-size: .78rem; font-weight: 400; }

.table-wrap { overflow-x: auto; }
@media print { .table-wrap { overflow: visible; } }
.register td { font-size: .82rem; padding: .35rem .5rem; }
.register thead { display: table-header-group; } /* repeat the header on each printed page */
.register tr { break-inside: avoid; }
.status { font-size: .72rem; font-weight: 700; padding: .1rem .45rem; border-radius: 6px; }
.status.yes { background: #ecfdf3; color: #15803d; }
.status.no { background: #f1f5f9; color: #64748b; }

.statement { margin: 2rem 0; padding: 1rem 1.2rem; background: #f8fafc; border-left: 4px solid #0e7c77; border-radius: 8px; break-inside: avoid; }
.statement h2 { margin-bottom: .4rem; font-size: 1rem; }
.statement p { margin: 0 0 .4rem; font-size: .86rem; line-height: 1.6; color: #334155; }

.signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; margin-top: 2.5rem; break-inside: avoid; }
@media (max-width: 575.98px) { .signatures { grid-template-columns: 1fr; } }
.sig-role { font-weight: 800; margin-bottom: 1rem; }
.line { border-bottom: 1px solid #94a3b8; height: 2.2rem; display: flex; align-items: flex-end; margin-bottom: .6rem; }
.line span { font-size: .72rem; color: #64748b; transform: translateY(1.1rem); }

.r-foot { margin-top: 3rem; text-align: center; font-size: .75rem; color: #94a3b8; }

@media print {
    @page { size: A4; margin: 14mm 13mm; }
    .no-print { display: none !important; }
    .report-shell { background: #fff; padding: 0; min-height: 0; }
    .paper { box-shadow: none; border-radius: 0; padding: 0; max-width: none; }
    .paper * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    /* All the headline numbers on one row across the A4 page. */
    .stats { grid-template-columns: none; grid-auto-flow: column; grid-auto-columns: 1fr; gap: .5rem; }
    .stats div { padding: .6rem .7rem; }
    .stats span { font-size: .6rem; }
    .stats b { font-size: 1.25rem; }
}
</style>
