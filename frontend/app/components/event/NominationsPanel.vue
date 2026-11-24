<template>
    <div>
        <div class="status-bar card-b mb-4">
            <span class="pill" :class="data?.open ? 'pill-active' : 'pill-completed'"><span class="dot"></span>{{ data?.open ? 'Nominations open' : 'Nominations closed' }}</span>
            <span class="small text-muted flex-grow-1">
                <template v-if="data">{{ formatNumber(data.total) }} nomination{{ data.total === 1 ? '' : 's' }}</template>
                <template v-if="data?.ends_at"> · {{ data.open ? 'closes' : 'closed' }} {{ formatDate(data.ends_at, true) }}</template>
            </span>
            <button class="btn btn-ghost btn-sm" @click="copyText(nominateUrl, 'Nomination link copied')"><i class="bi bi-link-45deg"></i> Copy link</button>
            <button class="btn btn-ghost btn-icon btn-sm" title="Refresh" aria-label="Refresh" @click="load"><i class="bi bi-arrow-clockwise"></i></button>
        </div>

        <div v-if="loading && !data" class="d-grid gap-3"><div v-for="i in 2" :key="i" class="skeleton" style="height: 160px"></div></div>
        <EmptyState v-else-if="data && !data.categories.length" class="card-b" icon="bi-grid" title="Add categories first"
            text="People nominate per category. Add them on the Ballot tab." />
        <div v-else-if="data" class="d-grid gap-4">
            <section v-for="cat in data.categories" :key="cat.id" class="card-b overflow-hidden">
                <header class="card-header-b">
                    <h3 class="h6 fw-800 mb-0 text-break">{{ cat.name }}</h3>
                    <span class="small text-muted">{{ cat.nominees.length }} nominee{{ cat.nominees.length === 1 ? '' : 's' }}</span>
                </header>
                <div v-if="!cat.nominees.length" class="p-4 small text-muted text-center">No nominations yet.</div>
                <ul v-else class="list-unstyled mb-0">
                    <li v-for="(n, i) in cat.nominees" :key="n.name" class="nom-row">
                        <span class="rank" :class="{ top: i < 3 }">{{ i + 1 }}</span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-ink text-break">{{ n.name }}</div>
                            <div v-if="n.reasons.length" class="small text-muted reason" :title="n.reasons[0]">“{{ n.reasons[0] }}”</div>
                        </div>
                        <span class="votes-pill">{{ formatNumber(n.count) }}</span>
                        <span v-if="n.on_ballot" class="pill pill-active flex-shrink-0"><i class="bi bi-check2"></i> On ballot</span>
                        <button v-else-if="canEdit" class="btn btn-soft btn-sm flex-shrink-0" :disabled="event.ballot_locked || adding === key(cat.id, n.name)"
                            @click="promote(cat.id, n.name)">
                            <span v-if="adding === key(cat.id, n.name)" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-plus-lg"></i> Add to ballot
                        </button>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, NominationSummary } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ changed: [] }>();
const toast = useToast();

const data = ref<NominationSummary | null>(null);
const loading = ref(true);
const adding = ref<string | null>(null);
const canEdit = computed(() => props.event.abilities?.includes('manage') ?? true);
const { voteUrl } = useEventLinks(() => props.event.link_token);
const nominateUrl = computed(() => voteUrl.value.replace('/vote/', '/nominate/'));
const key = (c: number, n: string) => `${c}:${n}`;

async function load() {
    loading.value = true;
    try { data.value = (await adminApi.getNominations(props.event.id)).data; }
    catch (e) { toast.error(apiErrorMessage(e)); }
    finally { loading.value = false; }
}
onMounted(load);

async function promote(categoryId: number, name: string) {
    adding.value = key(categoryId, name);
    try {
        await adminApi.promoteNominee(props.event.id, { category_id: categoryId, name });
        toast.success(`${name} added to the ballot`);
        await load();
        emit('changed');
    } catch (e) {
        toast.error(apiFieldErrors(e).name ?? apiErrorMessage(e));
    } finally {
        adding.value = null;
    }
}
</script>

<style scoped>
.status-bar { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .75rem 1rem; }
.nom-row { display: flex; align-items: center; gap: .8rem; padding: .75rem 1.25rem; border-bottom: 1px solid var(--line); flex-wrap: wrap; }
.nom-row:last-child { border-bottom: 0; }
.rank { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; font-weight: 800; font-size: .8rem; background: var(--surface-2); color: var(--muted); flex-shrink: 0; }
.rank.top { background: var(--brand-50); color: var(--brand-700); }
.votes-pill { font-weight: 800; color: var(--ink); min-width: 2.5rem; text-align: right; flex-shrink: 0; }
.reason { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
</style>
