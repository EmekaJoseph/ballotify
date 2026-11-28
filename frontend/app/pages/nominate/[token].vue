<template>
    <div>
        <div v-if="loading" class="card-b pad"><div class="skeleton mb-3" style="height: 30px; width: 60%"></div><div class="skeleton" style="height: 160px"></div></div>
        <div v-else-if="notFound" class="card-b text-center px-4 py-5">
            <h1 class="h4 fw-800 mb-2">{{ t('nom.notFound') }}</h1>
            <p class="text-muted mb-0">{{ t('nom.checkLink') }}</p>
        </div>

        <template v-else-if="form">
            <div class="intro card-b overflow-hidden mb-3">
                <div class="intro-band">
                    <img v-if="form.brand?.logo_url" :src="form.brand.logo_url" alt="" class="brand-logo mb-3" />
                    <span class="pill" :class="form.open ? 'pill-live' : ''" :style="form.open ? '' : 'background: rgba(255,255,255,.12); color: #fff'">
                        <span class="dot"></span>{{ form.open ? t('nom.open') : t('nom.closed') }}
                    </span>
                    <h1 class="h2 fw-800 text-white mt-3 mb-2 text-break">{{ form.name }}</h1>
                    <p class="intro-text mb-0">{{ t('nom.intro') }}</p>
                </div>
                <div v-if="form.ends_at || form.voting_starts_at" class="d-flex flex-wrap gap-3 px-4 py-3 small text-muted">
                    <span v-if="form.ends_at && form.open">{{ t('nom.closes', { date: formatDate(form.ends_at, true) }) }}</span>
                    <span v-if="form.voting_starts_at">{{ t('nom.votingOpens', { date: formatDate(form.voting_starts_at, true) }) }}</span>
                </div>
            </div>

            <div v-if="!form.open" class="card-b text-center px-4 py-5">
                <h2 class="h5 fw-800 mb-2">{{ t('nom.closedTitle') }}</h2>
                <p class="text-muted mb-3">{{ t('nom.closedText') }}</p>
                <NuxtLink :to="`/vote/${token}`" class="btn btn-primary">{{ t('nom.goVote') }}</NuxtLink>
            </div>

            <!-- Sent: what went through, what didn't (and why) -->
            <div v-else-if="result" class="card-b pad fade-up">
                <div class="text-center">
                    <h2 class="h5 fw-800 mb-2">{{ result.accepted.length ? t('nom.received') : t('nom.notSent') }}</h2>
                    <p class="text-muted mb-4">{{ result.message }}</p>
                </div>

                <div v-if="result.accepted.length" class="sent mb-3">
                    <div class="small fw-bold text-muted mb-2">{{ t('nom.yours') }}</div>
                    <ul class="list-unstyled mb-0">
                        <li v-for="g in grouped(result.accepted)" :key="g.category" class="sent-row">
                            <span class="min-w-0"><b class="text-ink">{{ g.category }}:</b> {{ g.names.join(', ') }}</span>
                        </li>
                    </ul>
                </div>
                <div v-if="result.refused.length" class="sent refused mb-3">
                    <div class="small fw-bold mb-2">{{ t('nom.notSentList') }}</div>
                    <ul class="list-unstyled mb-0">
                        <li v-for="(r, i) in result.refused" :key="i" class="sent-row">
                            <span class="min-w-0"><b class="text-ink">{{ r.name }}</b><template v-if="r.category"> ({{ r.category }})</template>: {{ r.reason }}</span>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-primary w-100" @click="startOver">{{ t('nom.nominateMore') }}</button>
            </div>

            <!-- One form: every category, one or more names each, sent together -->
            <form v-else novalidate @submit.prevent="submit">
                <p class="small text-muted mb-3">{{ t('nom.introAll') }}</p>

                <section v-for="cat in form.categories" :key="cat.id" class="card-b pad mb-3 cat">
                    <h2 class="h6 fw-800 mb-3 text-break">{{ cat.name }}</h2>
                    <div v-for="(row, i) in entries[cat.id]" :key="row.key" class="name-row" :class="{ 'mt-3': i > 0 }">
                        <div class="name-fields">
                            <AppCombobox :id="`nom-${cat.id}-${row.key}`" v-model="row.name" :options="cat.suggestions" :maxlength="120"
                                :placeholder="t('nom.fullName')" :toggle-label="t('nom.showSuggestions')" :aria-label="`${cat.name}: ${t('nom.fullName')}`" />
                            <input v-model="row.reason" class="form-control reason" maxlength="280" :placeholder="`${t('nom.why')} ${t('common.optional')}`"
                                :aria-label="`${t('nom.why')} ${t('common.optional')}`" />
                        </div>
                        <button v-if="entries[cat.id]!.length > 1" type="button" class="btn btn-ghost btn-icon btn-sm flex-shrink-0" :title="t('nom.removeName')"
                            :aria-label="t('nom.removeName')" @click="removeRow(cat.id, i)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <div v-if="isRepeat(cat.id, i)" class="field-error w-100 mt-1">{{ t('nom.repeatName') }}</div>
                    </div>
                    <div v-if="cat.suggestions.length" class="form-text mt-2">{{ t('nom.pickOrType') }}</div>
                    <button v-if="entries[cat.id]!.length < perCategory" type="button" class="btn btn-link btn-sm px-0 mt-2" @click="addRow(cat.id)">
                        {{ t('nom.addName') }}
                    </button>
                </section>

                <div class="card-b pad send-bar">
                    <HumanCheck ref="human" v-model="captcha" class="mb-3" />
                    <button class="btn btn-primary btn-lg w-100" :disabled="busy || !toSend.length">
                        <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                        {{ toSend.length ? tn('nom.sendN', toSend.length) : t('nom.fillOne') }}
                    </button>
                    <p class="small text-muted text-center mt-3 mb-0">{{ t('nom.organiserDecides') }}</p>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { NominationForm, NominationResult } from '~/types';

