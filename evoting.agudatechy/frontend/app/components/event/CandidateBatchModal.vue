<template>
    <BaseModal :open="open" title="Add candidates" subtitle="Fill in one line and press Enter. Add as many as you like, then save them all at once."
        size="lg" @close="close">
        <!-- One-line entry: category, name, description. Enter adds it to the list below. -->
        <form class="entry" novalidate @submit.prevent="addRow">
            <div class="f-cat">
                <label class="form-label small mb-1" for="cb-cat">Category</label>
                <AppSelect id="cb-cat" v-model="draft.categoryId" :options="categoryOptions" placeholder="Choose…" />
            </div>
            <div class="f-name">
                <label class="form-label small mb-1" for="cb-name">Name</label>
                <input id="cb-name" ref="nameInput" v-model="draft.name" class="form-control" maxlength="255" placeholder="e.g. Ada Obi" autocomplete="off" />
            </div>
            <div class="f-desc">
                <label class="form-label small mb-1" for="cb-desc">Description <span class="text-muted fw-normal">(optional)</span></label>
                <input id="cb-desc" v-model="draft.description" class="form-control" maxlength="280" placeholder="e.g. Member since 2015" autocomplete="off" />
            </div>
            <button type="submit" class="btn btn-soft f-add" :disabled="!draft.categoryId || !draft.name.trim()">
                <i class="bi bi-plus-lg"></i> Add
            </button>
        </form>
        <div v-if="entryError" class="field-error mt-2" role="alert">{{ entryError }}</div>
        <div class="form-text">Press <kbd>Enter</kbd> to add. The category stays selected for the next one. Photos can be added afterwards.</div>

        <!-- The list waiting to be saved -->
        <div class="mt-3 border rounded-3 overflow-hidden">
            <div v-if="!rows.length" class="text-center text-muted small py-4">Nothing added yet.</div>
            <div v-else class="table-responsive">
                <table class="table-b small mb-0">
                    <thead><tr><th style="width: 40px">#</th><th>Category</th><th>Name</th><th>Description</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="(r, i) in rows" :key="r.key">
                            <td class="text-muted">{{ i + 1 }}</td>
                            <td class="text-break">{{ categoryName(r.categoryId) }}</td>
                            <td class="fw-semibold text-ink text-break">{{ r.name }}</td>
                            <td class="text-muted text-break">{{ r.description || '—' }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-ghost btn-icon btn-sm" :aria-label="`Remove ${r.name}`" title="Remove" @click="rows.splice(i, 1)">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-if="error" class="field-error mt-2">{{ error }}</div>

        <template #footer>
            <span class="small text-muted me-auto align-self-center">{{ rows.length }} to add</span>
            <button class="btn btn-ghost" :disabled="busy" @click="close">Cancel</button>
            <button class="btn btn-primary" :disabled="busy || !rows.length" @click="save">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                Save {{ rows.length || '' }} candidate{{ rows.length === 1 ? '' : 's' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { Candidate, CandidateImportResult, Category } from '~/types';

const props = defineProps<{
    open: boolean;
    eventId: number;
    categories: Category[];
    /** Candidates already on the ballot (to refuse duplicates). */
    candidates: Candidate[];
    /** Category to start with (e.g. the one whose "Candidate" button was clicked). */
    initialCategoryId?: number | null;
}>();
const emit = defineEmits<{ close: []; saved: [result: CandidateImportResult] }>();
const { confirm } = useConfirm();

interface Row { key: number; categoryId: number; name: string; description: string }
const rows = ref<Row[]>([]);
const draft = reactive({ categoryId: 0, name: '', description: '' });
const entryError = ref('');
const error = ref('');
const busy = ref(false);
const nameInput = ref<HTMLInputElement | null>(null);
let nextKey = 1;

const categoryOptions = computed(() => props.categories.map(c => ({ value: c.id, label: c.name })));
const categoryName = (id: number) => props.categories.find(c => c.id === id)?.name ?? '';
/** "Ada  OBI" and "ada obi" are the same name. */
const key = (name: string) => name.replace(/\s+/g, ' ').trim().toLowerCase();

watch(() => props.open, (o) => {
    if (!o) return;
    rows.value = [];
    entryError.value = '';
    error.value = '';
    Object.assign(draft, { categoryId: props.initialCategoryId ?? categoryOptions.value[0]?.value ?? 0, name: '', description: '' });
    nextTick(() => nameInput.value?.focus());
});
watch(() => [draft.name, draft.categoryId], () => { entryError.value = ''; });

function addRow() {
    const name = draft.name.replace(/\s+/g, ' ').trim();
    if (!draft.categoryId || name.length < 1) return;
    const cat = categoryName(draft.categoryId);
    // No duplicates: not twice in this list, and not someone already on the ballot in that category.
    if (rows.value.some(r => r.categoryId === draft.categoryId && key(r.name) === key(name))) {
        entryError.value = `${name} is already in your list for “${cat}”.`;
        return;
    }
    if (props.candidates.some(c => c.category_id === draft.categoryId && key(c.name) === key(name))) {
        entryError.value = `${name} is already on the ballot in “${cat}”.`;
        return;
    }
    rows.value.push({ key: nextKey++, categoryId: draft.categoryId, name, description: draft.description.trim() });
    draft.name = '';
    draft.description = '';
    nextTick(() => nameInput.value?.focus());
}

async function save() {
    // Something typed but not added yet: add it too, rather than lose it.
    if (draft.name.trim()) addRow();
    if (entryError.value || !rows.value.length) return;
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.importCandidates(props.eventId, rows.value.map(r => ({
            category: categoryName(r.categoryId), name: r.name, description: r.description || null,
        })));
        emit('saved', data);
    } catch (e) {
        error.value = apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

async function close() {
    if (rows.value.length && !busy.value) {
        const ok = await confirm({
            title: 'Discard this list?',
            message: `${rows.value.length} candidate${rows.value.length === 1 ? ' hasn’t' : 's haven’t'} been saved yet.`,
            confirmText: 'Discard', danger: true,
        });
        if (!ok) return;
    }
    emit('close');
}
</script>

<style scoped>
.entry { display: grid; grid-template-columns: minmax(150px, 1fr) minmax(150px, 1.2fr) minmax(150px, 1.6fr) auto; gap: .6rem; align-items: end; }
.f-add { height: 46px; white-space: nowrap; }
@media (max-width: 767.98px) {
    .entry { grid-template-columns: 1fr; }
    .f-add { width: 100%; }
}
kbd { font-size: .72rem; padding: .05rem .35rem; border-radius: 4px; background: var(--surface-2); color: var(--ink); border: 1px solid var(--line); }
</style>
