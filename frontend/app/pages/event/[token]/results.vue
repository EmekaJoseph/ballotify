<template>
    <div class="results-page py-5 py-lg-5">
        <div class="container animate-fade-in">
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-grow text-primary mb-4" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="fw-bold text-dark">Gathering live results</h4>
                <p class="text-muted fw-medium">Connecting to secure election server...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="card border-0 modern-card rounded-4 overflow-hidden shadow-sm">
                <div class="card-body p-5 text-center">
                    <div
                        class="error-icon bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                    <h3 class="fw-bolder mb-3 text-dark">Dashboard Unavailable</h3>
                    <p class="text-muted mb-5 opacity-75 text-balance mx-auto" style="max-width: 400px;">We couldn't
                        fetch the results for this election. The link might be expired or the event doesn't exist.</p>
                    <NuxtLink to="/" class="btn btn-dark rounded-pill px-5 py-3 fw-bold transition-hover shadow-sm">
                        <i class="bi bi-house-door me-2"></i>Return Home
                    </NuxtLink>
                </div>
            </div>

            <!-- Content -->
            <div v-else-if="results">
                <!-- Dashboard Header -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3">
                    <div>
                        <h1 class="h2 fw-black text-dark mb-1">My Elections</h1>
                        <p class="text-muted fw-medium mb-0">{{ results.event.name }} Dashboard</p>
                    </div>
                    <div>
                        <button @click="fetchResults"
                            class="btn btn-primary rounded-pill px-4 py-2 fw-bold transition-hover shadow-sm d-flex align-items-center gap-2"
                            :disabled="loading">
                            <i class="bi bi-arrow-clockwise fs-5" :class="{ 'spin': loading }"></i>
                            <span>Refresh Data</span>
                        </button>
                    </div>
                </div>

                <!-- 4 Stat Cards Grid -->
                <div class="row g-4 mb-5">
                    <!-- Total Votes -->
                    <div class="col-sm-6 col-xl-3 animate-slide-up" style="animation-delay: 0.1s">
                        <div class="glass-card p-4 h-100 position-relative overflow-hidden transition-hover">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <div class="text-xs fw-bold text-muted uppercase tracking-widest mb-1">Total Votes
                                    </div>
                                    <h2 class="display-6 fw-black text-dark mb-0">{{
                                        results.stats.total_votes.toLocaleString() }}</h2>
                                    <div class="mt-2">
                                        <span class="text-success fw-bold small">+5%</span>
                                        <span class="text-muted text-xs ms-2">vs last hour</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-3 p-2 shadow-sm border border-light">
                                    <i class="bi bi-check2-all fs-4 text-dark"></i>
                                </div>
                            </div>
                            <div class="status-line bg-dark opacity-10"></div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="col-sm-6 col-xl-3 animate-slide-up" style="animation-delay: 0.2s">
                        <div class="glass-card p-4 h-100 position-relative overflow-hidden transition-hover">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <div class="text-xs fw-bold text-muted uppercase tracking-widest mb-1">Categories
                                    </div>
                                    <h2 class="display-6 fw-black text-dark mb-0">{{ results.categories.length }}</h2>
                                    <div class="mt-2">
                                        <span class="text-success fw-bold small">+12%</span>
                                        <span class="text-muted text-xs ms-2">participation</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-3 p-2 shadow-sm border border-light">
                                    <i class="bi bi-grid fs-4 text-dark"></i>
                                </div>
                            </div>
                            <div class="status-line bg-dark opacity-10"></div>
                        </div>
                    </div>

                    <!-- Candidates -->
                    <div class="col-sm-6 col-xl-3 animate-slide-up" style="animation-delay: 0.3s">
                        <div class="glass-card p-4 h-100 position-relative overflow-hidden transition-hover">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <div class="text-xs fw-bold text-muted uppercase tracking-widest mb-1">Candidates
                                    </div>
                                    <h2 class="display-6 fw-black text-dark mb-0">{{ results.stats.total_candidates }}
                                    </h2>
                                    <div class="mt-2">
                                        <span class="text-success fw-bold small">+3%</span>
                                        <span class="text-muted text-xs ms-2">engagement</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-3 p-2 shadow-sm border border-light">
                                    <i class="bi bi-people fs-4 text-dark"></i>
                                </div>
                            </div>
                            <div class="status-line bg-dark opacity-10"></div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-sm-6 col-xl-3 animate-slide-up" style="animation-delay: 0.4s">
                        <div class="glass-card p-4 h-100 position-relative overflow-hidden transition-hover"
                            :class="isEventActive ? 'border-success border-opacity-10' : 'border-danger border-opacity-10'">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <div class="text-xs fw-bold text-muted uppercase tracking-widest mb-1">Live Status
                                    </div>
                                    <h2 class="h3 fw-black text-dark mb-0 pt-2">{{ isEventActive ? 'Active' : 'Ended' }}
                                    </h2>
                                    <div class="mt-2">
                                        <span class="text-muted text-xs">{{ isEventActive ? 'Awaiting more votes' :
                                            'Voting has concluded' }}</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-3 p-2 shadow-sm border border-light"
                                    :class="isEventActive ? 'text-success' : 'text-danger'">
                                    <i :class="['bi fs-4', isEventActive ? 'bi-check2-circle' : 'bi-slash-circle']"></i>
                                </div>
                            </div>
                            <div class="status-line"
                                :class="isEventActive ? 'bg-success opacity-25' : 'bg-danger opacity-25'"></div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Row -->
                <div v-for="(cat, catIdx) in results.categories" :key="cat.id" class="row g-4 mb-5 animate-slide-up"
                    :style="{ animationDelay: `${0.5 + Number(catIdx) * 0.1}s` }">
                    <!-- Rankings List -->
                    <div class="col-lg-7">
                        <div class="glass-card p-4 p-lg-5 h-100 shadow-sm border-0">
                            <div class="d-flex justify-content-between align-items-center mb-5">
                                <h4 class="fw-black text-dark mb-0">{{ cat.name }} Rankings</h4>
                                <div
                                    class="badge bg-light text-dark rounded-pill px-3 py-2 border border-light shadow-sm">
                                    <i class="bi bi-check2-circle me-1"></i> Verified
                                </div>
                            </div>

                            <div class="ranking-list">
                                <div v-for="(cand, idx) in cat.candidates" :key="cand.id"
                                    class="d-flex align-items-center justify-content-between py-3 px-2 border-bottom border-light border-opacity-50 last-child-border-0 transition-hover rounded-3 mb-2">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="rank-indicator fw-black text-muted-gradient small">
                                            {{ (idx + 1).toString().padStart(2, '0') }}</div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6">{{ cand.name }}</div>
                                            <div class="text-xs text-muted fw-medium">{{ getPercentage(cand.votes,
                                                getTotalCatVotes(cat)) }}% of total votes</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div :class="['status-pill px-3 py-1 rounded-pill text-xs fw-bold',
                                            idx === 0 ? 'bg-blue-soft text-blue' :
                                                idx === 1 ? 'bg-lime-soft text-lime' :
                                                    'bg-orange-soft text-orange']">
                                            {{ cand.votes.toLocaleString() }}
                                        </div>
                                        <!-- <div class="text-dark fw-bold">${{ (cand.votes * 1.25).toFixed(2) }}</div>
                                        <button class="btn btn-link text-muted p-0 ms-2">
                                            <i class="bi bi-three-dots fs-5"></i>
                                        </button> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Donut Chart -->
                    <div class="col-lg-5">
                        <div class="glass-card p-4 p-lg-5 h-100 shadow-sm border-0 d-flex flex-column">
                            <h4 class="fw-black text-dark mb-5">Vote Distribution</h4>
                            <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative">
                                <client-only>
                                    <component :is="ApexChart" v-if="ApexChart" type="donut" width="100%" height="380"
                                        :options="getChartOptions(cat)" :series="getChartSeries(cat)" />
                                </client-only>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, shallowRef, computed } from 'vue';
