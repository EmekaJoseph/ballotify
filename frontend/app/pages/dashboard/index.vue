<template>
    <div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-2">
                    Overview
                </h3>
                <p class="text-muted small mb-0 fw-medium">Welcome to your Ballotify dashboard</p>
            </div>
            <NuxtLink to="/dashboard/events/create" class="btn btn-primary fw-bold rounded-pill shadow-sm transition-hover px-4">
                <i class="bi bi-plus-lg me-1"></i> New Event
            </NuxtLink>
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div v-else>
            <!-- Stats -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="glass-card p-4 transition-hover border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <div class="position-relative z-1">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-calendar-event fs-4"></i>
                                </div>
                            </div>
                            <h3 class="fw-black text-dark mb-1">{{ stats.totalEvents }}</h3>
                            <p class="text-muted small fw-bold tracking-widest uppercase mb-0">Total Events</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 transition-hover border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <div class="position-relative z-1">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-check-circle fs-4"></i>
                                </div>
                            </div>
                            <h3 class="fw-black text-dark mb-1">{{ stats.activeEvents }}</h3>
                            <p class="text-muted small fw-bold tracking-widest uppercase mb-0">Active Events</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 transition-hover border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <div class="position-relative z-1">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-people fs-4"></i>
                                </div>
                            </div>
                            <h3 class="fw-black text-dark mb-1">{{ stats.totalVoters }}</h3>
                            <p class="text-muted small fw-bold tracking-widest uppercase mb-0">Total Voters</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Events Grid -->
            <h5 class="fw-bold text-dark mb-3">Recent Events</h5>
            <div v-if="events.length > 0" class="row g-4">
                <div v-for="event in events.slice(0, 3)" :key="event.id" class="col-md-6 col-lg-4">
                    <div class="glass-card p-4 h-100 transition-hover border-0 shadow-sm d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="badge rounded-pill fw-bold" :class="{
                                'bg-success bg-opacity-10 text-success': event.status === 'active',
                                'bg-warning bg-opacity-10 text-warning': event.status === 'draft',
                                'bg-secondary bg-opacity-10 text-secondary': event.status === 'completed'
                            }">
                                {{ event.status.charAt(0).toUpperCase() + event.status.slice(1) }}
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 glass-card">
                                    <li><NuxtLink :to="`/dashboard/events/${event.id}`" class="dropdown-item small"><i class="bi bi-eye me-2"></i>View Details</NuxtLink></li>
                                    <li><NuxtLink :to="`/events/${event.token}`" target="_blank" class="dropdown-item small"><i class="bi bi-box-arrow-up-right me-2"></i>Public Link</NuxtLink></li>
                                </ul>
                            </div>
                        </div>
                        
                        <h5 class="fw-bold text-dark mb-2 text-truncate" :title="event.title">{{ event.title }}</h5>
                        <p class="text-muted small mb-4 flex-grow-1 text-truncate-2 lines-clamp" :title="event.description">{{ event.description || 'No description provided.' }}</p>
                        
                        <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top border-dark border-opacity-10">
                            <div class="d-flex align-items-center gap-2 text-muted small fw-medium">
                                <i class="bi bi-calendar3"></i>
                                {{ new Date(event.start_date).toLocaleDateString() }}
                            </div>
                            <NuxtLink :to="`/dashboard/events/${event.id}`" class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-3">
                                Manage
                            </NuxtLink>
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-else class="text-center py-5 glass-card border-0 shadow-sm rounded-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-calendar-x fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">No Events Yet</h5>
                <p class="text-muted small mb-4">You haven't created any events. Start your first election now!</p>
                <NuxtLink to="/dashboard/events/create" class="btn btn-primary fw-bold rounded-pill shadow-sm transition-hover px-4">
                    Create Event
                </NuxtLink>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { adminApi } from '../../api/endpoints/admin';

definePageMeta({
    layout: 'dashboard',
    middleware: ['auth']
});

const loading = ref(true);
const events = ref<any[]>([]);
const stats = ref({
    totalEvents: 0,
    activeEvents: 0,
    totalVoters: 0
});

onMounted(async () => {
    try {
        const response = await adminApi.getEvents();
        events.value = response.data || [];
        
        // Calculate basic stats
        stats.value.totalEvents = events.value.length;
        stats.value.activeEvents = events.value.filter(e => e.status === 'active').length;
        
        // Sum voters (assuming an event returns voter_count or you could mock it if not available)
        stats.value.totalVoters = events.value.reduce((acc, curr) => acc + (curr.voters_count || 0), 0);
    } catch (error) {
        console.error('Failed to fetch dashboard data', error);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.lines-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
</style>
