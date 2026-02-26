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
            <!-- Sleek Hero Banner -->
            <div v-else-if="results">
                <div class="card border-0 modern-gradient-card rounded-5 mb-5 overflow-hidden shadow">
                    <div class="card-body p-5 position-relative">
                        <!-- Decorative Shapes -->
                        <div class="position-absolute top-0 end-0 opacity-25" style="transform: translate(20%, -30%);">
                            <svg width="400" height="400" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#FFFFFF"
                                    d="M45.7,-76.1C58.9,-69.3,69,-55.4,78.2,-40.8C87.4,-26.2,95.7,-11,94.3,3.7C93,18.4,82,32.6,71.5,45.4C61,58.2,51,69.5,38.2,76.5C25.4,83.5,9.8,86.2,-5.4,84.4C-20.6,82.6,-35.4,76.3,-48.5,67.6C-61.6,58.9,-73,47.8,-80.6,34.3C-88.2,20.8,-92,4.9,-89.6,-10.1C-87.2,-25.1,-78.6,-39.2,-67.2,-50.2C-55.8,-61.2,-41.6,-69.1,-27.8,-74.6C-14,-80.1,1.4,-83.2,16.5,-80.5C31.6,-77.8,45.7,-76.1,45.7,-76.1Z" />
                            </svg>
                        </div>

                        <div class="row align-items-center position-relative z-1">
                            <div class="col-lg-8 text-white">
                                <div
                                    class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-2 small d-inline-block mb-3 fw-bold tracking-wider uppercase border border-white border-opacity-25 backdrop-blur">
                                    <i class="bi bi-broadcast me-2 pulse-icon"></i> Live Election Dashboard
                                </div>
                                <h1 class="display-4 fw-bolder mb-2 text-shadow">{{ results.event.name }}</h1>
                                <p class="lead mb-0 text-white-50 fw-medium">Real-time voting analytics and transparent
                                    results break down.</p>
                            </div>
                            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                                <button @click="fetchResults"
                                    class="btn btn-light rounded-pill px-5 py-3 fw-bold transition-hover shadow hover-lift text-primary"
                                    :disabled="loading">
                                    <i class="bi bi-arrow-clockwise me-2" :class="{ 'spin': loading }"></i> Refresh Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Stat Cards -->
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div
                            class="card border-0 modern-card rounded-4 h-100 p-4 transition-hover shadow-sm position-relative overflow-hidden">
                            <div class="position-absolute top-0 end-0 p-3 opacity-10">
                                <i class="bi bi-person-check-fill shadow-icon" style="font-size: 5rem;"></i>
                            </div>
                            <div class="d-flex align-items-center mb-4 position-relative z-1">
                                <div
                                    class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 shadow-inner">
                                    <i class="bi bi-person-check-fill fs-4"></i>
                                </div>
                                <h6 class="text-muted mb-0 fw-bold uppercase tracking-wider small">Total Votes Cast</h6>
                            </div>
                            <h2 class="display-5 fw-black text-dark mb-0 position-relative z-1">{{
                                results.stats.total_votes.toLocaleString() }}</h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div
                            class="card border-0 modern-card rounded-4 h-100 p-4 transition-hover shadow-sm position-relative overflow-hidden">
                            <div class="position-absolute top-0 end-0 p-3 opacity-10">
                                <i class="bi bi-people-fill shadow-icon" style="font-size: 5rem;"></i>
                            </div>
                            <div class="d-flex align-items-center mb-4 position-relative z-1">
                                <div
                                    class="stat-icon-wrapper bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3 shadow-inner">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                                <h6 class="text-muted mb-0 fw-bold uppercase tracking-wider small">Candidates</h6>
                            </div>
                            <h2 class="display-5 fw-black text-dark mb-0 position-relative z-1">{{
                                results.stats.total_candidates.toLocaleString() }}</h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div
                            class="card border-0 modern-gradient-card rounded-4 h-100 p-4 transition-hover shadow-lg text-white position-relative overflow-hidden">
                            <div class="position-absolute top-0 end-0 p-3 opacity-25">
                                <i class="bi bi-activity shadow-icon" style="font-size: 5rem;"></i>
                            </div>
                            <div class="position-absolute top-0 start-0 w-100 h-100 bg-glass overlay-glass"></div>

                            <div class="d-flex align-items-center mb-4 position-relative z-1">
                                <div
                                    class="stat-icon-wrapper bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center me-3 backdrop-blur shadow-inner">
                                    <i class="bi bi-trophy-fill fs-4 text-white"></i>
                                </div>
                                <h6 class="mb-0 fw-bold uppercase tracking-wider small text-white-50">Event Status</h6>
                            </div>
                            <h2 class="h3 fw-black mb-0 position-relative z-1 d-flex align-items-center">
                                <span class="status-indicator bg-success me-2 pulse-ring"></span> Live & Active
                            </h2>
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="category-grid">
                    <div v-for="cat in results.categories" :key="cat.id"
                        class="category-block mb-5 pb-5 border-bottom border-light">
                        <div class="d-flex align-items-center mb-5">
                            <div class="bg-primary rounded-pill me-3" style="width: 8px; height: 32px;"></div>
                            <h2 class="fw-bolder text-dark mb-0">{{ cat.name }}</h2>
                        </div>

                        <div class="row g-5">
                            <!-- Leaderboard -->
                            <div class="col-xl-7">
                                <div class="card border-0 modern-card rounded-4 p-4 p-lg-5 shadow-sm h-100 bg-white">
                                    <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom">Rankings</h5>
                                    <div class="candidate-list">
                                        <div v-for="(cand, idx) in cat.candidates" :key="cand.id"
                                            class="candidate-row p-3 mb-3 rounded-4 border transition-hover bg-light d-flex align-items-center justify-content-between position-relative overflow-hidden"
                                            :class="{ 'border-warning': idx === 0, 'border-light bg-opacity-50': idx !== 0 }">

                                            <!-- Success Background Fill for Leader -->
                                            <div v-if="idx === 0"
                                                class="position-absolute top-0 start-0 w-100 h-100 bg-warning bg-opacity-10 border-warning"
                                                style="z-index: 0;"></div>

                                            <div class="d-flex align-items-center position-relative z-1 w-100">
                                                <!-- Rank -->
                                                <div class="rank-badge me-4 shadow-sm"
                                                    :class="idx === 0 ? 'rank-1' : 'rank-other'">
                                                    {{ idx + 1 }}
                                                </div>

                                                <!-- Avatar & Name -->
                                                <div class="d-flex align-items-center flex-grow-1">
                                                    <div
                                                        class="candidate-avatar me-3 shadow-sm border border-white p-1 rounded-circle bg-white">
                                                        <i v-if="!cand.image_path"
                                                            class="bi bi-person-fill text-muted fs-5"></i>
                                                        <img v-else :src="`/${cand.image_path}`"
                                                            class="rounded-circle w-100 h-100 object-fit-cover" />
                                                    </div>
                                                    <div>
                                                        <div class="fw-bolder text-dark mb-1 lh-1">{{ cand.name }}</div>
                                                        <div v-if="idx === 0"
                                                            class="badge bg-warning text-dark small fw-bold px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1">
                                                            <i class="bi bi-star-fill text-dark"
                                                                style="font-size: 0.6rem;"></i> Leader
                                                        </div>
                                                        <div v-else class="text-muted small fw-medium">Candidate</div>
                                                    </div>
                                                </div>

                                                <!-- Stats -->
                                                <div class="d-flex flex-column align-items-end ms-4">
                                                    <div class="fw-black text-dark fs-5 lh-1 mb-1">{{
                                                        cand.votes.toLocaleString() }} <span
                                                            class="fs-6 text-muted fw-normal">votes</span></div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-bold text-primary small">{{
                                                            getPercentage(cand.votes, getTotalCatVotes(cat)) }}%</span>
                                                        <div class="progress d-none d-sm-flex shadow-inner"
                                                            style="width: 80px; height: 6px; background-color: var(--bs-gray-200);">
                                                            <div class="progress-bar rounded-pill bg-primary"
                                                                :class="{ 'bg-warning': idx === 0 }"
                                                                :style="{ width: getPercentage(cand.votes, getTotalCatVotes(cat)) + '%' }">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Charts -->
                            <div class="col-xl-5">
                                <div
                                    class="card border-0 modern-card rounded-4 p-4 p-lg-5 shadow-sm h-100 d-flex flex-column">
                                    <h5 class="fw-bold text-dark mb-4 text-center text-lg-start">Vote Distribution</h5>
                                    <div class="flex-grow-1 d-flex align-items-center justify-content-center py-4"
                                        style="min-height: 400px;">
                                        <client-only>
                                            <component :is="ApexChart" v-if="ApexChart" type="donut" width="100%"
                                                height="380" :options="getChartOptions(cat)"
                                                :series="getChartSeries(cat)" />
                                        </client-only>
                                    </div>
                                </div>
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
import { useRoute } from 'vue-router';
import { publicApi } from '~/api/endpoints/public';

