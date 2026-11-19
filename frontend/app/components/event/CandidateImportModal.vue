<template>
    <BaseModal :open="open" title="Import candidates"
        subtitle="One candidate per line: category, name, description. The description is optional."
        size="lg" @close="emit('close')">
        <textarea v-model="text" class="form-control mono small" rows="7"
            placeholder="President, Ada Obi, Final-year Law student&#10;President, Bola Ade&#10;Treasurer, Chidi Eze, Runs the finance society"></textarea>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
            <div class="d-flex flex-wrap gap-2">
                <label class="btn btn-ghost btn-sm mb-0">
                    <i class="bi bi-filetype-csv"></i> Load a .csv file
                    <input type="file" accept=".csv,.txt,text/csv,text/plain" class="visually-hidden" @change="loadFile" />
                </label>
                <button type="button" class="btn btn-ghost btn-sm" @click="downloadTemplate"><i class="bi bi-download"></i> Template</button>
            </div>
            <span class="small text-muted">
                {{ rows.length }} candidate{{ rows.length === 1 ? '' : 's' }} in {{ categoryCount }} categor{{ categoryCount === 1 ? 'y' : 'ies' }}
            </span>
        </div>
        <div class="form-text">
            Spreadsheet exports work too: columns named <b>Category</b>, <b>Name</b> and <b>Description</b> are matched in any order.
            Missing categories are created. Names already on the ballot are skipped. Photos can be added afterwards.
        </div>

        <div v-if="rows.length" class="table-responsive mt-3 border rounded-3">
            <table class="table-b small">
                <thead><tr><th>#</th><th>Category</th><th>Name</th><th>Description</th></tr></thead>
                <tbody>
                    <tr v-for="(r, i) in rows.slice(0, 6)" :key="i">
                        <td class="text-muted">{{ i + 1 }}</td>
                        <td :class="{ 'text-danger': !r.category }">{{ r.category || 'missing' }}</td>
                        <td :class="{ 'text-danger': !r.name }">{{ r.name || 'missing' }}</td>
                        <td class="text-muted desc-cell" :class="{ 'text-danger': (r.description?.length ?? 0) > 280 }">{{ r.description || '—' }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="rows.length > 6" class="small text-muted px-3 py-2 border-top">…and {{ rows.length - 6 }} more</div>
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

watch(() => props.open, (o) => { if (o) { text.value = ''; error.value = ''; } });

/**
 * Parse pasted lines or a CSV export. A header row is used when present;
 * otherwise columns are category, name, description. Unquoted commas in the
 * description are kept (everything after the name is the description).
 */
const rows = computed<CandidateImportRow[]>(() => {
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

const categoryCount = computed(() => new Set(rows.value.filter(r => r.category).map(r => r.category.toLowerCase())).size);

const problems = computed(() => {
    if (rows.value.length > MAX_ROWS) return `That’s ${rows.value.length} rows. Import up to ${MAX_ROWS} at a time.`;
    const missing = rows.value.findIndex(r => !r.category || !r.name);
    if (missing >= 0) return `Row ${missing + 1} needs both a category and a name.`;
    const long = rows.value.findIndex(r => (r.description?.length ?? 0) > 280);
    if (long >= 0) return `The description in row ${long + 1} is longer than 280 characters.`;
    return '';
});

async function loadFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) { error.value = 'That file is larger than 3 MB.'; return; }
    text.value = await file.text();
}

function downloadTemplate() {
    downloadFile('candidates-template.csv', toCsv([
        ['Category', 'Name', 'Description'],
        ['President', 'Ada Obi', 'Final-year Law student'],
        ['President', 'Bola Ade', ''],
        ['Treasurer', 'Chidi Eze', 'Runs the finance society'],
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
.desc-cell { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>
