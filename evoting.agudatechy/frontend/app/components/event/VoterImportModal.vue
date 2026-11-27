<template>
    <BaseModal :open="open" title="Import voters" subtitle="From an Excel or CSV file: one person per row. To type a few names, use Add instead."
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
                <span class="small text-muted">or drop it here · columns Name, Email, Phone…</span>
            </template>
        </label>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
            <button type="button" class="btn btn-ghost btn-sm" title="A spreadsheet with the right columns and example rows. Fill it in (Excel is fine), then load it here."
                @click="downloadTemplate"><i class="bi bi-download"></i> Download template</button>
            <span class="small text-muted">
                {{ rows.length }} voter{{ rows.length === 1 ? '' : 's' }} ·
                <i class="bi bi-envelope"></i> {{ withEmail }} · <i class="bi bi-phone"></i> {{ withPhone }}
            </span>
        </div>
        <div class="form-text">
            Columns named <b>Name</b>, <b>Email</b>, <b>Phone</b>, an <b>ID</b> column (e.g. Membership number, Staff ID)
            and a <b>Group</b> column (e.g. branch or unit; several separated by “;”) are matched in any order.
            <template v-if="weighted"> A <b>Weight</b> (or Shares) column sets how many votes each ballot counts as; blank means 1.</template>
            The ID lets voters request their own code if you switch on self-registration.
            Local numbers like 0801… are converted using your country code.
        </div>

        <div v-if="rows.length || removed.size" class="mt-3 border rounded-3 overflow-hidden">
          <div class="preview">
            <table class="table-b small mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th v-if="withId">ID</th><th v-if="withGroups">Groups</th><th v-if="withWeight">Weight</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="(r, i) in rows.slice(0, PREVIEW_MAX)" :key="origin[i]">
                        <td class="text-muted">{{ i + 1 }}</td>
                        <td>
                            {{ r.name || '—' }}
                            <i v-if="rowMatches.get(i)" class="bi bi-files text-warning ms-1" :title="rowMatches.get(i)!.map(describeMatch).join('\n')"></i>
                        </td>
                        <td :class="{ 'text-danger': r.email && !emailOk(r.email) }">{{ r.email || '—' }}</td>
                        <td>{{ r.phone || '—' }}</td>
                        <td v-if="withId" class="mono">{{ r.member_id || '—' }}</td>
                        <td v-if="withGroups">{{ r.groups || '—' }}</td>
                        <td v-if="withWeight" class="mono">{{ r.weight || '1' }}</td>
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

        <!-- Possible duplicates: in this file, or already on the voter list (warned, not blocked) -->
        <div v-if="rowMatches.size" class="dup-panel mt-3">
            <div class="fw-bold mb-1"><i class="bi bi-files"></i> {{ rowMatches.size }} row{{ rowMatches.size === 1 ? '' : 's' }} may be duplicates</div>
            <ul class="mb-2">
                <li v-for="[i, m] in [...rowMatches].slice(0, 6)" :key="i">
                    <b>Row {{ i + 1 }}{{ rows[i]?.name ? ` (${rows[i]!.name})` : '' }}:</b> {{ describeMatch(m[0]!) }}<template v-if="m.length > 1"> and {{ m.length - 1 }} more</template>
                </li>
                <li v-if="rowMatches.size > 6" class="text-muted">…and {{ rowMatches.size - 6 }} more</li>
            </ul>
            <div class="small mb-2">Someone listed twice gets two codes. Same name only, or a shared phone, is often fine: two people, or one family phone.</div>
            <div v-if="alreadyListed.size" class="form-check mb-0">
                <input id="imp-skip" v-model="skipListed" class="form-check-input" type="checkbox" />
                <label for="imp-skip" class="form-check-label small fw-semibold">
                    Leave out the {{ alreadyListed.size }} row{{ alreadyListed.size === 1 ? '' : 's' }} already on the voter list (same name and same email or phone)
                </label>
            </div>
        </div>


        <template #footer>
            <button class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" :disabled="busy || !toCreate.length || toCreate.length > 10000" @click="submit">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                Create {{ toCreate.length || '' }} code{{ toCreate.length === 1 ? '' : 's' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { GeneratedCode, Voter, VoterContact } from '~/types';
import type { DuplicateMatch } from '~/utils/duplicates';

const props = withDefaults(defineProps<{ open: boolean; eventId: number; memberLabel?: string; weighted?: boolean; existing?: Voter[] }>(),
    { memberLabel: 'Member ID', weighted: false, existing: () => [] });

/** A ready-to-fill spreadsheet: the columns this import recognises, with example rows. */
function downloadTemplate() {
    // The ID column keeps the event's own label when the import will recognise it.
    const idHeader = /matric|member|staff|student|reg(istration)?\.? ?(no|num)|id|identification/i.test(props.memberLabel) ? props.memberLabel : 'Member ID';
    // Weighted votes: a Weight column (shares, units...).
    const w = (n: string) => (props.weighted ? [n] : []);
    downloadFile('voters-template.csv', toCsv([
        ['Name', 'Email', 'Phone', idHeader, 'Group', ...w('Weight')],
        ['Ada Obi', 'ada@example.com', '0801 234 5678', 'MEM-0001', 'Lagos; Youth', ...w('40')],
        ['Bola Ade', 'bola@example.com', '', 'MEM-0002', 'Abuja', ...w('25')],
        ['Chidi Eze', '', '0809 876 5432', 'MEM-0003', '', ...w('1')],
    ]));
}
const emit = defineEmits<{ close: []; created: [codes: GeneratedCode[]]; limit: [] }>();

