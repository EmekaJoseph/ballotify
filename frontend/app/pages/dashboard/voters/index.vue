<template>
    <div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-2">
                    Voters Directory
                </h3>
                <p class="text-muted small mb-0 fw-medium">Global list of all voters across your events</p>
            </div>
        </div>

        <div class="glass-card p-4 border-0 shadow-sm min-vh-50">
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            
            <div v-else-if="voters.length === 0" class="text-center py-5">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-people fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">No Voters Found</h5>
                <p class="text-muted small mb-0">Voters will appear here once you add them to an event.</p>
            </div>
            
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0 border-transparent">
                    <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="border-0 rounded-start-3 text-muted small tracking-widest uppercase py-3 ps-4">Voter Name</th>
                            <th class="border-0 text-muted small tracking-widest uppercase py-3">Access Code</th>
                            <th class="border-0 text-muted small tracking-widest uppercase py-3">Event</th>
                            <th class="border-0 text-muted small tracking-widest uppercase py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="voter in voters" :key="voter.id">
                            <td class="border-bottom border-dark border-opacity-10 py-3 ps-4">
                                <div class="fw-bold text-dark">{{ voter.name || 'Anonymous Voter' }}</div>
                            </td>
                            <td class="border-bottom border-dark border-opacity-10 py-3">
                                <code class="bg-light px-2 py-1 rounded text-primary fw-bold">{{ voter.code }}</code>
                            </td>
                            <td class="border-bottom border-dark border-opacity-10 py-3 text-muted small">
                                {{ voter.event?.title || 'Unknown Event' }}
                            </td>
                            <td class="border-bottom border-dark border-opacity-10 py-3">
                                <span class="badge rounded-pill fw-bold" :class="voter.voted_at ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning'">
                                    {{ voter.voted_at ? 'Voted' : 'Not Voted' }}
                                </span>
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
const voters = ref<any[]>([]);

onMounted(async () => {
    try {
        // The backend doesn't have a global voters endpoint, so we aggregate from events
        const eventsResponse = await adminApi.getEvents();
        const events = eventsResponse.data || [];
        
        const allVoters = [];
        for (const event of events) {
            const votersRes = await adminApi.getVoters(event.id);
            const eventVoters = votersRes.data.map((v: any) => ({ ...v, event }));
            allVoters.push(...eventVoters);
        }
        voters.value = allVoters;
    } catch (error) {
        console.error('Failed to fetch voters', error);
    } finally {
        loading.value = false;
    }
});
</script>
