<template>
    <div>
        <PageHeader title="Events" subtitle="All your elections, awards and polls.">
            <NuxtLink to="/dashboard/events/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New event</NuxtLink>
        </PageHeader>

        <div class="d-flex flex-column flex-md-row gap-2 mb-4">
            <div class="input-icon flex-grow-1" style="max-width: 420px">
                <i class="bi bi-search"></i>
                <input v-model="query" type="search" class="form-control" placeholder="Search events" aria-label="Search events" />
            </div>
            <div class="segmented" role="tablist">
                <button v-for="f in filters" :key="f.value" type="button" :class="{ active: filter === f.value }"
                    @click="filter = f.value">
                    {{ f.label }} <span class="text-muted small">{{ countFor(f.value) }}</span>
                </button>
            </div>
        </div>

        <div v-if="loading" class="grid-auto">
            <div v-for="i in 6" :key="i" class="card-b pad"><div class="skeleton mb-3" style="width: 40%"></div><div class="skeleton mb-2" style="height: 22px"></div><div class="skeleton" style="width: 70%"></div></div>
        </div>
        <div v-else-if="loadError" class="card-b">
            <EmptyState icon="bi-wifi-off" title="Couldn't load your events" :text="loadError">
                <button class="btn btn-soft" @click="load">Try again</button>
            </EmptyState>
        </div>
        <div v-else-if="!events.length" class="card-b">
            <EmptyState icon="bi-calendar-plus" title="No events yet" text="Create an event to start building your ballot.">
                <NuxtLink to="/dashboard/events/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New event</NuxtLink>
            </EmptyState>
        </div>
        <div v-else-if="!visible.length" class="card-b">
            <EmptyState icon="bi-search" title="No matching events" text="Try a different search or filter." />
        </div>
        <div v-else class="grid-auto">
            <EventCard v-for="ev in visible" :key="ev.id" :event="ev" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, EventStatus } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });
useHead({ title: 'Events · Ballotify' });

type Filter = 'all' | EventStatus;
const filters: { value: Filter; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Live' },
    { value: 'upcoming', label: 'Scheduled' },
    { value: 'completed', label: 'Closed' },
];

const loading = ref(true);
const loadError = ref('');
const events = ref<BallotEvent[]>([]);
const query = ref('');
const filter = ref<Filter>('all');

const countFor = (f: Filter) => (f === 'all' ? events.value.length : events.value.filter(e => e.status === f).length);
const visible = computed(() => {
    const q = query.value.trim().toLowerCase();
    return events.value.filter(e =>
        (filter.value === 'all' || e.status === filter.value)
        && (!q || e.name.toLowerCase().includes(q) || (e.description ?? '').toLowerCase().includes(q)));
});

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
