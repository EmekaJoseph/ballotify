<template>
    <div>
        <div v-if="locked && canEdit" class="lock-banner mb-4">
            <i class="bi bi-lock-fill"></i>
            <span>
                <b>{{ event.status === 'completed' ? 'Voting has closed.' : 'Voting has started.' }}</b>
                The ballot is locked, so categories and candidates can’t be added, removed or moved.
                You can still fix names, descriptions and photos; every change is recorded in the Activity tab.
            </span>
        </div>

        <div v-if="!canEdit" class="lock-banner mb-4">
            <i class="bi bi-eye"></i>
            <span>You can view the ballot. Only the owner and editors can change it.</span>
        </div>

        <!-- Add category -->
        <div v-if="(!locked && canEdit) || categories.length" class="card-b pad mb-4">
            <form v-if="!locked && canEdit" class="d-flex flex-column flex-sm-row gap-2" @submit.prevent="addCategory">
                <div class="input-icon flex-grow-1 min-w-0">
                    <i class="bi bi-folder-plus"></i>
                    <input v-model.trim="newCategory" type="text" class="form-control" maxlength="255"
                        placeholder="New category, e.g. President" aria-label="New category name" :disabled="busy" />
                </div>
                <button type="submit" class="btn btn-primary text-nowrap flex-shrink-0" :disabled="busy || !newCategory">
                    <i class="bi bi-plus-lg"></i> Add category
                </button>
            </form>
            <!-- Secondary tools: import while the ballot can change; export any time. -->
            <div class="ballot-tools" :class="{ 'mt-3 pt-3 border-top': !locked && canEdit }">
                <button v-if="!locked && canEdit && categories.some(c => c.method !== 'motion')" type="button" class="btn btn-ghost btn-sm text-nowrap" :disabled="busy" @click="openBatch(null)">
                    <i class="bi bi-person-plus"></i> Add candidates
                </button>
                <button v-if="!locked && canEdit" type="button" class="btn btn-ghost btn-sm text-nowrap" :disabled="busy" @click="importOpen = true">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Import candidates
                </button>
                <NuxtLink v-if="categories.length" :to="`/ballot-sheet/${event.id}`" target="_blank" class="btn btn-ghost btn-sm text-nowrap"
                    title="All categories and candidates on one printable page: save it as PDF, or print paper ballots">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </NuxtLink>
            </div>
        </div>

        <EmptyState v-if="!categories.length" class="card-b" icon="bi-ui-checks-grid" title="Your ballot is empty"
            text="Start with a category such as “President” or “Best Actor”, then add candidates to it. Or import them all from a spreadsheet." />

        <div class="d-grid gap-4">
            <section v-for="cat in categories" :key="cat.id" class="card-b overflow-hidden">
                <header class="card-header-b">
                    <form v-if="editingCat === cat.id" class="d-flex gap-2 flex-grow-1" @submit.prevent="saveCategory(cat)">
                        <input v-model.trim="catDraft" class="form-control form-control-sm" maxlength="255" aria-label="Category name" autofocus />
                        <button class="btn btn-primary btn-sm" :disabled="busy || !catDraft">Save</button>
                        <button type="button" class="btn btn-ghost btn-sm" @click="editingCat = null">Cancel</button>
                    </form>
                    <template v-else>
                        <div class="min-w-0">
                            <h3 class="h6 fw-800 mb-0 text-break"><span v-if="cat.method === 'motion'" class="pill pill-violet me-1"><i class="bi bi-hand-thumbs-up"></i> Motion</span>{{ cat.name }}</h3>
                            <p v-if="cat.method === 'motion' && cat.description" class="small text-muted mb-1 motion-text">{{ cat.description }}</p>
                            <div class="small text-muted d-flex flex-wrap align-items-center gap-1">
                                <template v-if="cat.method === 'motion'">
                                    <span>Yes / No / Abstain · {{ passRuleName(cat.pass_rule) }}</span>
                                </template>
                                <span v-else>{{ candidatesOf(cat.id).length }} candidate{{ candidatesOf(cat.id).length === 1 ? '' : 's' }}</span>
                                <template v-if="cat.method !== 'motion'">
                                    <span aria-hidden="true">·</span>
                                    <span v-if="locked || !canEdit">{{ methodName(cat) }}</span>
                                    <AppSelect v-else :model-value="methodValue(cat)" :options="methodOptions(cat)" size="sm" :block="false" :menu-min-width="280"
                                        class="limit-select" :aria-label="`Voting method for ${cat.name}`" :disabled="busy" @change="setLimit(cat, $event)" />
                                </template>
                                <span v-if="cat.shuffle_candidates" class="pill pill-violet shuffle-pill"><i class="bi bi-shuffle"></i> Random order</span>
                            </div>
                        </div>
                        <div v-if="canEdit" class="d-flex gap-1 flex-shrink-0">
                            <button v-if="cat.method !== 'motion'" class="btn btn-ghost btn-icon btn-sm" :class="{ 'shuffle-on': cat.shuffle_candidates }" :disabled="busy"
                                :title="cat.shuffle_candidates ? 'Candidates appear in random order. Click for a fixed order.' : 'Show candidates in random order on each ballot'"
                                :aria-pressed="!!cat.shuffle_candidates" aria-label="Random candidate order" @click="toggleShuffle(cat)"><i class="bi bi-shuffle"></i></button>
                            <button v-if="!locked && cat.method !== 'motion'" class="btn btn-soft btn-sm" @click="openBatch(cat.id)"><i class="bi bi-person-plus"></i> <span class="d-none d-sm-inline">Candidates</span></button>
                            <button v-else class="btn btn-ghost btn-icon btn-sm" title="Rename" aria-label="Rename category" @click="editingCat = cat.id; catDraft = cat.name"><i class="bi bi-pencil"></i></button>
                            <button v-if="!locked" class="btn btn-ghost btn-icon btn-sm text-danger" title="Delete" aria-label="Delete category" @click="removeCategory(cat)"><i class="bi bi-trash"></i></button>
                        </div>
                    </template>
                </header>

                <div v-if="!candidatesOf(cat.id).length" class="p-4 text-center small text-muted">
                    No candidates<template v-if="!locked && canEdit"> yet. <button class="btn btn-link btn-sm p-0 align-baseline" @click="openBatch(cat.id)">Add candidates</button></template>.
                </div>
                <ul v-else class="list-unstyled mb-0">
                    <li v-for="cand in candidatesOf(cat.id)" :key="cand.id" class="cand-row" :class="{ motion: cand.motion_choice }">
                        <span v-if="cand.motion_choice" class="motion-ic" :class="cand.motion_choice" aria-hidden="true"><i class="bi" :class="MOTION_ICON[cand.motion_choice]"></i></span>
                        <CandidateAvatar v-else :name="cand.name" :src="cand.image_url" />
                        <span class="flex-grow-1 min-w-0">
                            <span class="d-block fw-semibold text-ink text-break">{{ cand.name }}</span>
                            <span v-if="cand.description" class="small text-muted text-break cand-desc" :title="cand.description">{{ cand.description }}</span>
                        </span>
                        <template v-if="!cand.motion_choice">
                            <button class="btn btn-ghost btn-icon btn-sm" title="Campaign card: an image to share on WhatsApp and social media" aria-label="Campaign card" @click="cardFor = { cand, category: cat.name }"><i class="bi bi-image"></i></button>
                            <button v-if="canEdit" class="btn btn-ghost btn-icon btn-sm" title="Edit" aria-label="Edit candidate" @click="openCandidate(cand)"><i class="bi bi-pencil"></i></button>
                            <button v-if="!locked && canEdit" class="btn btn-ghost btn-icon btn-sm text-danger" title="Delete" aria-label="Delete candidate" @click="removeCandidate(cand)"><i class="bi bi-trash"></i></button>
                        </template>
                    </li>
                </ul>
            </section>
        </div>


        <EventCandidateBatchModal :open="batchOpen" :event-id="event.id" :categories="categories" :candidates="event.candidates ?? []"
            :initial-category-id="batchCategory" @close="batchOpen = false" @saved="onBatchSaved" />
        <EventCandidateImportModal :open="importOpen" :event-id="event.id" @close="importOpen = false" @imported="onImported" />
        <CampaignCardModal :open="!!cardFor" :candidate="cardFor?.cand ?? null" :category-name="cardFor?.category ?? ''" :event-name="event.name"
            :token="event.link_token" :brand-color="event.brand_color" @close="cardFor = null" />

        <!-- Candidate modal -->
        <BaseModal :open="modal.open" :title="modal.id ? 'Edit candidate' : 'Add candidate'" @close="modal.open = false">
            <form id="cand-form" novalidate @submit.prevent="saveCandidate">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <CandidateAvatar :name="modal.name || '?'" :src="modal.preview" size="xl" />
                    <div class="d-flex flex-wrap gap-2">
                        <label class="btn btn-soft btn-sm mb-0">
                            <i class="bi bi-image"></i> {{ modal.preview ? 'Change photo' : 'Upload photo' }}
                            <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="visually-hidden" @change="onFile" />
                        </label>
                        <button v-if="modal.preview" type="button" class="btn btn-ghost btn-sm" @click="clearPhoto">Remove</button>
                        <div class="form-text w-100 mt-0">JPG, PNG or WebP, up to 5 MB.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="cand-name" class="form-label">Name</label>
                    <input id="cand-name" v-model.trim="modal.name" class="form-control" maxlength="255" placeholder="Full name" />
                </div>
                <div class="mb-3">
                    <label for="cand-desc" class="form-label">Short description <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea id="cand-desc" v-model="modal.description" class="form-control" rows="2" :maxlength="DESC_MAX"
                        placeholder="e.g. Member since 2015 · focused on transparent finances"></textarea>
                    <div class="form-text d-flex justify-content-between gap-2">
                        <span>Shown under the name on the ballot and the results.</span>
                        <span :class="{ 'text-warning-emphasis': modal.description.length > DESC_MAX - 20 }">{{ modal.description.length }}/{{ DESC_MAX }}</span>
                    </div>
                </div>
                <div>
                    <label for="cand-cat" class="form-label">Category</label>
                    <AppSelect id="cand-cat" v-model="modal.categoryId" :options="categories.map(c => ({ value: c.id, label: c.name }))"
                        :disabled="locked" :title="locked ? 'Locked while voting is under way' : ''" />
                </div>
                <div v-if="modal.error" class="field-error mt-3">{{ modal.error }}</div>
            </form>
            <template #footer>
                <button type="button" class="btn btn-ghost" @click="modal.open = false">Cancel</button>
                <button type="submit" form="cand-form" class="btn btn-primary" :disabled="busy || !modal.name || !modal.categoryId">
                    <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                    {{ modal.id ? 'Save changes' : 'Add candidate' }}
                </button>
            </template>
        </BaseModal>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, Candidate, CandidateImportResult, Category } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ changed: [] }>();

