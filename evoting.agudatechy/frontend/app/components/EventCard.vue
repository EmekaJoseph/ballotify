<template>
    <NuxtLink :to="`/dashboard/events/${event.id}`" class="card-b hoverable pad d-flex flex-column h-100 text-reset event-card">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <StatusBadge :status="event.status" :draft="isDraft(event)" />
            <span class="d-flex flex-wrap gap-1 justify-content-end">
            <span v-if="event.voting_mode === 'paid'" class="pill pill-amber"><i class="bi bi-cash-coin"></i> Paid voting</span>
            <span v-else class="pill pill-brand"><i class="bi bi-shield-lock"></i> Secret ballot</span>
            <span v-if="event.my_role && event.my_role !== 'owner'" class="pill pill-completed" :title="`Shared with you as ${roleLabel(event.my_role).toLowerCase()}`"><i class="bi bi-people"></i> Shared</span>
            </span>
        </div>
        <h3 class="h5 fw-800 mb-1 text-break clamp-2">{{ event.name }}</h3>
        <p class="small text-muted mb-3 clamp-2">{{ event.description || formatWindow(event.starts_at, event.ends_at) }}</p>

        <div class="mt-auto">
            <template v-if="event">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">Turnout</span>
                    <span class="fw-bold text-ink">{{ event.voted_count ?? 0 }} / {{ event.voters_count ?? 0 }}</span>
                </div>
                <div class="bar slim mb-3"><span :style="{ width: percent(event.voted_count ?? 0, event.voters_count ?? 0) + '%' }"></span></div>
            </template>
            <div class="d-flex gap-3 small text-muted">
                <span><i class="bi bi-grid"></i> {{ event.categories_count ?? 0 }}</span>
                <span><i class="bi bi-person-badge"></i> {{ event.candidates_count ?? 0 }}</span>
                <span class="ms-auto"><i class="bi bi-calendar3"></i> {{ formatDate(event.starts_at || event.created_at) }}</span>
            </div>
        </div>
    </NuxtLink>
</template>

<script setup lang="ts">
import type { BallotEvent } from '~/types';

defineProps<{ event: BallotEvent }>();
</script>
