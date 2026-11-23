<template>
    <div class="cert-page">
        <div class="toolbar no-print">
            <NuxtLink :to="`/verify/${serial}`" class="btn btn-ghost btn-sm"><i class="bi bi-patch-check"></i> Verification page</NuxtLink>
            <button class="btn btn-primary btn-sm" :disabled="!cert" @click="print"><i class="bi bi-printer"></i> Print / Save as PDF</button>
        </div>

        <div v-if="loading" class="sheet"><div class="skeleton mb-3" style="height: 40px"></div><div class="skeleton" style="height: 300px"></div></div>
        <div v-else-if="!cert" class="sheet text-center"><h1 class="h5 fw-800">Certificate not found</h1></div>

        <article v-else class="sheet">
            <header class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <AppLogo />
                <div class="text-end">
                    <div class="small-caps">Certificate of results</div>
                    <div class="mono fw-bold">{{ cert.serial }}</div>
                </div>
            </header>

            <h1 class="h2 fw-800 mb-1">{{ p.event.name }}</h1>
            <p class="text-muted mb-4">
                <template v-if="p.event.organiser">Organised by {{ p.event.organiser }} · </template>
                Voting {{ formatWindow(p.event.starts_at, p.event.ends_at) }}
            </p>

            <div class="stats mb-4">
                <template v-if="p.event.voting_mode === 'paid'">
                    <div><b>{{ formatNumber(p.stats.votes ?? 0) }}</b><span>votes</span></div>
                    <div><b>{{ formatNumber(p.stats.payments ?? 0) }}</b><span>payments</span></div>
                </template>
                <template v-else>
                    <div><b>{{ formatNumber(p.stats.ballots ?? 0) }}</b><span>ballots cast</span></div>
                    <div><b>{{ formatNumber(p.stats.voters ?? 0) }}</b><span>eligible voters</span></div>
                    <div><b>{{ p.stats.voters ? Math.round(((p.stats.ballots ?? 0) / p.stats.voters) * 1000) / 10 : 0 }}%</b><span>turnout</span></div>
                </template>
            </div>

            <section v-for="c in p.categories" :key="c.name" class="cat">
                <div class="d-flex justify-content-between align-items-baseline gap-2">
                    <h2 class="h6 fw-800 mb-2">{{ c.name }}</h2>
                    <span v-if="c.method === 'motion' && c.motion" class="small fw-bold" :class="c.motion.passed ? 'winner' : 'text-danger'">
                        <i class="bi" :class="c.motion.passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                        {{ !c.motion.decided ? 'No Yes or No votes' : c.motion.passed ? 'Passed' : 'Not passed' }}
                    </span>
                    <span v-else-if="c.winners.length" class="small fw-bold winner"><i class="bi bi-trophy-fill"></i> {{ c.winners.join(' & ') }}{{ c.winners.length > 1 ? ' (tie)' : '' }}</span>
                </div>
                <table class="w-100 lines">
                    <tbody>
                        <tr v-if="c.method === 'ranked'" class="ranked-head"><td></td><td class="text-end">First choices</td><td class="text-end">Final round</td></tr>
                        <tr v-for="cand in c.candidates" :key="cand.name" :class="{ won: c.winners.includes(cand.name) }">
                            <td>{{ cand.name }}</td>
                            <td class="text-end mono">{{ formatNumber(cand.votes) }}</td>
                            <td v-if="c.method === 'ranked'" class="text-end mono">{{ finalOf(c, cand.name) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="c.method === 'ranked'" class="small text-muted mt-1 mb-0">
                    Ranked choice, decided in {{ c.rounds }} round{{ c.rounds === 1 ? '' : 's' }}: the last-placed candidate was eliminated each round and their ballots moved to the next choice.
                </p>
            </section>

            <footer class="verify-box mt-4">
                <img v-if="qr" :src="qr" alt="QR code to verify this certificate" width="110" height="110" />
                <div class="small">
                    <div class="fw-800 text-ink mb-1">Verify this certificate</div>
                    <div class="text-muted">Scan the code or visit <span class="mono text-break">{{ cert.verify_url }}</span></div>
                    <div class="text-muted mt-1">Issued {{ formatDate(cert.issued_at, true) }} · Signature <span class="mono">{{ cert.signature.slice(0, 16) }}…</span></div>
                </div>
            </footer>
        </article>
    </div>
</template>

<script setup lang="ts">
import QRCode from 'qrcode';
import { publicApi } from '~/api/endpoints/public';
import type { CertificatePayload, ResultCertificateInfo } from '~/types';

const route = useRoute();
const serial = String(route.params.serial ?? '');
const cert = ref<ResultCertificateInfo | null>(null);
const loading = ref(true);
const qr = ref('');
const p = computed(() => cert.value!.payload);

useHead({ title: computed(() => (cert.value ? `Certificate · ${cert.value.payload.event.name}` : 'Certificate · Ballotify')) });

onMounted(async () => {
    try {
        cert.value = (await publicApi.verifyCertificate(serial)).data;
        qr.value = await QRCode.toDataURL(cert.value.verify_url, { width: 240, margin: 1, color: { dark: '#0a1222', light: '#ffffff' } });
    } catch { /* not found */ } finally { loading.value = false; }
});

function print() { window.print(); }

/** Ranked choice: a candidate's votes in the deciding round ('–' if eliminated earlier). */
function finalOf(c: CertificatePayload['categories'][number], name: string): string {
    const row = c.final_round?.find(r => r.name === name);
    return row ? formatNumber(row.votes) : '–';
}
</script>

<style scoped>
.ranked-head td { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); font-weight: 700; padding-bottom: .2rem; }
.cert-page { min-height: 100vh; background: var(--bg); padding: 1.5rem 1rem 3rem; }
.toolbar { max-width: 780px; margin: 0 auto 1rem; display: flex; justify-content: space-between; gap: .5rem; }
.sheet { max-width: 780px; margin: 0 auto; background: var(--surface); border: 1px solid var(--line); border-radius: 20px; padding: 2.5rem; }
.stats { display: flex; gap: 2rem; flex-wrap: wrap; padding: 1rem 1.25rem; border-radius: 14px; background: var(--surface-2); }
.stats div { display: flex; flex-direction: column; }
.stats b { font-size: 1.4rem; font-weight: 800; color: var(--ink); }
.stats span { font-size: .8rem; color: var(--muted); }
.cat { margin-bottom: 1.5rem; break-inside: avoid; }
.lines td { padding: .45rem 0; border-bottom: 1px solid var(--line); }
.lines tr.won td { font-weight: 800; color: var(--ink); }
.winner { color: #b45309; }
.verify-box { display: flex; gap: 1.25rem; align-items: center; padding: 1rem; border: 1.5px dashed var(--line-strong); border-radius: 16px; break-inside: avoid; }
@media print {
    .cert-page { background: #fff; padding: 0; }
    .sheet { border: 0; padding: 0; }
}
</style>