const toast = useToast();
const { confirm } = useConfirm();
const busy = ref(false);
const newCategory = ref('');
const editingCat = ref<number | null>(null);
const catDraft = ref('');

const DESC_MAX = 280;
const MOTION_ICON = { yes: 'bi-check-lg', no: 'bi-x-lg', abstain: 'bi-dash-lg' } as const;
const passRuleName = (r?: string | null) => ({ two_thirds: 'two-thirds majority', three_quarters: 'three-quarters majority' } as Record<string, string>)[r ?? ''] ?? 'simple majority';
const cardFor = ref<{ cand: Candidate; category: string } | null>(null);
const importOpen = ref(false);
const canEdit = computed(() => props.event.abilities?.includes('manage') ?? true);
/** Candidates removed a moment ago (Undo still possible). */
const hidden = ref(new Set<number>());
const categories = computed<Category[]>(() => props.event.categories ?? []);
const locked = computed(() => props.event.ballot_locked);
const candidatesOf = (catId: number): Candidate[] =>
    (props.event.candidates ?? []).filter(c => c.category_id === catId && !hidden.value.has(c.id));


async function run(action: () => Promise<unknown>, success: string) {
    busy.value = true;
    try {
        await action();
        toast.success(success);
        emit('changed');
        return true;
    } catch (e) {
        toast.error(apiErrorMessage(e));
        return false;
    } finally {
        busy.value = false;
    }
}

