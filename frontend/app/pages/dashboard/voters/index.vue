<template>
    <div>
        <PageHeader title="Voters" subtitle="Turnout across all your events. Open an event to manage its codes." />

        <div class="row g-3 mb-4">
            <div class="col-4"><StatCard label="Codes issued" :value="loading ? '–' : formatNumber(totals.voters)" icon="bi-key" /></div>
            <div class="col-4"><StatCard label="Voted" :value="loading ? '–' : formatNumber(totals.voted)" icon="bi-check2-square" tone="green" /></div>
            <div class="col-4"><StatCard label="Turnout" :value="loading ? '–' : (totals.voters ? percent(totals.voted, totals.voters) + '%' : '—')" icon="bi-graph-up" tone="violet" /></div>
        </div>

        <div class="card-b overflow-hidden">
            <div v-if="loading" class="p-4 d-grid gap-2"><div v-for="i in 4" :key="i" class="skeleton" style="height: 44px"></div></div>
            <EmptyState v-else-if="loadError" icon="bi-wifi-off" title="Couldn't load voters" :text="loadError">
                <button class="btn btn-soft" @click="load">Try again</button>
            </EmptyState>
            <EmptyState v-else-if="!events.length" icon="bi-people" title="No events yet" text="Create an event, then generate voter codes for it.">
                <NewEventButton />
            </EmptyState>
            <div v-else class="table-responsive">
                <table class="table-b">
                    <thead>
                        <tr><th>Event</th><th>Status</th><th>Codes</th><th style="min-width: 200px">Turnout</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="ev in events" :key="ev.id">
                            <td><NuxtLink :to="`/dashboard/events/${ev.id}?tab=voters`" class="fw-semibold text-ink">{{ ev.name }}</NuxtLink></td>
                            <td><StatusBadge :status="ev.status" :draft="isDraft(ev)" /></td>
                            <td class="text-nowrap">{{ formatNumber(ev.voted_count) }} / {{ formatNumber(ev.voters_count) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bar slim flex-grow-1"><span :style="{ width: percent(ev.voted_count ?? 0, ev.voters_count ?? 0) + '%' }"></span></div>
                                    <span class="small fw-bold text-nowrap">{{ percent(ev.voted_count ?? 0, ev.voters_count ?? 0) }}%</span>
                                </div>
                            </td>
                            <td class="text-end"><NuxtLink :to="`/dashboard/events/${ev.id}?tab=voters`" class="btn btn-ghost btn-sm">Manage</NuxtLink></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });
useHead({ title: 'Voters · Ballotify' });

const loading = ref(true);
const loadError = ref('');
const events = ref<BallotEvent[]>([]);
const totals = computed(() => events.value.reduce(
    (a, e) => ({ voters: a.voters + (e.voters_count ?? 0), voted: a.voted + (e.voted_count ?? 0) }), { voters: 0, voted: 0 }));

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        events.value = (await adminApi.getEvents()).data;
    } catch (e) {
        loadError.value = apiErrorMessage(e);
    } finally {
        loading.value = false;
    }
}
onMounted(load);
</script>