definePageMeta({ layout: 'ballot' });
useBallotModeFor('nominate');
const route = useRoute();
const token = String(route.params.token ?? '');
const { alert } = useAlert();

const form = ref<NominationForm | null>(null);
const loading = ref(true);
const notFound = ref(false);
const captcha = ref('');
const human = ref<{ reset: () => void } | null>(null);
const busy = ref(false);
const result = ref<NominationResult | null>(null);

// ----- The names typed, per category (one row to start; up to the limit)
interface Row { key: number; name: string; reason: string }
const entries = ref<Record<number, Row[]>>({});
let nextKey = 1;
const blankRow = (): Row => ({ key: nextKey++, name: '', reason: '' });
const perCategory = computed(() => form.value?.per_category ?? 3);
function resetEntries() {
    entries.value = Object.fromEntries((form.value?.categories ?? []).map(c => [c.id, [blankRow()]]));
}
function addRow(catId: number) { entries.value[catId]?.push(blankRow()); }
function removeRow(catId: number, i: number) { entries.value[catId]?.splice(i, 1); }

/** "Ada  OBI" and "ada obi" are the same name. */
const key = (s: string) => s.replace(/\s+/g, ' ').trim().toLowerCase();
/** The same name typed twice in one category: only the first counts. */
function isRepeat(catId: number, i: number): boolean {
    const rows = entries.value[catId] ?? [];
    const k = key(rows[i]?.name ?? '');
    return k.length >= 2 && rows.slice(0, i).some(r => key(r.name) === k);
}
/** Every filled-in name, once per category. */
const toSend = computed(() => (form.value?.categories ?? []).flatMap(c =>
    (entries.value[c.id] ?? [])
        .filter((r, i) => key(r.name).length >= 2 && !isRepeat(c.id, i))
        .map(r => ({ category_id: c.id, nominee_name: r.name.replace(/\s+/g, ' ').trim(), reason: r.reason.trim() || undefined }))));

useHead({ title: computed(() => `${t('nom.title')} · ${form.value?.name ?? 'Ballotify'}`) });
useBrandTheme(() => form.value?.brand);

onMounted(async () => {
    try {
        form.value = (await publicApi.nominationForm(token)).data;
        setEventLanguage(form.value.language);
        resetEntries();
    } catch (e) {
        notFound.value = apiStatus(e) === 404;
        if (!notFound.value) await alert({ title: t('nom.cantLoad'), message: apiErrorMessage(e) });
    } finally {
        loading.value = false;
    }
});

async function submit() {
    if (!toSend.value.length) return;
    busy.value = true;
    try {
        const { data } = await publicApi.nominateMany(token, {
            nominations: toSend.value, captcha_token: captcha.value || undefined, device_id: deviceId(),
        });
        showResult(data);
    } catch (e) {
        human.value?.reset();
        // Every name refused: the server still says which and why.
        const body = (e as { response?: { data?: Partial<NominationResult> } }).response?.data;
        if (body?.refused?.length) { showResult({ message: body.message ?? '', accepted: body.accepted ?? [], refused: body.refused }); return; }
        const fields = apiFieldErrors(e);
        await alert({ title: t('nom.notSent'), message: Object.values(fields)[0] ?? apiErrorMessage(e) });
    } finally {
        busy.value = false;
    }
}

function showResult(r: NominationResult) {
    result.value = r;
    captcha.value = ''; // a check token works once: the next form gets a fresh one
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function startOver() {
    result.value = null;
    resetEntries();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
/** Accepted names grouped by category, in the form's order. */
function grouped(list: NominationResult['accepted']) {
    const by = new Map<string, string[]>();
    for (const a of list) by.set(a.category, [...(by.get(a.category) ?? []), a.name]);
    return [...by].map(([category, names]) => ({ category, names }));
}
</script>

<style scoped>
.name-row { display: flex; flex-wrap: wrap; align-items: flex-start; gap: .5rem; }
.name-fields { flex: 1 1 0; min-width: 0; display: grid; grid-template-columns: 1.2fr 1fr; gap: .5rem; }
@media (max-width: 575.98px) { .name-fields { grid-template-columns: 1fr; } }
.reason { font-size: .92rem; }
.send-bar { position: sticky; bottom: .75rem; z-index: 5; box-shadow: 0 12px 32px -16px rgba(15, 23, 42, .35); }
.sent { padding: .8rem 1rem; border-radius: 12px; background: var(--surface-2); }
.sent.refused { background: var(--danger-50, var(--surface-2)); }
.sent-row { display: flex; gap: .5rem; align-items: baseline; font-size: .92rem; padding: .15rem 0; overflow-wrap: anywhere; }
.sent-row .bi-check-circle-fill { color: var(--success); flex-shrink: 0; }
.sent-row .bi-x-circle-fill { color: var(--danger); flex-shrink: 0; }
.intro-band {
    padding: 1.75rem 1.5rem;
    background: var(--night);
}
.intro-band .pill-live { background: rgba(74, 222, 128, .15); color: #86efac; }
.intro-text { color: rgba(255, 255, 255, .75); }
.brand-logo { display: block; max-height: 48px; max-width: 180px; object-fit: contain; border-radius: 8px; background: rgba(255, 255, 255, .92); padding: 4px 8px; }
</style>
