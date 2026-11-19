<template>
    <div class="row g-4">
        <div class="col-xl-8 d-grid gap-4 align-content-start">
            <div class="row g-3">
                <div class="col-6 col-md-3"><StatCard label="Categories" :value="event.categories?.length ?? 0" icon="bi-grid" /></div>
                <div class="col-6 col-md-3"><StatCard label="Candidates" :value="event.candidates?.length ?? 0" icon="bi-person-badge" tone="violet" /></div>
                <template v-if="paid">
                    <div class="col-6 col-md-3"><StatCard label="Votes sold" :value="formatNumber(event.votes_sold ?? 0)" icon="bi-heart" tone="amber" /></div>
                    <div class="col-6 col-md-3"><StatCard label="Raised" :value="formatNaira((event.votes_sold ?? 0) * (event.vote_price_naira ?? 0))" icon="bi-cash-coin" tone="green"
                        :hint="`₦${formatNumber(event.vote_price_naira ?? 0)} per vote`" /></div>
                </template>
                <template v-else>
                    <div class="col-6 col-md-3"><StatCard label="Voters" :value="formatNumber(event.voters_count)" icon="bi-people" tone="amber" /></div>
                    <div class="col-6 col-md-3"><StatCard label="Voted" :value="formatNumber(event.voted_count)" icon="bi-check2-square" tone="green"
                        :hint="event.voters_count ? `${percent(event.voted_count ?? 0, event.voters_count)}% turnout` : ''" /></div>
                </template>
            </div>

            <div class="card-b pad">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-3">
                    <h3 class="h6 fw-800 mb-0">{{ paid ? 'Votes over time' : 'Turnout over time' }}</h3>
                    <span v-if="!paid" class="small text-muted">{{ formatNumber(event.voted_count) }} of {{ formatNumber(event.voters_count) }} codes used</span>
                </div>
                <div v-if="!paid && event.voters_count" class="bar mb-3"><span :style="{ width: percent(event.voted_count ?? 0, event.voters_count ?? 0) + '%' }"></span></div>
                <TurnoutChart :timeline="timeline" :loading="timelineLoading" :unit="paid ? 'votes' : 'ballots'" />
            </div>

            <div class="card-b pad">
                <h3 class="h6 fw-800 mb-3">Event details</h3>
                <dl class="row mb-0 small gy-2">
                    <dt class="col-sm-4 text-muted fw-semibold">Voting window</dt>
                    <dd class="col-sm-8 mb-0 text-ink">{{ formatWindow(event.starts_at, event.ends_at) }}</dd>
                    <dt class="col-sm-4 text-muted fw-semibold">How people vote</dt>
                    <dd class="col-sm-8 mb-0 text-ink">{{ paid ? `Paid public voting · ₦${formatNumber(event.vote_price_naira ?? 0)} per vote` : 'Secret ballot with one-time codes' }}</dd>
                    <dt class="col-sm-4 text-muted fw-semibold">Results</dt>
                    <dd class="col-sm-8 mb-0 text-ink">{{ visibilityLabel }}</dd>
                    <dt class="col-sm-4 text-muted fw-semibold">Ballot</dt>
                    <dd class="col-sm-8 mb-0 text-ink">
                        <span v-if="event.ballot_locked"><i class="bi bi-lock-fill text-muted"></i> Locked (voting has started)</span>
                        <span v-else><i class="bi bi-unlock text-muted"></i> Editable until the first vote</span>
                    </dd>
                    <template v-if="event.my_role && event.my_role !== 'owner'">
                        <dt class="col-sm-4 text-muted fw-semibold">Your role</dt>
                        <dd class="col-sm-8 mb-0 text-ink">{{ roleLabel(event.my_role) }}</dd>
                    </template>
                    <dt class="col-sm-4 text-muted fw-semibold">Created</dt>
                    <dd class="col-sm-8 mb-0 text-ink">{{ formatDate(event.created_at, true) }}</dd>
                    <template v-if="event.description">
                        <dt class="col-sm-4 text-muted fw-semibold">Description</dt>
                        <dd class="col-sm-8 mb-0 text-ink" style="white-space: pre-line">{{ event.description }}</dd>
                    </template>
                </dl>
            </div>
        </div>

        <div class="col-xl-4 d-grid gap-4 align-content-start">
            <EventUpgradeCard v-if="canManage && !paid" :event="event" />
            <div class="card-b pad" data-tour="checklist">
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
import { adminApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';
import type { BallotEvent, EventResults } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ go: [tab: string] }>();

const auth = useAuthStore();
const paid = computed(() => props.event.voting_mode === 'paid');
const canManage = computed(() => props.event.abilities?.includes('manage') ?? true);

// Timeline for the chart comes with the organiser's results.
const timeline = ref<EventResults['timeline'] | null>(null);
const timelineLoading = ref(true);
async function loadTimeline() {
    try { timeline.value = (await adminApi.getResults(props.event.id)).data.timeline ?? null; } catch { /* chart stays empty */ }
    finally { timelineLoading.value = false; }
}
onMounted(loadTimeline);
watch(() => [props.event.voted_count, props.event.votes_sold], loadTimeline);

const checklist = computed(() => {
    const cats = props.event.categories ?? [];
    const cands = props.event.candidates ?? [];
    const everyCatFilled = cats.length > 0 && cats.every(c => cands.some(x => x.category_id === c.id));
    const sharedOrVoted = paid.value ? (props.event.votes_sold ?? 0) > 0 : (props.event.voted_count ?? 0) > 0;
    return [
        { label: 'Add at least one category', done: cats.length > 0, tab: 'ballot' },
        { label: paid.value ? 'Add nominees to every category' : 'Give every category candidates', done: everyCatFilled, tab: 'ballot' },
        paid.value
            ? { label: 'Set your payout bank account', done: !!auth.user?.payout_account_number, tab: 'earnings' }
            : { label: 'Create voter codes', done: (props.event.voters_count ?? 0) > 0, tab: 'voters' },
        { label: 'Set the voting window', done: !!props.event.ends_at, tab: 'settings' },
        { label: 'Share the voting link', done: sharedOrVoted, tab: 'share' },
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
