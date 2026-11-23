<template>
    <BaseModal :open="open" :title="category ? `Who can vote in “${category.name}”` : ''" size="md" @close="emit('close')">
        <div class="d-grid gap-2 mb-3">
            <label class="choice" :class="{ selected: mode === 'all' }">
                <input v-model="mode" type="radio" value="all" class="form-check-input mt-0" />
                <span><b>Everyone</b><span class="d-block small text-muted">Every voter on the list sees this category.</span></span>
            </label>
            <label class="choice" :class="{ selected: mode === 'some' }">
                <input v-model="mode" type="radio" value="some" class="form-check-input mt-0" />
                <span><b>Only some groups</b><span class="d-block small text-muted">E.g. only members of the Lagos branch vote for the Lagos Branch Rep.</span></span>
            </label>
        </div>

        <template v-if="mode === 'some'">
            <div v-if="loading" class="skeleton" style="height: 90px"></div>
            <template v-else>
                <div v-if="options.length" class="group-grid mb-3">
                    <label v-for="g in options" :key="g.name" class="group-opt" :class="{ on: picked.has(g.name.toLowerCase()) }">
                        <input type="checkbox" class="form-check-input mt-0" :checked="picked.has(g.name.toLowerCase())" @change="toggle(g.name)" />
                        <span class="flex-grow-1 text-truncate">{{ g.name }}</span>
                        <span class="small text-muted">{{ g.voters }} voter{{ g.voters === 1 ? '' : 's' }}</span>
                    </label>
                </div>
                <p v-else class="small text-muted">No voter has a group yet. Add groups to voters (a <b>Group</b> column when importing, or the Groups field), or type one below.</p>
                <form class="d-flex gap-2" @submit.prevent="addNew">
                    <input v-model.trim="newGroup" class="form-control form-control-sm" maxlength="40" placeholder="Add a group, e.g. Youth" aria-label="New group" />
                    <button class="btn btn-soft btn-sm" :disabled="!newGroup">Add</button>
                </form>
                <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle"></i> Voters in <b>any</b> of the ticked groups can vote here.</p>
            </template>
        </template>
        <div v-if="error" class="field-error mt-3">{{ error }}</div>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button type="button" class="btn btn-primary" :disabled="busy || (mode === 'some' && !picked.size)" @click="save">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span> Save
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { Category, VoterGroup } from '~/types';

const props = defineProps<{ open: boolean; eventId: number; category: Category | null }>();
const emit = defineEmits<{ close: []; saved: [] }>();
const toast = useToast();

const mode = ref<'all' | 'some'>('all');
const groups = ref<VoterGroup[]>([]);
const extra = ref<string[]>([]);
/** Ticked groups, by lower-case name; values keep the display spelling. */
const picked = ref(new Map<string, string>());
const newGroup = ref('');
const loading = ref(false);
const busy = ref(false);
const error = ref('');

const options = computed(() => {
    const list = [...groups.value];
    for (const g of [...extra.value, ...(props.category?.groups ?? [])]) {
        if (!list.some(x => x.name.toLowerCase() === g.toLowerCase())) list.push({ name: g, voters: 0 });
    }
    return list.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }));
});

watch(() => props.open, async (o) => {
    if (!o || !props.category) return;
    const current = props.category.groups ?? [];
    mode.value = current.length ? 'some' : 'all';
    picked.value = new Map(current.map(g => [g.toLowerCase(), g]));
    extra.value = [];
    error.value = '';
    loading.value = true;
    try { groups.value = (await adminApi.voterGroups(props.eventId)).data; } catch { groups.value = []; } finally { loading.value = false; }
});

function toggle(name: string) {
    const key = name.toLowerCase();
    const next = new Map(picked.value);
    next.has(key) ? next.delete(key) : next.set(key, name);
    picked.value = next;
}
function addNew() {
    if (!newGroup.value) return;
    if (!options.value.some(g => g.name.toLowerCase() === newGroup.value.toLowerCase())) extra.value.push(newGroup.value);
    if (!picked.value.has(newGroup.value.toLowerCase())) toggle(newGroup.value);
    newGroup.value = '';
}

async function save() {
    if (!props.category) return;
    busy.value = true;
    error.value = '';
    try {
        const list = mode.value === 'all' ? [] : [...picked.value.values()];
        await adminApi.updateCategory(props.eventId, props.category.id, { groups: list });
        toast.success(list.length ? `“${props.category.name}”: only ${list.join(', ')}` : `“${props.category.name}”: everyone can vote`);
        emit('saved');
    } catch (e) {
        error.value = Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}
</script>

<style scoped>
.choice { display: flex; gap: .75rem; align-items: flex-start; padding: .75rem .9rem; border: 1.5px solid var(--line); border-radius: 12px; cursor: pointer; }
.choice.selected { border-color: var(--brand-500); background: var(--brand-50); }
.group-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: .5rem; max-height: 260px; overflow-y: auto; }
.group-opt { display: flex; align-items: center; gap: .55rem; padding: .5rem .7rem; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; font-size: .9rem; min-width: 0; }
.group-opt.on { border-color: var(--brand-500); background: var(--brand-50); }
</style>