async function addCategory() {
    if (!newCategory.value) return;
    const name = newCategory.value;
    if (await run(() => adminApi.createCategory(props.event.id, { name }), `“${name}” added`)) newCategory.value = '';
}

async function saveCategory(cat: Category) {
    if (catDraft.value === cat.name) { editingCat.value = null; return; }
    if (await run(() => adminApi.updateCategory(props.event.id, cat.id, { name: catDraft.value }), 'Category renamed')) editingCat.value = null;
}

/** "Pick up to" choices: 2 … number of candidates (at least the current limit). */
function limitOptions(cat: Category): number[] {
    const max = Math.max(candidatesOf(cat.id).length, cat.max_choices ?? 0, 2);
    return Array.from({ length: max - 1 }, (_, i) => i + 2);
}

async function toggleShuffle(cat: Category) {
    const on = !cat.shuffle_candidates;
    await run(() => adminApi.updateCategory(props.event.id, cat.id, { shuffle_candidates: on }),
        on ? `Candidates in “${cat.name}” will appear in random order` : `Candidates in “${cat.name}” will appear in a fixed order`);
}

/** The method picker's value: '1'…'N' or 'any' (approval). */
const methodValue = (cat: Category) => (cat.max_choices == null ? 'any' : String(cat.max_choices));
function methodOptions(cat: Category) {
    return [
        { value: '1', label: 'Choose one', hint: 'Most votes wins' },
        ...limitOptions(cat).map(n => ({ value: String(n), label: `Pick up to ${n}`, hint: `Voters choose up to ${n}` })),
        { value: 'any', label: 'Approval', hint: 'Voters tick everyone they approve of' },
    ];
}

