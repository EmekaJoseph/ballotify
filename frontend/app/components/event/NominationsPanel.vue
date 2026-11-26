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
                    <button v-if="canEdit && editingId !== cat.id" class="btn btn-ghost btn-sm" @click="startEdit(cat)">
                        <i class="bi bi-list-ul"></i> {{ cat.suggestions.length ? `Suggested names (${cat.suggestions.length})` : 'Suggest names' }}
                    </button>
                </header>

                <!-- Names the organiser suggests: shown as a pick list on the public form (people can still type their own). -->
                <div v-if="editingId === cat.id" class="sugg-edit">
                    <label :for="`sugg-${cat.id}`" class="form-label mb-1">Suggested names for “{{ cat.name }}”</label>
                    <div class="small text-muted mb-2">One name per line. People nominating can pick one of these from a list, or type someone else.</div>
                    <textarea :id="`sugg-${cat.id}`" v-model="draft" class="form-control mb-2" rows="6" placeholder="Amaka Obi&#10;Tunde Bakare&#10;Zainab Musa"></textarea>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="btn btn-ghost btn-sm mb-0" title="Adds the Name column (or the first column) of an Excel or CSV file to the list">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Import Excel or CSV
                            <input type="file" :accept="TABLE_FILE_ACCEPT" class="visually-hidden" @change="importNames" />
                        </label>
                        <span class="small text-muted flex-grow-1">{{ draftNames.length }} name{{ draftNames.length === 1 ? '' : 's' }}<template v-if="draftNames.length > 300">: at most 300</template></span>
                        <button class="btn btn-ghost btn-sm" :disabled="saving" @click="editingId = null">Cancel</button>
                        <button class="btn btn-primary btn-sm" :disabled="saving || draftNames.length > 300" @click="saveSuggestions(cat.id)">
                            <span v-if="saving" class="spinner-border spinner-border-sm"></span> Save
                        </button>
                    </div>
                </div>
                <div v-else-if="cat.suggestions.length" class="sugg-list">
                    <span class="small text-muted me-1">Suggested:</span>
                    <span v-for="s in cat.suggestions.slice(0, 8)" :key="s" class="sugg-chip">{{ s }}</span>
                    <span v-if="cat.suggestions.length > 8" class="small text-muted">+{{ cat.suggestions.length - 8 }} more</span>
                </div>
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

// ----- Suggested names (one category edited at a time)
const editingId = ref<number | null>(null);
const draft = ref('');
const saving = ref(false);
const draftNames = computed(() => {
    const seen = new Set<string>();
    return draft.value.split(/\r?\n/).map(s => s.replace(/\s+/g, ' ').trim())
        .filter(s => s.length >= 2 && !seen.has(s.toLowerCase()) && seen.add(s.toLowerCase()));
});
function startEdit(cat: NominationSummary['categories'][number]) {
    editingId.value = cat.id;
    draft.value = cat.suggestions.join('\n');
}
/** Adds names from an Excel / CSV file to the list being edited (nothing is saved until Save). */
async function importNames(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) { toast.error('That file is larger than 3 MB.'); return; }
    try {
        const names = namesFromTable(await readTableFile(file));
        if (!names.length) { toast.error('No names found in that file.'); return; }
        const before = draftNames.value.length;
        draft.value = [draft.value.trim(), ...names].filter(Boolean).join('\n');
        const added = draftNames.value.length - before;
        toast.success(added ? `${added} name${added === 1 ? '' : 's'} added. Check the list, then Save.` : 'Those names are already on the list.');
    } catch (err) {
        toast.error(err instanceof Error ? err.message : 'That file couldn’t be read.');
    }
}
async function saveSuggestions(categoryId: number) {
    saving.value = true;
    try {
        const { data: res } = await adminApi.setNomineeSuggestions(props.event.id, { category_id: categoryId, names: draftNames.value });
        const cat = data.value?.categories.find(c => c.id === categoryId);
        if (cat) cat.suggestions = res.suggestions;
        editingId.value = null;
        toast.success(res.suggestions.length ? `${res.suggestions.length} suggested name${res.suggestions.length === 1 ? '' : 's'} saved` : 'Suggested names removed');
    } catch (e) {
        toast.error(Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e));
    } finally {
        saving.value = false;
    }
}

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
.sugg-edit { padding: 1rem 1.25rem; border-bottom: 1px solid var(--line); background: var(--surface-2); }
.sugg-list { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem; padding: .65rem 1.25rem; border-bottom: 1px solid var(--line); }
.sugg-chip { font-size: .78rem; font-weight: 600; padding: .15rem .55rem; border-radius: 999px; background: var(--brand-50); color: var(--brand-700); max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.reason { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
</style>
