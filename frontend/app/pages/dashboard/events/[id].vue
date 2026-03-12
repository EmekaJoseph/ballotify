<template>
    <div>
        <div class="d-flex align-items-center gap-3 mb-4">
            <NuxtLink to="/dashboard/events" class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-arrow-left fs-5"></i>
            </NuxtLink>
            <div>
                <h3 v-if="event" class="fw-black text-dark mb-1">{{ event.title }}</h3>
                <h3 v-else class="fw-black text-dark mb-1">Loading Event...</h3>
                <p class="text-muted small mb-0 fw-medium">Manage categories, candidates, and voters</p>
            </div>
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div v-else-if="event">
            <!-- Tabs -->
            <div class="glass-card p-2 rounded-pill d-inline-flex gap-2 mb-4 bg-white bg-opacity-50">
                <button v-for="tab in ['Overview', 'Categories', 'Candidates', 'Voters']" :key="tab" 
                    @click="activeTab = tab" 
                    class="btn rounded-pill px-4 py-2 fw-bold transition-base border-0" 
                    :class="activeTab === tab ? 'bg-primary text-white shadow-sm' : 'text-muted hover-bg-light'">
                    {{ tab }}
                </button>
            </div>

            <!-- Tab Content -->
            <div class="tab-content transition-fade">
                <!-- Overview Tab -->
                <div v-if="activeTab === 'Overview'" class="animate-fade-in">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="glass-card p-4 border-0 shadow-sm mb-4">
                                <h5 class="fw-bold text-dark mb-4">Event Details</h5>
                                <div class="row g-4">
                                    <div class="col-sm-6">
                                        <label class="text-muted small fw-bold tracking-widest uppercase d-block mb-1">Public Token</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <code class="bg-light px-3 py-2 rounded flex-grow-1 border">{{ event.token }}</code>
                                            <button class="btn btn-sm btn-outline-primary" @click="copyToken"><i class="bi bi-clipboard"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="text-muted small fw-bold tracking-widest uppercase d-block mb-1">Status</label>
                                        <span class="badge rounded-pill fw-bold bg-success bg-opacity-10 text-success p-2 px-3 fs-6">
                                            {{ event.status.toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="text-muted small fw-bold tracking-widest uppercase d-block mb-1">Description</label>
                                        <p class="text-dark bg-light p-3 rounded border">{{ event.description || 'No description provided.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="glass-card p-4 border-0 shadow-sm">
                                <h5 class="fw-bold text-dark mb-4">Quick Stats</h5>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total Categories</span>
                                        <span class="fw-bold">{{ event.categories_count || 0 }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total Candidates</span>
                                        <span class="fw-bold">{{ event.candidates_count || 0 }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total Voters</span>
                                        <span class="fw-bold">{{ event.voters_count || 0 }}</span>
                                    </div>
                                </div>
                                <NuxtLink :to="`/events/${event.token}`" target="_blank" class="btn btn-primary w-100 mt-4 fw-bold rounded-pill">
                                    <i class="bi bi-box-arrow-up-right me-2"></i> View Public Page
                                </NuxtLink>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories Tab -->
                <div v-if="activeTab === 'Categories'" class="animate-fade-in">
                    <div class="glass-card p-4 border-0 shadow-sm mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold text-dark mb-0">Event Categories</h5>
                            <button @click="showAddCategory = !showAddCategory" class="btn btn-sm btn-primary rounded-pill px-3">
                                <i class="bi" :class="showAddCategory ? 'bi-dash-lg' : 'bi-plus-lg'"></i> {{ showAddCategory ? 'Cancel' : 'Add Category' }}
                            </button>
                        </div>

                        <!-- Add Category Form -->
                        <div v-if="showAddCategory" class="bg-light p-3 rounded-4 mb-4 border animate-slide-up">
                            <form @submit.prevent="handleAddCategory" class="d-flex gap-2">
                                <input type="text" v-model="newCategoryName" class="form-control border-white focus-ring-primary" placeholder="Enter category name (e.g. Best Actor)" required />
                                <button type="submit" class="btn btn-primary px-4 fw-bold rounded-pill" :disabled="submitting">
                                    {{ submitting ? 'Saving...' : 'Save' }}
                                </button>
                            </form>
                        </div>

                        <div v-if="categories.length > 0" class="row g-3">
                            <div v-for="cat in categories" :key="cat.id" class="col-md-6">
                                <div class="bg-white bg-opacity-50 p-3 rounded-3 border d-flex justify-content-between align-items-center transition-hover">
                                    <span class="fw-bold text-dark">{{ cat.name }}</span>
                                    <div class="d-flex gap-2">
                                        <button @click="handleDeleteCategory(cat.id)" class="btn btn-sm text-danger p-0"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="!loading" class="text-center py-4 text-muted small">
                            No categories created yet.
                        </div>
                    </div>
                </div>

                <!-- Candidates Tab -->
                <div v-if="activeTab === 'Candidates'" class="animate-fade-in">
                    <div class="glass-card p-4 border-0 shadow-sm mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold text-dark mb-0">Event Candidates</h5>
                        </div>
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info rounded-4 small">
                            <i class="bi bi-info-circle-fill me-2"></i> Candidates are managed within their respective categories. Implementation of complex candidate management is in progress.
                        </div>
                        <!-- Placeholder for candidates list -->
                    </div>
                </div>

                <!-- Voters Tab -->
                <div v-if="activeTab === 'Voters'" class="animate-fade-in">
                    <div class="glass-card p-4 border-0 shadow-sm mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold text-dark mb-0">Event Voters</h5>
                            <NuxtLink :to="`/dashboard/voters`" class="btn btn-sm btn-outline-primary rounded-pill px-3">View Global Directory</NuxtLink>
                        </div>
                        <!-- Placeholder for Voters list -->
                        <p class="text-muted small">This tab will list voters specifically for this event ID: {{ eventId }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { adminApi } from '../../../api/endpoints/admin';

definePageMeta({
    layout: 'dashboard',
    middleware: ['auth']
});

const route = useRoute();
const eventId = route.params.id as string;
const loading = ref(true);
const submitting = ref(false);
const event = ref<any>(null);
const categories = ref<any[]>([]);
const activeTab = ref('Overview');

// Selection/Form state
const showAddCategory = ref(false);
const newCategoryName = ref('');

const fetchEventDetails = async () => {
    try {
        const res = await adminApi.getEventDetails(eventId);
        event.value = res.data;
        
        // Fetch categories for this event
        const catRes = await adminApi.getCategories(eventId);
        categories.value = catRes.data || [];
    } catch (error) {
        console.error('Failed to fetch event details', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchEventDetails();
});

const handleAddCategory = async () => {
    submitting.value = true;
    try {
        await adminApi.createCategory(eventId, { name: newCategoryName.value });
        newCategoryName.value = '';
        showAddCategory.value = false;
        fetchEventDetails(); // Refresh
    } catch (error) {
        console.error('Failed to add category', error);
        alert('Failed to add category.');
    } finally {
        submitting.value = false;
    }
};

const handleDeleteCategory = async (catId: number) => {
    if (confirm('Delete this category?')) {
        try {
            await adminApi.deleteCategory(eventId, catId);
            fetchEventDetails();
        } catch (error) {
            console.error('Failed to delete category', error);
        }
    }
};

const copyToken = () => {
    navigator.clipboard.writeText(event.value.token);
    alert('Token copied to clipboard!');
};
</script>

<style scoped>
.transition-fade {
    transition: all 0.3s ease;
}
.hover-bg-light:hover {
    background-color: var(--bs-light);
}
</style>
