<template>
    <div class="sheet-shell">
        <div class="toolbar no-print">
            <NuxtLink :to="`/dashboard/events/${id}?tab=ballot`" class="tb-btn"><i class="bi bi-arrow-left"></i> Back to event</NuxtLink>
            <div class="tb-options">
                <label title="Boxes to tick (or rank) by hand: a paper ballot"><input v-model="opts.boxes" type="checkbox" /> Tick boxes</label>
                <label v-if="hasPhotos"><input v-model="opts.photos" type="checkbox" /> Photos</label>
                <label v-if="hasDescriptions"><input v-model="opts.descriptions" type="checkbox" /> Descriptions</label>
                <label><input v-model="opts.pageEach" type="checkbox" /> Each category on its own page</label>
            </div>
            <button class="tb-btn primary" :disabled="!event" @click="print"><i class="bi bi-file-earmark-pdf"></i> Save as PDF / Print</button>
        </div>
        <p class="tb-hint no-print">In the print window, choose <b>Save as PDF</b> as the printer.</p>

        <div v-if="loading" class="paper"><div class="muted">Preparing the ballot…</div></div>
        <div v-else-if="error" class="paper"><div class="muted">{{ error }}</div></div>

        <article v-else-if="event" class="paper">
            <header class="r-head">
                <div class="brand">
                    <img v-if="event.brand_logo_url" :src="event.brand_logo_url" alt="" class="org-logo" />
                    <template v-else><BrandMark :size="28" tone="on-light" /> E-voting by Agudatechy</template>
                </div>
                <span class="kicker">{{ opts.boxes ? 'Ballot paper' : 'Ballot' }}</span>
            </header>

            <div class="title-block">
                <h1>{{ event.name }}</h1>
                <p v-if="event.starts_at || event.ends_at" class="sub">{{ formatWindow(event.starts_at, event.ends_at) }}</p>
                <p v-if="event.description" class="desc">{{ event.description }}</p>
                <p class="sub small">{{ categories.length }} categor{{ categories.length === 1 ? 'y' : 'ies' }} · {{ candidateCount }} candidate{{ candidateCount === 1 ? '' : 's' }}</p>
            </div>

            <p v-if="!categories.length" class="muted">The ballot is empty: add categories and candidates on the Ballot tab.</p>

            <template v-for="(cat, ci) in categories" :key="cat.id">
                <section class="cat" :class="{ 'page-each': opts.pageEach && ci > 0 }">
                    <div class="cat-head">
                        <h2><span class="cat-no">{{ ci + 1 }}.</span> {{ cat.name }}</h2>
                        <span class="how">{{ instruction(cat) }}</span>
                    </div>

                    <p v-if="!(byCategory.get(cat.id) ?? []).length" class="muted small">No candidates yet.</p>
                    <ol v-else class="cands">
                        <li v-for="c in byCategory.get(cat.id)" :key="c.id" class="cand">
                            <span v-if="opts.boxes" class="box" aria-hidden="true"></span>
                            <img v-if="opts.photos && c.image_url" :src="c.image_url" alt="" class="photo" />
                            <div class="cand-text">
                                <div class="cname">{{ c.name }}</div>
                                <div v-if="opts.descriptions && c.description" class="cdesc">{{ c.description }}</div>
                            </div>
                        </li>
                    </ol>
                </section>
            </template>

            <footer class="r-foot">
                <template v-if="anyShuffled">Online, candidates in some categories appear in a random order on each ballot. </template>
                Printed {{ generatedAt }} · E-voting by Agudatechy
            </footer>
        </article>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, Candidate, Category } from '~/types';

definePageMeta({ layout: false, middleware: ['auth'] });

const route = useRoute();
const id = String(route.params.id);
const event = ref<BallotEvent | null>(null);
const categories = ref<Category[]>([]);
const candidates = ref<Candidate[]>([]);
const loading = ref(true);
const error = ref('');
const generatedAt = new Date().toLocaleString(undefined, { dateStyle: 'long', timeStyle: 'short' });
const opts = reactive({ boxes: route.query.paper === '1', photos: true, descriptions: true, pageEach: false });

const byCategory = computed(() => {
    const m = new Map<number, Candidate[]>();
    for (const c of candidates.value) m.set(c.category_id, [...(m.get(c.category_id) ?? []), c]);
    return m;
});
const candidateCount = computed(() => candidates.value.length);
const hasPhotos = computed(() => candidates.value.some(c => c.image_url));
const hasDescriptions = computed(() => candidates.value.some(c => c.description));
const anyShuffled = computed(() => categories.value.some(c => c.shuffle_candidates));

