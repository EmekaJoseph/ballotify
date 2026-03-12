<template>
    <div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-2">
                    Events Manager
                </h3>
                <p class="text-muted small mb-0 fw-medium">View, edit, and organize your elections</p>
            </div>
            <NuxtLink to="/dashboard/events/create" class="btn btn-primary fw-bold rounded-pill shadow-sm transition-hover px-4">
                <i class="bi bi-plus-lg me-1"></i> New Event
            </NuxtLink>
        </div>

        <div class="glass-card p-4 border-0 shadow-sm min-vh-50">
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            
            <div v-else-if="events.length === 0" class="text-center py-5">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-calendar-x fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">No Events Found</h5>
                <p class="text-muted small mb-4">You don't have any events yet.</p>
                <NuxtLink to="/dashboard/events/create" class="btn btn-primary fw-bold rounded-pill shadow-sm transition-hover px-4">
                    Create Event
                </NuxtLink>
            </div>
            
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0 border-transparent">
                    <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="border-0 rounded-start-3 text-muted small tracking-widest uppercase py-3 ps-4">Event Name</th>
                            <th class="border-0 text-muted small tracking-widest uppercase py-3">Status</th>
                            <th class="border-0 text-muted small tracking-widest uppercase py-3">Date</th>
                            <th class="border-0 rounded-end-3 text-end py-3 pe-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="event in events" :key="event.id" class="transition-hover">
                            <td class="border-bottom border-dark border-opacity-10 py-3 ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 text-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 250px;" :title="event.title">{{ event.title }}</h6>
                                        <p class="text-muted small mb-0">{{ event.token }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="border-bottom border-dark border-opacity-10 py-3">
                                <span class="badge rounded-pill fw-bold" :class="{
                                    'bg-success bg-opacity-10 text-success': event.status === 'active',
                                    'bg-warning bg-opacity-10 text-warning': event.status === 'draft',
                                    'bg-secondary bg-opacity-10 text-secondary': event.status === 'completed'
                                }">
                                    {{ event.status.charAt(0).toUpperCase() + event.status.slice(1) }}
                                </span>
                            </td>
                            <td class="border-bottom border-dark border-opacity-10 py-3 text-muted small fw-medium">
                                {{ new Date(event.start_date).toLocaleDateString() }}
                            </td>
                            <td class="border-bottom border-dark border-opacity-10 py-3 text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <NuxtLink :to="`/dashboard/events/${event.id}`" class="btn btn-sm btn-light rounded-pill fw-bold text-primary hover-bg-primary hover-text-white transition-hover px-3">
                                        Manage
                                    </NuxtLink>
                                    <button @click="confirmDelete(event.id)" class="btn btn-sm btn-light rounded-circle text-danger hover-bg-danger hover-text-white transition-hover" style="width: 32px; height: 32px; padding: 0;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { adminApi } from '../../../api/endpoints/admin';

definePageMeta({
    layout: 'dashboard',
    middleware: ['auth']
});

const loading = ref(true);
const events = ref<any[]>([]);

const fetchEvents = async () => {
    loading.value = true;
    try {
        const response = await adminApi.getEvents();
        events.value = response.data || [];
    } catch (error) {
        console.error('Failed to fetch events', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchEvents();
});

const confirmDelete = async (id: number) => {
    if (confirm('Are you sure you want to delete this event?')) {
        try {
            await adminApi.deleteEvent(id);
            events.value = events.value.filter(e => e.id !== id);
        } catch (error) {
            console.error('Failed to delete event', error);
            alert('Could not delete event. Please try again.');
        }
    }
};
</script>

<style scoped>
.hover-bg-primary:hover {
    background-color: var(--bs-primary) !important;
}
.hover-text-white:hover {
    color: white !important;
}
.hover-bg-danger:hover {
    background-color: var(--bs-danger) !important;
}
.border-transparent {
    border-color: transparent !important;
}
</style>