import { useRoute } from 'vue-router';
import { publicApi } from '~/api/endpoints/public';

definePageMeta({
    layout: 'public'
});

interface Candidate {
    id: number;
    name: string;
    votes: number;
    image?: string;
}

interface Category {
    id: number;
    name: string;
    candidates: Candidate[];
}

interface EventResults {
    event: {
        id: number;
        name: string;
        ends_at?: string;
    };
    categories: Category[];
    stats: {
        total_votes: number;
        total_candidates: number;
    };
}

const route = useRoute();
const token = route.params.token as string;

const loading = ref(true);
const results = ref<EventResults | null>(null);
const error = ref(false);
const ApexChart = shallowRef<any>(null);

const isEventActive = computed(() => {
    if (!results.value?.event?.ends_at) return true;
    return new Date(results.value.event.ends_at) > new Date();
});

const fetchResults = async () => {
    try {
        loading.value = true;
        error.value = false;
        const response = await publicApi.getResultsByToken(token);
        results.value = response.data;
    } catch (err) {
        console.error('Failed to fetch results:', err);
        error.value = true;
    } finally {
        loading.value = false;
    }
};

const getTotalCatVotes = (cat: Category) => {
    return cat.candidates.reduce((sum: number, c: Candidate) => sum + c.votes, 0);
};

