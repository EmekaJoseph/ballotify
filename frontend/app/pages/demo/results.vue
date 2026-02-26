<template>
    <div class="results-page py-5 py-lg-5">
        <div class="container animate-fade-in">
            <!-- Loading State (Simulated) -->
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-grow text-primary mb-4" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="fw-bold text-dark">Gathering demo results</h4>
                <p class="text-muted fw-medium">Simulating live data feed...</p>
            </div>

            <!-- Content -->
            <div v-else>
                <!-- Dashboard Header -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3">
                    <div>
                        <h1 class="h2 fw-black text-dark mb-1">Demo Dashboard</h1>
                        <p class="text-muted fw-medium mb-0">{{ results.event.name }} Results</p>
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
                                        <span class="text-success fw-bold small">+100%</span>
                                        <span class="text-muted text-xs ms-2">demo coverage</span>
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
                                        <span class="text-success fw-bold small">Full</span>
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
                                        <span class="text-success fw-bold small">Live</span>
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
                        <div
                            class="glass-card p-4 h-100 position-relative overflow-hidden border-success border-opacity-10 transition-hover">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <div class="text-xs fw-bold text-muted uppercase tracking-widest mb-1">Live Status
                                    </div>
                                    <h2 class="h3 fw-black text-dark mb-0 pt-2">Active</h2>
                                    <div class="mt-2">
                                        <span class="text-muted text-xs">Awaiting more votes</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-3 p-2 shadow-sm border border-light text-success">
                                    <i class="bi bi-check2-circle fs-4"></i>
                                </div>
                            </div>
                            <div class="status-line bg-success opacity-25"></div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Row -->
                <div v-for="(cat, catIdx) in results.categories" :key="cat.id" class="row g-4 mb-5 animate-slide-up"
                    :style="{ animationDelay: `${0.5 + catIdx * 0.1}s` }">
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
import { ref, onMounted, shallowRef } from 'vue';

definePageMeta({
    layout: 'public'
});

const loading = ref(true);
const ApexChart = shallowRef<any>(null);

// Hardcoded Results Data
const results = {
    event: { name: 'Election Demo 2026' },
    categories: [
        {
            id: 1,
            name: 'Executive Council',
            candidates: [
                { id: 101, name: 'Dr. Sarah Mitchell', votes: 845 },
                { id: 102, name: 'Eng. Marcus Chen', votes: 612 }
            ]
        },
        {
            id: 2,
            name: 'Technical Committee',
            candidates: [
                { id: 201, name: 'Prof. David Okafor', votes: 1204 },
                { id: 202, name: 'Linda Gustafsson', votes: 958 }
            ]
        }
    ],
    stats: {
        total_votes: 3619,
        total_candidates: 4
    }
};

const getTotalCatVotes = (cat: any) => {
    return cat.candidates.reduce((sum: number, c: any) => sum + c.votes, 0);
};

const getPercentage = (votes: number, total: number) => {
    if (total === 0) return 0;
    return Math.round((votes / total) * 100);
};

const getChartOptions = (cat: any) => {
    return {
        chart: {
            fontFamily: 'Outfit, sans-serif',
            toolbar: { show: false }
        },
        labels: cat.candidates.map((c: any) => c.name),
        colors: ['#4FC1E9', '#A0D468', '#FFCE54', '#ED5565'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: false },
        stroke: { show: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: () => getTotalCatVotes(cat).toLocaleString()
                        }
                    }
                }
            }
        }
    };
};

const getChartSeries = (cat: any) => {
    return cat.candidates.map((c: any) => c.votes);
};

onMounted(async () => {
    setTimeout(() => {
        loading.value = false;
    }, 800);

    if (import.meta.client) {
        const VueApexCharts = (await import('vue3-apexcharts')).default;
        ApexChart.value = VueApexCharts;
    }
});
</script>

<style scoped>
.results-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 50%, #bccad6 100%);
    background-attachment: fixed;
}

/* Glassmorphism Card */
.glass-card {
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), inset 0 0 20px rgba(255, 255, 255, 0.5);
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

.transition-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.transition-hover:hover {
    transform: translateY(-4px);
    background: rgba(255, 255, 255, 0.7);
}

.rank-indicator {
    width: 24px;
}

.last-child-border-0:last-child {
    border-bottom: none !important;
}

.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-slide-up {
    opacity: 0;
    animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(15px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.uppercase {
    text-transform: uppercase;
}

.tracking-widest {
    letter-spacing: 0.1em;
}

.text-xs {
    font-size: 0.75rem;
}

:deep(.apexcharts-canvas) {
    margin: 0 auto;
}
</style>