const text = ref('');
const busy = ref(false);
const error = ref('');
useErrorAlert(error, 'Import didn’t work');

watch(() => props.open, (o) => { if (o) { text.value = ''; fileName.value = ''; error.value = ''; skipListed.value = true; } });

const emailOk = (e: string) => /^\S+@\S+\.\S+$/.test(e);
const looksLikeEmail = (s: string) => s.includes('@');
const looksLikePhone = (s: string) => /^[+\d][\d\s\-().]{6,}$/.test(s);

/** Parse lines/CSV into contacts. Uses a header row when present, otherwise guesses each cell. */
const parsed = computed<VoterContact[]>(() => {
    const table = parseCsv(text.value);
    if (!table.length) return [];

    const header = table[0]!.map(h => h.toLowerCase());
    const idx = {
        name: header.findIndex(h => /^(full ?)?name|voter/.test(h)),
        email: header.findIndex(h => /e-?mail/.test(h)),
        member_id: header.findIndex(h => /matric|member|staff|student|reg(istration)?\.? ?(no|num)|\bid\b|identification/.test(h)),
        phone: -1,
        // "Group", "Branch", "Unit", "Department", "Zone", "Level"…: all go into groups.
        groups: [] as number[],
        // Weighted votes: "Weight", "Shares", "Votes", "Holding".
        weight: header.findIndex(h => /weight|shares?|^votes?$|holding/.test(h)),
    };
    header.forEach((h, i) => { if (i !== idx.weight && /^groups?$|branch|chapter|unit|department|dept|zone|district|region|parish|assembly|fellowship|ministry|cell|ward|faculty|level|class\b|house/.test(h)) idx.groups.push(i); });
    // Phone: a phone-like column that isn't the ID column ("Member number" is an ID, not a phone).
    idx.phone = header.findIndex((h, i) => i !== idx.member_id && /phone|mobile|tel|whatsapp|^number/.test(h));
    const hasHeader = idx.name >= 0 || idx.email >= 0 || idx.phone >= 0 || idx.member_id >= 0 || idx.groups.length > 0 || idx.weight >= 0;

    return (hasHeader ? table.slice(1) : table).map((cells) => {
        if (hasHeader) {
            return {
                name: idx.name >= 0 ? cells[idx.name] || null : null,
                email: idx.email >= 0 ? cells[idx.email] || null : null,
                phone: idx.phone >= 0 ? cells[idx.phone] || null : null,
                member_id: idx.member_id >= 0 ? cells[idx.member_id] || null : null,
                groups: idx.groups.map(i => (cells[i] ?? '').trim()).filter(Boolean).join('; ') || null,
                ...(idx.weight >= 0 ? { weight: (cells[idx.weight] ?? '').trim() || null } : {}),
            };
        }
        const row: VoterContact = { name: null, email: null, phone: null };
        for (const cell of cells) {
            if (!cell) continue;
            if (!row.email && looksLikeEmail(cell)) row.email = cell;
            else if (!row.phone && looksLikePhone(cell)) row.phone = cell;
            else if (!row.name) row.name = cell;
        }
        return row;
    }).filter(r => r.name || r.email || r.phone || r.member_id);
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

const withEmail = computed(() => rows.value.filter(r => r.email).length);
const withPhone = computed(() => rows.value.filter(r => r.phone).length);
const withId = computed(() => rows.value.some(r => r.member_id));
const withGroups = computed(() => rows.value.some(r => r.groups));
const withWeight = computed(() => rows.value.some(r => r.weight));

// ----- Possible duplicates
const skipListed = ref(true);
const existingLabel = (v: Voter) => `${v.name || v.email || v.phone || 'a voter'}, already on the list`;
/** Row index => what it looks like: other rows of this file, then voters already on the list. */
const rowMatches = computed(() => {
    const out = new Map<number, DuplicateMatch[]>();
    const inFile = findDuplicates(rows.value, (r, j) => `row ${j + 1}${r.name ? ` (${r.name})` : ''}`);
    rows.value.forEach((_, i) => {
        const m = [...(listed.value.get(i) ?? []), ...(inFile.get(i) ?? [])];
        if (m.length) out.set(i, sortMatches(m));
    });
    return out;
});
/** Matches against voters already on the list. */
const listed = computed(() => crossMatches(rows.value, props.existing, existingLabel));
/** Rows that are almost certainly someone already on the list. */
const alreadyListed = computed(() => new Set([...listed.value].filter(([, m]) => m[0]!.kind === 'same').map(([i]) => i)));
const toCreate = computed(() => (skipListed.value ? rows.value.filter((_, i) => !alreadyListed.value.has(i)) : rows.value));

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

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.importVoters(props.eventId, { voters: toCreate.value });
        emit('created', data.voters);
    } catch (e) {
        // Free plan: a second live event. The parent explains the options in a modal.
        if (planLimitReason(e) === 'events') { emit('limit'); return; }
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
.dup-panel { padding: .75rem .9rem; border-radius: 12px; background: var(--warning-50); color: var(--warning-text); font-size: .875rem; overflow-wrap: anywhere; }
.dup-panel ul { padding-left: 1.1rem; }
.dup-panel .form-check-label { color: var(--ink); }
</style>