async function setLimit(cat: Category, raw: string) {
    const method = 'plurality';
    const max_choices = raw === 'any' ? null : Number(raw);
    if (method === (cat.method ?? 'plurality') && max_choices === (cat.max_choices ?? null)) return;
    const label = methodName({ method, max_choices });
    await run(() => adminApi.updateCategory(props.event.id, cat.id, { method, max_choices }), `“${cat.name}”: ${label.charAt(0).toLowerCase()}${label.slice(1)}`);
}

// Add several candidates at once (one-line form + list).
const batchOpen = ref(false);
const batchCategory = ref<number | null>(null);
function openBatch(categoryId: number | null) { batchCategory.value = categoryId; batchOpen.value = true; }
function onBatchSaved(result: CandidateImportResult) {
    batchOpen.value = false;
    onImported(result);
}

function onImported(result: CandidateImportResult) {
    importOpen.value = false;
    const cats = result.categories_created;
    toast.success(`${result.created} candidate${result.created === 1 ? '' : 's'} added`
        + (cats ? ` into ${cats} new categor${cats === 1 ? 'y' : 'ies'}` : ''));
    if (result.skipped.length) {
        const first = result.skipped[0]!;
        toast.info(`${result.skipped.length} row${result.skipped.length === 1 ? '' : 's'} skipped (e.g. row ${first.row}: ${first.reason}).`);
    }
    emit('changed');
}

async function removeCategory(cat: Category) {
    const n = candidatesOf(cat.id).length;
    const ok = await confirm({
        title: `Delete “${cat.name}”?`,
        message: n ? `Its ${n} candidate${n === 1 ? '' : 's'} will be deleted too.` : 'This category will be removed from the ballot.',
        confirmText: 'Delete category',
        danger: true,
    });
    if (ok) await run(() => adminApi.deleteCategory(props.event.id, cat.id), 'Category deleted');
}

// ----- Candidate modal -----
const modal = reactive({
    open: false,
    id: null as number | null,
    name: '',
    description: '',
    categoryId: 0,
    file: null as File | null,
    preview: null as string | null,
    removePhoto: false,
    error: '',
});

function revokePreview() {
    if (modal.preview?.startsWith('blob:')) URL.revokeObjectURL(modal.preview);
}

function openCandidate(cand: Candidate | null, categoryId?: number) {
    revokePreview();
    Object.assign(modal, {
        open: true,
        id: cand?.id ?? null,
        name: cand?.name ?? '',
        description: cand?.description ?? '',
        categoryId: cand?.category_id ?? categoryId ?? categories.value[0]?.id ?? 0,
        file: null,
        preview: cand?.image_url ?? null,
        removePhoto: false,
        error: '',
    });
}

function onFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    if (!file.type.startsWith('image/')) { modal.error = 'Please choose an image file.'; return; }
    if (file.size > 5 * 1024 * 1024) { modal.error = 'That image is larger than 5 MB.'; return; }
    revokePreview();
    modal.error = '';
    modal.file = file;
    modal.preview = URL.createObjectURL(file);
    modal.removePhoto = false;
}

function clearPhoto() {
    revokePreview();
    modal.file = null;
    modal.preview = null;
    modal.removePhoto = true;
}

async function saveCandidate() {
    if (!modal.name || !modal.categoryId) return;
    const fd = new FormData();
    fd.append('name', modal.name);
    fd.append('description', modal.description.trim());
    fd.append('category_id', String(modal.categoryId));
    if (modal.file) fd.append('image', modal.file);
    if (modal.id && modal.removePhoto && !modal.file) fd.append('remove_image', '1');

    const id = modal.id;
    const ok = await run(
        () => (id ? adminApi.updateCandidate(props.event.id, id, fd) : adminApi.createCandidate(props.event.id, fd)),
        id ? 'Candidate updated' : `${modal.name} added`,
    );
    if (ok) {
        revokePreview();
        modal.open = false;
    }
}

/** Asks first, then removes with a few seconds to undo. */
async function removeCandidate(cand: Candidate) {
    const ok = await confirm({
        title: `Remove ${cand.name}?`,
        message: 'They come off the ballot, with their photo and description. You can add them again before voting starts.',
        confirmText: 'Remove candidate',
        danger: true,
    });
    if (!ok) return;
    toast.undoable(`${cand.name} removed`, {
        hide: () => { hidden.value = new Set(hidden.value).add(cand.id); },
        restore: () => { const next = new Set(hidden.value); next.delete(cand.id); hidden.value = next; },
        commit: async () => {
            await adminApi.deleteCandidate(props.event.id, cand.id);
            emit('changed');
        },
    });
}

onBeforeUnmount(revokePreview);

</script>

<style scoped>
.ballot-tools { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: flex-end; }
.ballot-tools:empty { display: none; }
.who-pill { border: 1px dashed var(--line-strong); background: none; border-radius: 999px; padding: .05rem .55rem; font-size: .78rem; color: var(--muted); display: inline-flex; gap: .3rem; align-items: center; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.who-pill:hover:not(:disabled) { border-color: var(--brand-500); color: var(--brand-600); }
.who-pill.limited { border-style: solid; border-color: var(--violet-200, #d9cffe); background: var(--violet-50, rgba(124, 92, 255, .08)); color: var(--violet-600, #6d4ce8); font-weight: 700; }
.who-pill:disabled { cursor: default; opacity: .85; }
.motion-text { white-space: pre-line; max-width: 640px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.cand-row.motion { padding-top: .55rem; padding-bottom: .55rem; }
.motion-ic { width: 34px; height: 34px; border-radius: 50%; display: inline-grid; place-items: center; flex-shrink: 0; font-size: 1.05rem; }
.motion-ic.yes { background: #ecfdf3; color: #15803d; }
.motion-ic.no { background: #fef3f2; color: #b42318; }
.motion-ic.abstain { background: var(--surface-2); color: var(--muted); }
.lock-banner { display: flex; gap: .7rem; align-items: flex-start; padding: .9rem 1.1rem; border-radius: var(--radius); background: var(--surface-2); border: 1px solid var(--line); color: var(--ink-2); font-size: .9rem; }
.lock-banner > .bi { color: var(--muted); margin-top: 2px; }
.cand-row { display: flex; align-items: center; gap: .85rem; padding: .75rem 1.25rem; border-bottom: 1px solid var(--line); }
.cand-row:last-child { border-bottom: 0; }
.cand-row:hover { background: var(--surface-2); }
.limit-select :deep(.as-trigger) { padding: .12rem .5rem; font-size: .8rem; border-radius: 8px; }
.shuffle-on { color: var(--violet-600, #7c5cff); background: var(--violet-50, rgba(124, 92, 255, .1)); }
.shuffle-pill { font-size: .7rem; padding: .1rem .45rem; }
.link-box { display: flex; align-items: center; gap: .5rem; padding: .5rem .5rem .5rem .8rem; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); }
.link-box .mono { flex: 1; min-width: 0; }
.cand-desc { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }
</style>
