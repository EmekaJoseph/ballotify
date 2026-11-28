<template>
    <div>
        <div class="mb-3">
            <div class="small-caps">Results verification</div>
            <h1 class="h3 fw-800 mb-1">Check a results certificate</h1>
            <p class="text-muted mb-0">Enter the certificate number (it starts with BLT-), or scan the QR code on the certificate.</p>
        </div>

        <form class="card-b pad mb-3 d-flex gap-2" novalidate @submit.prevent="lookup">
            <input v-model.trim="input" class="form-control form-control-lg mono text-uppercase" placeholder="BLT-XXXX-XXXX" maxlength="24" aria-label="Certificate number" />
            <button class="btn btn-primary btn-lg" :disabled="loading || input.length < 6">
                <span v-if="loading" class="spinner-border spinner-border-sm"></span><span v-else>Check</span>
            </button>
        </form>

        <template v-if="cert">
            <div class="verdict mb-3" :class="verdict">
                <div>
                    <div class="fw-800">{{ verdict === 'ok' ? 'Genuine certificate' : verdict === 'warn' ? 'Genuine, but results changed since' : 'Not valid' }}</div>
                    <div class="small">{{ cert.message }}</div>
                </div>
            </div>
            <div class="card-b pad">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
                    <div>
                        <h2 class="h5 fw-800 mb-1 text-break">{{ cert.payload.event.name }}</h2>
                        <div class="small text-muted">Certificate <span class="mono">{{ cert.serial }}</span> · issued {{ formatDate(cert.issued_at, true) }}</div>
                    </div>
                    <NuxtLink :to="`/certificate/${cert.serial}`" class="btn btn-ghost btn-sm">View certificate</NuxtLink>
                </div>
                <div v-for="c in cert.payload.categories" :key="c.name" class="py-2 border-top d-flex justify-content-between gap-2 flex-wrap">
                    <span class="fw-semibold text-ink">{{ c.name }}</span>
                    <span class="small">{{ c.winners.join(' & ') || 'No votes' }}</span>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { ResultCertificateInfo } from '~/types';

definePageMeta({ layout: 'ballot' });
useBallotModeFor('other');
useHead({ title: 'Verify results · Ballotify' });

const route = useRoute();
const input = ref(String(route.params.serial ?? ''));
const cert = ref<ResultCertificateInfo | null>(null);
const loading = ref(false);
const { alert } = useAlert();

const verdict = computed(() => (!cert.value?.valid ? 'bad' : cert.value.matches_current === false ? 'warn' : 'ok'));

async function lookup() {
    loading.value = true;
    cert.value = null;
    try {
        cert.value = (await publicApi.verifyCertificate(input.value)).data;
        if (route.params.serial !== cert.value.serial) navigateTo(`/verify/${cert.value.serial}`, { replace: true });
    } catch (e) {
        await alert({ title: 'Certificate not found', message: apiStatus(e) === 404 ? 'No certificate with this number exists. Check it and try again.' : apiErrorMessage(e) });
    } finally {
        loading.value = false;
    }
}
onMounted(() => { if (input.value) lookup(); });
</script>

<style scoped>
.verdict { display: flex; gap: .8rem; align-items: flex-start; padding: 1rem 1.1rem; border-radius: 16px; }
.verdict > .bi { font-size: 1.5rem; }
.verdict.ok { background: var(--success-50); color: var(--success); }
.verdict.warn { background: var(--warning-50); color: var(--warning-text, var(--warning)); }
.verdict.bad { background: var(--danger-50); color: var(--danger); }
.verdict .small { color: var(--ink-2); }
</style>