definePageMeta({
    layout: 'public'
});

const route = useRoute();
const token = route.params.token as string;

const loading = ref(true);
const results = ref<any>(null);
const error = ref(false);
const ApexChart = shallowRef<any>(null);

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
            fontFamily: 'Inter, sans-serif',
            toolbar: { show: false }
        },
        labels: cat.candidates.map((c: any) => c.name),
        colors: ['#0d6efd', '#6610f2', '#0dcaf0', '#20c997', '#ffc107', '#fd7e14', '#dc3545', '#d63384'],
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

const getChartSeries = (cat: any) => {
    return cat.candidates.map((c: any) => c.votes);
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
.modern-card {
    background: #ffffff;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.02), 0 1px 1px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.01) !important;
}

.modern-gradient-card {
    background: linear-gradient(135deg, #0d6efd 0%, #4608ad 100%);
    box-shadow: 0 15px 35px rgba(13, 110, 253, 0.2);
}

.stat-icon {
    width: 64px;
    height: 64px;
}

.candidate-avatar {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.rank-badge {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
}

.rank-1 {
    background: linear-gradient(135deg, #ffca2c 0%, #ff8a00 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(255, 138, 0, 0.3);
}

.rank-other {
    background: #f8f9fa;
    color: #adb5bd;
    border: 1px solid #e9ecef;
}

.transition-hover {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.transition-hover:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.06);
}

.error-icon {
    width: 100px;
    height: 100px;
}

.animate-fade-in {
    animation: fadeIn 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.uppercase {
    text-transform: uppercase;
}

.tracking-wider {
    letter-spacing: 0.2em;
}

.btn-white {
    background: #fff;
    color: #212529;
    border: 1px solid #e9ecef;
}

.btn-white:hover {
    background: #f8f9fa;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
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

.text-balance {
    text-wrap: balance;
}

.table> :not(caption)>*>* {
    padding: 1.25rem 0.5rem;
}
</style>