/** How to fill in this category, in words. */
function instruction(cat: Category): string {
    if (cat.max_choices === null) return 'Choose as many as you support';
    if (!cat.max_choices || cat.max_choices === 1) return 'Choose one';
    return `Choose up to ${cat.max_choices}`;
}

useHead({ title: computed(() => (event.value ? `${event.value.name} · Ballot` : 'Ballot')) });

// A printed document is always light, whatever the app theme.
let previousTheme: string | null = null;
onMounted(async () => {
    previousTheme = document.documentElement.getAttribute('data-theme');
    document.documentElement.setAttribute('data-theme', 'light');
    try {
        const [e, cats, cands] = await Promise.all([adminApi.getEvent(id), adminApi.getCategories(id), adminApi.getCandidates(id)]);
        event.value = e.data;
        categories.value = cats.data;
        candidates.value = cands.data;
    } catch (e) {
        error.value = apiStatus(e) === 404 ? 'This event was not found.' : apiErrorMessage(e, 'Could not load the ballot.');
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
h1, h2, .cname, .cdesc, .desc { overflow-wrap: anywhere; }
/* A printed document: always light, whatever the app theme. */
.sheet-shell {
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
.tb-btn.primary { background: #058580; border-color: #058580; color: #fff; }
.tb-btn:disabled { opacity: .6; cursor: not-allowed; }

.paper {
    max-width: 860px; margin: 0 auto; background: #fff; border-radius: 14px; padding: 2.75rem 3rem;
    box-shadow: 0 20px 50px -25px rgba(15, 23, 42, .35);
}
@media (max-width: 575.98px) { .paper { padding: 1.5rem 1.1rem; } }
.muted { color: #64748b; }
.small { font-size: .82rem; }

.r-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-bottom: 1.25rem; border-bottom: 3px solid #058580; margin-bottom: 1.75rem; }
.brand { display: inline-flex; align-items: center; gap: .5rem; font-weight: 800; color: #058580; font-size: 1.1rem; }
.org-logo { max-height: 44px; max-width: 200px; object-fit: contain; }
.kicker { text-transform: uppercase; letter-spacing: .12em; font-size: .72rem; font-weight: 800; color: #058580; }

.title-block { margin-bottom: 1.75rem; }
h1 { font-size: 2rem; font-weight: 800; margin: 0 0 .3rem; letter-spacing: -.02em; line-height: 1.15; }
.sub { color: #64748b; margin: 0 0 .2rem; }
.desc { margin: .6rem 0 .4rem; color: #334155; white-space: pre-line; }

.cat { margin-bottom: 1.75rem; break-inside: avoid; }
.cat.page-each { break-before: page; }
@media screen { .cat.page-each { margin-top: 2.25rem; padding-top: 2.25rem; border-top: 1px dashed #cbd5e1; } }
.cat-head { border-bottom: 2px solid #0f172a; padding-bottom: .35rem; margin-bottom: .5rem; }
h2 { font-size: 1.15rem; font-weight: 800; margin: 0; }
.cat-no { color: #058580; }
.how { display: block; font-size: .82rem; color: #475569; font-weight: 600; margin-top: .15rem; }
.note { font-size: .78rem; color: #64748b; margin: 0 0 .4rem; }

.cands { list-style: none; margin: 0; padding: 0; }
.cand { display: flex; align-items: center; gap: .85rem; padding: .6rem .25rem; border-bottom: 1px solid #eef2f6; break-inside: avoid; }
.cand:last-child { border-bottom: 0; }
.box { width: 22px; height: 22px; border: 2px solid #0f172a; border-radius: 4px; flex-shrink: 0; }
.photo { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: #eef2f6; }
.cand-text { min-width: 0; }
.cname { font-weight: 700; }
.cdesc { color: #64748b; font-size: .8rem; margin-top: .1rem; }

.r-foot { margin-top: 3rem; text-align: center; font-size: .75rem; color: #94a3b8; }

@media print {
    /* The margin lives on the page itself (padding repeated on every printed page), so it's
       the same whatever "Margins" the print dialog is set to, and no browser URL/date lines. */
    @page { size: A4; margin: 0; }
    .no-print { display: none !important; }
    .sheet-shell { background: #fff; padding: 0; min-height: 0; }
    .paper {
        box-shadow: none; border-radius: 0; max-width: none; padding: 14mm 13mm;
        -webkit-box-decoration-break: clone; box-decoration-break: clone;
    }
    .paper * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
