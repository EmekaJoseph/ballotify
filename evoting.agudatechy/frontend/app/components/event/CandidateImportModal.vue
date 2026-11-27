<template>
    <BaseModal :open="open" title="Import candidates"
        subtitle="From an Excel or CSV file: one candidate per row. To type a few names, use Add candidates instead."
        size="lg" @close="emit('close')">
        <!-- A file only (Excel or CSV): no typing. Click to choose, or drop it here. -->
        <label class="dropzone" :class="{ over: dragOver, loaded: !!fileName }" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="onDrop">
            <input type="file" :accept="TABLE_FILE_ACCEPT" class="visually-hidden" @change="loadFile" />
            <i class="bi" :class="fileName ? 'bi-file-earmark-check' : 'bi-file-earmark-spreadsheet'"></i>
            <template v-if="fileName">
                <span class="fw-bold text-ink text-break">{{ fileName }}</span>
                <span class="small text-muted">Click to choose another file</span>
            </template>
            <template v-else>
                <span class="fw-bold text-ink">Choose an Excel or CSV file</span>
                <span class="small text-muted">or drop it here · columns Category, Name, Description</span>
            </template>
        </label>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
            <button type="button" class="btn btn-ghost btn-sm" title="A spreadsheet with the right columns and example rows. Fill it in (Excel is fine), then load it here."
                @click="downloadTemplate"><i class="bi bi-download"></i> Download template</button>
            <span class="small text-muted">
                {{ rows.length }} candidate{{ rows.length === 1 ? '' : 's' }} in {{ categoryCount }} categor{{ categoryCount === 1 ? 'y' : 'ies' }}
            </span>
        </div>
        <div class="form-text">
            Columns named <b>Category</b>, <b>Name</b> and <b>Description</b> are matched in any order.
            Missing categories are created. Names already on the ballot are skipped. Photos can be added afterwards.
        </div>

        <div v-if="rows.length || removed.size" class="mt-3 border rounded-3 overflow-hidden">
          <div class="preview">
            <table class="table-b small mb-0">
                <thead><tr><th>#</th><th>Category</th><th>Name</th><th>Description</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="(r, i) in rows.slice(0, PREVIEW_MAX)" :key="origin[i]">
                        <td class="text-muted">{{ i + 1 }}</td>
                        <td :class="{ 'text-danger': !r.category }">{{ r.category || 'missing' }}</td>
                        <td :class="{ 'text-danger': !r.name }">{{ r.name || 'missing' }}</td>
                        <td class="text-muted desc-cell" :class="{ 'text-danger': (r.description?.length ?? 0) > 280 }">{{ r.description || '—' }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" title="Leave this row out" :aria-label="`Leave out row ${i + 1}`" @click="removeRow(i)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
          </div>
            <div v-if="removed.size || rows.length > PREVIEW_MAX" class="d-flex flex-wrap gap-2 align-items-center small text-muted px-3 py-2 border-top">
                <span v-if="rows.length > PREVIEW_MAX">Showing the first {{ formatNumber(PREVIEW_MAX) }} of {{ formatNumber(rows.length) }}.</span>
                <span v-if="removed.size">{{ removed.size }} row{{ removed.size === 1 ? '' : 's' }} left out.</span>
                <button v-if="removed.size" type="button" class="btn btn-link btn-sm p-0" @click="restoreRows">Put back</button>
            </div>
        </div>

        <div v-if="problems" class="alert alert-warning border-0 rounded-4 small mt-3 mb-0">{{ problems }}</div>

        <template #footer>
            <button class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" :disabled="busy || !rows.length || !!problems" @click="submit">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                Import {{ rows.length || '' }} candidate{{ rows.length === 1 ? '' : 's' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { CandidateImportResult, CandidateImportRow } from '~/types';

const MAX_ROWS = 2000;
const props = defineProps<{ open: boolean; eventId: number }>();
const emit = defineEmits<{ close: []; imported: [result: CandidateImportResult] }>();

const text = ref('');
const busy = ref(false);
const error = ref('');
useErrorAlert(error, 'Import didn’t work');

watch(() => props.open, (o) => { if (o) { text.value = ''; fileName.value = ''; error.value = ''; } });

/**
 * Parse pasted lines or a CSV export. A header row is used when present;
 * otherwise columns are category, name, description. Unquoted commas in the
 * description are kept (everything after the name is the description).
 */
const parsed = computed<CandidateImportRow[]>(() => {
    const table = parseCsv(text.value);
    if (!table.length) return [];

    const header = table[0]!.map(h => h.toLowerCase());
    const idx = {
        category: header.findIndex(h => /categor|position|office|award|post/.test(h)),
        name: header.findIndex(h => /^(full ?)?name|candidate|nominee/.test(h)),
        description: header.findIndex(h => /descr|bio|about|summary|blurb|tagline/.test(h)),
    };
    const hasHeader = idx.category >= 0 && idx.name >= 0;
    const lastCol = header.length - 1;

    return (hasHeader ? table.slice(1) : table).map((cells) => {
        if (hasHeader) {
            const d = idx.description;
            const description = d < 0 ? '' : (d === lastCol ? cells.slice(d).filter(Boolean).join(', ') : cells[d] ?? '');
            return { category: cells[idx.category] ?? '', name: cells[idx.name] ?? '', description: description || null };
        }
        const [category = '', name = '', ...rest] = cells;
        return { category, name, description: rest.filter(Boolean).join(', ') || null };
    }).filter(r => r.category || r.name);
});

// ----- Removing single rows before importing (the file itself is not changed)
const PREVIEW_MAX = 500;
const removed = ref(new Set<number>());
/** Positions in the file of the rows still included. */
const origin = computed(() => parsed.value.map((_, i) => i).filter(i => !removed.value.has(i)));
const rows = computed(() => origin.value.map(i => parsed.value[i]!));
function removeRow(i: number) {
    const at = origin.value[i];
    if (at === undefined) return;
    removed.value = new Set(removed.value).add(at);
}
function restoreRows() { removed.value = new Set(); }
watch(text, () => { removed.value = new Set(); });

const categoryCount = computed(() => new Set(rows.value.filter(r => r.category).map(r => r.category.toLowerCase())).size);

const problems = computed(() => {
    if (rows.value.length > MAX_ROWS) return `That’s ${rows.value.length} rows. Import up to ${MAX_ROWS} at a time.`;
    const missing = rows.value.findIndex(r => !r.category || !r.name);
    if (missing >= 0) return `Row ${missing + 1} needs both a category and a name.`;
    const long = rows.value.findIndex(r => (r.description?.length ?? 0) > 280);
    if (long >= 0) return `The description in row ${long + 1} is longer than 280 characters.`;
    return '';
});

const fileName = ref('');
const dragOver = ref(false);
function onDrop(e: DragEvent) {
    dragOver.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) readFile(file);
}
async function loadFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (file) await readFile(file);
}
async function readFile(file: File) {
    if (file.size > 3 * 1024 * 1024) { error.value = 'That file is larger than 3 MB.'; return; }
    fileName.value = file.name;
    try { text.value = await readTableFile(file); error.value = ''; }
    catch (err) { fileName.value = ''; text.value = ''; error.value = err instanceof Error ? err.message : 'That file couldn’t be read.'; }
}

function downloadTemplate() {
    downloadFile('candidates-template.csv', toCsv([
        ['Category', 'Name', 'Description'],
        ['President', 'Ada Obi', 'Member since 2015'],
        ['President', 'Bola Ade', ''],
        ['Treasurer', 'Chidi Eze', 'Chartered accountant'],
    ]));
}

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.importCandidates(props.eventId, rows.value);
        emit('imported', data);
    } catch (e) {
        error.value = apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}
</script>

<style scoped>
.preview { max-height: 340px; overflow: auto; }
.preview thead th { position: sticky; top: 0; z-index: 1; }
.dropzone {
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .2rem; text-align: center; cursor: pointer;
    padding: 1.6rem 1rem; border: 2px dashed var(--line-strong); border-radius: 14px; background: var(--surface-2);
    transition: border-color .15s ease, background-color .15s ease;
}
.dropzone:hover, .dropzone.over { border-color: var(--brand-500); background: var(--brand-50); }
.dropzone.loaded { border-style: solid; border-color: var(--brand-500); }
.dropzone > .bi { font-size: 1.8rem; color: var(--brand-600); }
.desc-cell { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>
