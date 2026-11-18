<template>
    <div>
        <div v-if="(event.voted_count ?? 0) > 0" class="alert border-0 rounded-4 d-flex gap-2 small mb-4" style="background: var(--warning-50); color: #92400e">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <span>Votes have already been cast. Deleting a category or candidate also deletes the votes it received.</span>
        </div>

        <!-- Add category -->
        <form class="card-b pad mb-4 d-flex flex-column flex-sm-row gap-2" @submit.prevent="addCategory">
            <div class="input-icon flex-grow-1">
                <i class="bi bi-folder-plus"></i>
                <input v-model.trim="newCategory" type="text" class="form-control" maxlength="255"
                    placeholder="New category, e.g. President" aria-label="New category name" :disabled="busy" />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="busy || !newCategory">
                <i class="bi bi-plus-lg"></i> Add category
            </button>
        </form>

        <EmptyState v-if="!categories.length" class="card-b" icon="bi-ui-checks-grid" title="Your ballot is empty"
            text="Start with a category such as “President” or “Best Actor”, then add candidates to it." />

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
                            <h3 class="h6 fw-800 mb-0 text-break">{{ cat.name }}</h3>
                            <div class="small text-muted">{{ candidatesOf(cat.id).length }} candidate{{ candidatesOf(cat.id).length === 1 ? '' : 's' }}</div>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-soft btn-sm" @click="openCandidate(null, cat.id)"><i class="bi bi-person-plus"></i> <span class="d-none d-sm-inline">Candidate</span></button>
                            <button class="btn btn-ghost btn-icon btn-sm" title="Rename" aria-label="Rename category" @click="editingCat = cat.id; catDraft = cat.name"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-ghost btn-icon btn-sm text-danger" title="Delete" aria-label="Delete category" @click="removeCategory(cat)"><i class="bi bi-trash"></i></button>
                        </div>
                    </template>
                </header>

                <div v-if="!candidatesOf(cat.id).length" class="p-4 text-center small text-muted">
                    No candidates yet. <button class="btn btn-link btn-sm p-0 align-baseline" @click="openCandidate(null, cat.id)">Add the first one</button>
                </div>
                <ul v-else class="list-unstyled mb-0">
                    <li v-for="cand in candidatesOf(cat.id)" :key="cand.id" class="cand-row">
                        <CandidateAvatar :name="cand.name" :src="cand.image_url" />
                        <span class="fw-semibold text-ink flex-grow-1 min-w-0 text-break">{{ cand.name }}</span>
                        <button class="btn btn-ghost btn-icon btn-sm" title="Edit" aria-label="Edit candidate" @click="openCandidate(cand)"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-ghost btn-icon btn-sm text-danger" title="Delete" aria-label="Delete candidate" @click="removeCandidate(cand)"><i class="bi bi-trash"></i></button>
                    </li>
                </ul>
            </section>
        </div>

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
                <div>
                    <label for="cand-cat" class="form-label">Category</label>
                    <select id="cand-cat" v-model.number="modal.categoryId" class="form-select">
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
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
import type { BallotEvent, Candidate, Category } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ changed: [] }>();

const toast = useToast();
const { confirm } = useConfirm();
const busy = ref(false);
const newCategory = ref('');
const editingCat = ref<number | null>(null);
const catDraft = ref('');

const categories = computed<Category[]>(() => props.event.categories ?? []);
const candidatesOf = (catId: number): Candidate[] => (props.event.candidates ?? []).filter(c => c.category_id === catId);

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

async function removeCategory(cat: Category) {
    const n = candidatesOf(cat.id).length;
    const ok = await confirm({
        title: `Delete “${cat.name}”?`,
        message: n ? `Its ${n} candidate${n === 1 ? '' : 's'} and any votes in this category will be deleted too.` : 'This category will be removed from the ballot.',
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

async function removeCandidate(cand: Candidate) {
    const ok = await confirm({
        title: `Remove ${cand.name}?`,
        message: 'The candidate and any votes they received will be deleted.',
        confirmText: 'Remove',
        danger: true,
    });
    if (ok) await run(() => adminApi.deleteCandidate(props.event.id, cand.id), 'Candidate removed');
}

onBeforeUnmount(revokePreview);
</script>

<style scoped>
.cand-row { display: flex; align-items: center; gap: .85rem; padding: .75rem 1.25rem; border-bottom: 1px solid var(--line); }
.cand-row:last-child { border-bottom: 0; }
.cand-row:hover { background: var(--surface-2); }
</style>
