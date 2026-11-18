<template>
    <div class="card-b overflow-hidden">
        <div class="card-header-b">
            <div>
                <h3 class="h6 fw-800 mb-0">Activity log</h3>
                <div class="small text-muted">Every change to this event, newest first. Entries can’t be edited or deleted.</div>
            </div>
            <span v-if="duringVoting" class="pill pill-upcoming" title="Changes made after the first vote are counted on the public results page">
                {{ duringVoting }} change{{ duringVoting === 1 ? '' : 's' }} during voting
            </span>
            <span v-else-if="entries.length" class="pill pill-active"><i class="bi bi-shield-check"></i> No changes during voting</span>
        </div>

        <div v-if="loading" class="p-4 d-grid gap-2"><div v-for="i in 5" :key="i" class="skeleton" style="height: 40px"></div></div>
        <EmptyState v-else-if="!entries.length" icon="bi-clock-history" title="No activity yet" />
        <ol v-else class="list-unstyled mb-0 timeline">
            <li v-for="e in entries" :key="e.id" class="entry">
                <span class="dot" :class="{ voting: e.during_voting }"><i class="bi" :class="iconFor(e.action)"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-ink text-break">{{ e.description }}</div>
                    <div class="small text-muted">
                        {{ formatDate(e.created_at, true) }}<template v-if="e.user"> · {{ e.user.name }}</template>
                        <span v-if="e.during_voting" class="ms-1 voting-tag">during voting</span>
                    </div>
                </div>
            </li>
        </ol>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { AuditEntry } from '~/types';

const props = defineProps<{ eventId: number }>();
const entries = ref<AuditEntry[]>([]);
const loading = ref(true);
const duringVoting = computed(() => entries.value.filter(e => e.during_voting).length);

const ICONS: Record<string, string> = {
    event: 'bi-calendar-event', category: 'bi-folder', candidate: 'bi-person-badge', voters: 'bi-people',
};
const iconFor = (action: string) => {
    if (action === 'event.closed') return 'bi-stop-circle';
    if (action === 'voters.invited') return 'bi-send';
    if (action === 'voters.contacts_erased') return 'bi-eraser';
    if (action.endsWith('.deleted')) return 'bi-trash';
    return ICONS[action.split('.')[0] ?? ''] ?? 'bi-dot';
};

onMounted(async () => {
    try {
        entries.value = (await adminApi.getActivity(props.eventId)).data;
    } catch (e) {
        useToast().error(apiErrorMessage(e, 'Could not load the activity log.'));
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.entry { display: flex; gap: .9rem; padding: .85rem 1.25rem; border-bottom: 1px solid var(--line); }
.entry:last-child { border-bottom: 0; }
.dot {
    width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
    background: var(--surface-2); color: var(--muted); border: 1px solid var(--line);
}
.dot.voting { background: var(--warning-50); color: var(--warning-text); border-color: transparent; }
.voting-tag { color: var(--warning-text); font-weight: 600; }
</style>
