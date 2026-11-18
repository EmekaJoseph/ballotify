<template>
    <div class="row g-4">
        <div class="col-xl-8 d-grid gap-4">
            <div class="row g-3">
                <div class="col-6 col-md-3"><StatCard label="Categories" :value="event.categories?.length ?? 0" icon="bi-grid" /></div>
                <div class="col-6 col-md-3"><StatCard label="Candidates" :value="event.candidates?.length ?? 0" icon="bi-person-badge" tone="violet" /></div>
                <div class="col-6 col-md-3"><StatCard label="Voters" :value="formatNumber(event.voters_count)" icon="bi-people" tone="amber" /></div>
                <div class="col-6 col-md-3"><StatCard label="Voted" :value="formatNumber(event.voted_count)" icon="bi-check2-square" tone="green"
                    :hint="event.voters_count ? `${percent(event.voted_count ?? 0, event.voters_count)}% turnout` : ''" /></div>
            </div>

            <div class="card-b pad">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">
                    <h3 class="h6 fw-800 mb-0">Turnout</h3>
                    <span class="small text-muted">{{ formatNumber(event.voted_count) }} of {{ formatNumber(event.voters_count) }} codes used</span>
                </div>
                <div class="bar mb-2"><span :style="{ width: percent(event.voted_count ?? 0, event.voters_count ?? 0) + '%' }"></span></div>
            </div>

            <div class="card-b pad">
                <h3 class="h6 fw-800 mb-3">Event details</h3>
                <dl class="row mb-0 small gy-2">
                    <dt class="col-sm-4 text-muted fw-semibold">Voting window</dt>
                    <dd class="col-sm-8 mb-0 text-ink">{{ formatWindow(event.starts_at, event.ends_at) }}</dd>
                    <dt class="col-sm-4 text-muted fw-semibold">Voting style</dt>
                    <dd class="col-sm-8 mb-0 text-ink">{{ event.selection_mode === 'multiple' ? 'Multiple choice per category' : 'One candidate per category' }}</dd>
                    <dt class="col-sm-4 text-muted fw-semibold">Results</dt>
                    <dd class="col-sm-8 mb-0 text-ink">{{ visibilityLabel }}</dd>
                    <dt class="col-sm-4 text-muted fw-semibold">Ballot</dt>
                    <dd class="col-sm-8 mb-0 text-ink">
                        <span v-if="event.ballot_locked"><i class="bi bi-lock-fill text-muted"></i> Locked (voting has started)</span>
                        <span v-else><i class="bi bi-unlock text-muted"></i> Editable until the first vote</span>
                    </dd>
                    <dt class="col-sm-4 text-muted fw-semibold">Created</dt>
                    <dd class="col-sm-8 mb-0 text-ink">{{ formatDate(event.created_at, true) }}</dd>
                    <template v-if="event.description">
                        <dt class="col-sm-4 text-muted fw-semibold">Description</dt>
                        <dd class="col-sm-8 mb-0 text-ink" style="white-space: pre-line">{{ event.description }}</dd>
                    </template>
                </dl>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card-b pad">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h3 class="h6 fw-800 mb-0">Setup checklist</h3>
                    <span class="small fw-bold text-brand">{{ doneCount }}/{{ checklist.length }}</span>
                </div>
                <div class="bar slim mb-3"><span :style="{ width: (doneCount / checklist.length) * 100 + '%' }"></span></div>
                <ul class="list-unstyled d-grid gap-1 mb-0">
                    <li v-for="item in checklist" :key="item.label">
                        <button type="button" class="check-item" @click="emit('go', item.tab)">
                            <i class="bi" :class="item.done ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted'"></i>
                            <span class="flex-grow-1 text-start" :class="item.done ? 'text-muted text-decoration-line-through' : 'text-ink fw-semibold'">{{ item.label }}</span>
                            <i class="bi bi-chevron-right small text-muted"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { BallotEvent } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ go: [tab: string] }>();

const checklist = computed(() => {
    const cats = props.event.categories ?? [];
    const cands = props.event.candidates ?? [];
    const everyCatFilled = cats.length > 0 && cats.every(c => cands.some(x => x.category_id === c.id));
    return [
        { label: 'Add at least one category', done: cats.length > 0, tab: 'ballot' },
        { label: 'Give every category candidates', done: everyCatFilled, tab: 'ballot' },
        { label: 'Create voter codes', done: (props.event.voters_count ?? 0) > 0, tab: 'voters' },
        { label: 'Set the voting window', done: !!props.event.ends_at, tab: 'settings' },
        { label: 'Share the voting link', done: (props.event.voted_count ?? 0) > 0, tab: 'share' },
    ];
});
const visibilityLabel = computed(() => ({
    live: 'Public, live as votes arrive',
    after_close: 'Public after voting closes',
    private: 'Only visible to you',
}[props.event.results_visibility] ?? 'Public after voting closes'));

const doneCount = computed(() => checklist.value.filter(i => i.done).length);
</script>

<style scoped>
.check-item { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .6rem .5rem; border: 0; background: none; border-radius: 10px; }
.check-item:hover { background: var(--surface-2); }
</style>
