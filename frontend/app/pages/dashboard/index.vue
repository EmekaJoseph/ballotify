<template>
    <div>
        <PageHeader :title="greeting" subtitle="Here's how your events are doing.">
            <NuxtLink to="/dashboard/events/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New event</NuxtLink>
        </PageHeader>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3"><StatCard label="Events" :value="loading ? '–' : events.length" icon="bi-calendar2-check" /></div>
            <div class="col-6 col-xl-3"><StatCard label="Live now" :value="loading ? '–' : live.length" icon="bi-broadcast" tone="green" /></div>
            <div class="col-6 col-xl-3"><StatCard label="Voters" :value="loading ? '–' : formatNumber(totals.voters)" icon="bi-people" tone="violet" /></div>
            <div class="col-6 col-xl-3"><StatCard label="Ballots cast" :value="loading ? '–' : formatNumber(totals.voted)" icon="bi-check2-square" tone="amber"
                :hint="totals.voters ? `${percent(totals.voted, totals.voters)}% turnout` : ''" /></div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 fw-800 mb-0">Recent events</h2>
            <NuxtLink v-if="events.length" to="/dashboard/events" class="small fw-bold">View all <i class="bi bi-arrow-right"></i></NuxtLink>
        </div>

        <div v-if="loading" class="grid-auto">
            <div v-for="i in 3" :key="i" class="card-b pad"><div class="skeleton mb-3" style="width: 40%"></div><div class="skeleton mb-2" style="height: 22px"></div><div class="skeleton" style="width: 70%"></div></div>
        </div>
        <div v-else-if="loadError" class="card-b">
            <EmptyState icon="bi-wifi-off" title="Couldn't load your events" :text="loadError">
                <button class="btn btn-soft" @click="load">Try again</button>
            </EmptyState>
        </div>
        <div v-else-if="!events.length" class="card-b">
            <EmptyState icon="bi-calendar-plus" title="Create your first event"
                text="Set up a ballot, add candidates and share voter codes. It takes a few minutes.">
                <NuxtLink to="/dashboard/events/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New event</NuxtLink>
            </EmptyState>
        </div>
        <div v-else class="grid-auto">
            <EventCard v-for="ev in events.slice(0, 6)" :key="ev.id" :event="ev" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';
import type { BallotEvent } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });
useHead({ title: 'Dashboard · Ballotify' });

const auth = useAuthStore();
const loading = ref(true);
const loadError = ref('');
const events = ref<BallotEvent[]>([]);

const greeting = computed(() => {
    const h = new Date().getHours();
    const part = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
    return auth.firstName ? `${part}, ${auth.firstName}` : part;
});
const live = computed(() => events.value.filter(e => e.status === 'active'));
const totals = computed(() => events.value.reduce(
    (acc, e) => ({ voters: acc.voters + (e.voters_count ?? 0), voted: acc.voted + (e.voted_count ?? 0) }),
    { voters: 0, voted: 0 },
));

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