const getPercentage = (votes: number, total: number) => {
    if (total === 0) return 0;
    return Math.round((votes / total) * 100);
};

const getChartOptions = (cat: Category) => {
    return {
        chart: {
            fontFamily: 'Inter, sans-serif',
            toolbar: { show: false }
        },
        labels: cat.candidates.map((c: any) => c.name),
        colors: ['#A0D468', '#4FC1E9', '#FFCE54', '#ED5565', '#AC92EC', '#EC87C0', '#F6BB42', '#8CC152'],
        legend: {
            position: 'bottom',
            fontSize: '14px',
            horizontalAlign: 'center',
            markers: { radius: 12 }
        },
        dataLabels: { enabled: false },
        stroke: { show: false },
        tooltip: {
            y: {
                formatter: (val: any) => `${val} votes`
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            offsetY: -10,
                            fontWeight: 700
                        },
                        value: {
                            show: true,
                            offsetY: 10,
                            fontWeight: 800,
                            fontSize: '24px',
                            formatter: (val: any) => val
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            color: '#6c757d',
                            formatter: () => getTotalCatVotes(cat).toLocaleString()
                        }
                    }
                }
            }
        }
    };
};

const getChartSeries = (cat: Category) => {
    return cat.candidates.map((c: Candidate) => c.votes);
};

onMounted(async () => {
    if (token) {
        await fetchResults();
    }
    if (import.meta.client) {
        const VueApexCharts = (await import('vue3-apexcharts')).default;
        ApexChart.value = VueApexCharts;

        // @ts-ignore
        const { $echo } = useNuxtApp();
        if ($echo && results.value?.event?.id) {
            // @ts-ignore
            $echo.channel(`event.${results.value.event.id}`)
                .listen('.vote.cast', (e: any) => {
                    console.log('Real-time update received:', e);
                    fetchResults();
                });
        }
    }
});
</script>

<style scoped>
.results-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 50%, #bccad6 100%);
    background-attachment: fixed;
}


.text-xs {
    font-size: 0.7rem;
}

.uppercase {
    text-transform: uppercase;
}

.tracking-widest {
    letter-spacing: 0.1em;
}


.status-line {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60%;
    height: 4px;
    border-radius: 0 4px 4px 0;
}

/* Color Pills */
.bg-blue-soft {
    background-color: #e0f2fe;
}

.text-blue {
    color: #0284c7;
}

.bg-lime-soft {
    background-color: #f0fdf4;
}

.text-lime {
    color: #16a34a;
}

.bg-orange-soft {
    background-color: #fff7ed;
}

.text-orange {
    color: #ea580c;
}

.text-muted-gradient {
    color: rgba(26, 32, 44, 0.4);
}


.rank-indicator {
    width: 24px;
    font-size: 0.8rem;
}

.last-child-border-0:last-child {
    border-bottom: none !important;
}


@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

.spin {
    animation: spin 1s linear infinite;
}

/* Adjustments for ApexCharts donut */
:deep(.apexcharts-canvas) {
    margin: 0 auto;
}

:deep(.apexcharts-datalabels-group) {
    font-family: 'Outfit', sans-serif !important;
}

@media (max-width: 768px) {
    .display-6 {
        font-size: 2rem;
    }
}
</style>
