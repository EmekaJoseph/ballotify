<template>
    <BaseModal :open="open" title="Import voters" subtitle="One person per line: name, email, phone. Email and phone are optional."
        size="lg" @close="emit('close')">
        <textarea v-model="text" class="form-control mono small" rows="7"
            placeholder="Ada Obi, ada@example.com, 08012345678&#10;Bola Ade, bola@example.com&#10;Chidi Eze, , +2348098765432"></textarea>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
            <label class="btn btn-ghost btn-sm mb-0">
                <i class="bi bi-filetype-csv"></i> Load a .csv file
                <input type="file" accept=".csv,.txt,text/csv,text/plain" class="visually-hidden" @change="loadFile" />
            </label>
            <span class="small text-muted">
                {{ rows.length }} voter{{ rows.length === 1 ? '' : 's' }} ·
                <i class="bi bi-envelope"></i> {{ withEmail }} · <i class="bi bi-phone"></i> {{ withPhone }}
            </span>
        </div>
        <div class="form-text">
            Spreadsheet exports work too. Columns named <b>Name</b>, <b>Email</b>, <b>Phone</b> and an <b>ID</b> column (e.g. Matric number, Member no.) are matched in any order.
            The ID lets voters request their own code if you switch on self-registration.
            Local numbers like 0801… are converted using your country code.
        </div>

        <div v-if="rows.length" class="table-responsive mt-3 border rounded-3">
            <table class="table-b small">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th v-if="withId">ID</th></tr></thead>
                <tbody>
                    <tr v-for="(r, i) in rows.slice(0, 5)" :key="i">
                        <td class="text-muted">{{ i + 1 }}</td>
                        <td>{{ r.name || '—' }}</td>
                        <td :class="{ 'text-danger': r.email && !emailOk(r.email) }">{{ r.email || '—' }}</td>
                        <td>{{ r.phone || '—' }}</td>
                        <td v-if="withId" class="mono">{{ r.member_id || '—' }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="rows.length > 5" class="small text-muted px-3 py-2 border-top">…and {{ rows.length - 5 }} more</div>
        </div>


        <template #footer>
            <button class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" :disabled="busy || !rows.length || rows.length > 10000" @click="submit">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                Create {{ rows.length || '' }} code{{ rows.length === 1 ? '' : 's' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { GeneratedCode, VoterContact } from '~/types';

const props = defineProps<{ open: boolean; eventId: number }>();
const emit = defineEmits<{ close: []; created: [codes: GeneratedCode[]]; limit: [] }>();

const text = ref('');
const busy = ref(false);
const error = ref('');
useErrorAlert(error, 'Import didn’t work');

watch(() => props.open, (o) => { if (o) { text.value = ''; error.value = ''; } });

const emailOk = (e: string) => /^\S+@\S+\.\S+$/.test(e);
const looksLikeEmail = (s: string) => s.includes('@');
const looksLikePhone = (s: string) => /^[+\d][\d\s\-().]{6,}$/.test(s);

/** Parse lines/CSV into contacts. Uses a header row when present, otherwise guesses each cell. */
const rows = computed<VoterContact[]>(() => {
    const table = parseCsv(text.value);
    if (!table.length) return [];

    const header = table[0]!.map(h => h.toLowerCase());
    const idx = {
        name: header.findIndex(h => /^(full ?)?name|voter/.test(h)),
        email: header.findIndex(h => /e-?mail/.test(h)),
        member_id: header.findIndex(h => /matric|member|staff|student|reg(istration)?\.? ?(no|num)|\bid\b|identification/.test(h)),
        phone: -1,
    };
    // Phone: a phone-like column that isn't the ID column ("Member number" is an ID, not a phone).
    idx.phone = header.findIndex((h, i) => i !== idx.member_id && /phone|mobile|tel|whatsapp|^number/.test(h));
    const hasHeader = idx.name >= 0 || idx.email >= 0 || idx.phone >= 0 || idx.member_id >= 0;

    return (hasHeader ? table.slice(1) : table).map((cells) => {
        if (hasHeader) {
            return {
                name: idx.name >= 0 ? cells[idx.name] || null : null,
                email: idx.email >= 0 ? cells[idx.email] || null : null,
                phone: idx.phone >= 0 ? cells[idx.phone] || null : null,
                member_id: idx.member_id >= 0 ? cells[idx.member_id] || null : null,
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

const withEmail = computed(() => rows.value.filter(r => r.email).length);
const withPhone = computed(() => rows.value.filter(r => r.phone).length);
const withId = computed(() => rows.value.some(r => r.member_id));

async function loadFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) { error.value = 'That file is larger than 3 MB.'; return; }
    text.value = await file.text();
}

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.importVoters(props.eventId, { voters: rows.value });
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
