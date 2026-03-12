<template>
    <div class="dashboard-wrapper min-vh-100 d-flex page-bg">
        <!-- Overlay for mobile sidebar -->
        <div v-if="sidebarOpen" class="sidebar-overlay position-fixed top-0 start-0 w-100 h-100 bg-dark opacity-50 z-2 d-lg-none" @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside class="sidebar glass-card border-start-0 border-top-0 border-bottom-0 rounded-0 z-3 transition-base d-flex flex-column" :class="{ 'sidebar-open': sidebarOpen }">
            <div class="sidebar-header p-4 d-flex align-items-center justify-content-between">
                <NuxtLink to="/dashboard" class="text-decoration-none transition-hover">
                    <h4 class="fw-black text-dark mb-0 d-flex align-items-center gap-2 m-0 fs-5 pb-1">
                        <i class="bi bi-box-fill text-primary"></i>
                        Ballotify
                    </h4>
                </NuxtLink>
                <button class="btn btn-link text-dark p-0 d-lg-none" @click="sidebarOpen = false">
                    <i class="bi bi-x-lg fs-5"></i>
                </button>
            </div>
            
            <div class="sidebar-menu px-3 py-2 flex-grow-1 overflow-y-auto">
                <p class="small fw-bold text-muted tracking-widest uppercase mb-3 px-3">Menu</p>
                <nav class="nav flex-column gap-2 mb-4">
                    <NuxtLink to="/dashboard" class="nav-link text-dark fw-medium rounded-3 px-3 py-2 d-flex align-items-center gap-3 transition-hover" active-class="active-link bg-primary bg-opacity-10 text-primary fw-bold" exact>
                        <i class="bi bi-grid-1x2-fill fs-5 opacity-75"></i> Overview
                    </NuxtLink>
                    <NuxtLink to="/dashboard/events" class="nav-link text-dark fw-medium rounded-3 px-3 py-2 d-flex align-items-center gap-3 transition-hover" active-class="active-link bg-primary bg-opacity-10 text-primary fw-bold">
                        <i class="bi bi-calendar-event-fill fs-5 opacity-75"></i> Events
                    </NuxtLink>
                    <NuxtLink to="/dashboard/voters" class="nav-link text-dark fw-medium rounded-3 px-3 py-2 d-flex align-items-center gap-3 transition-hover" active-class="active-link bg-primary bg-opacity-10 text-primary fw-bold">
                        <i class="bi bi-people-fill fs-5 opacity-75"></i> Voters
                    </NuxtLink>
                </nav>

                <p class="small fw-bold text-muted tracking-widest uppercase mb-3 px-3">Settings</p>
                <nav class="nav flex-column gap-2">
                    <NuxtLink to="/dashboard/profile" class="nav-link text-dark fw-medium rounded-3 px-3 py-2 d-flex align-items-center gap-3 transition-hover" active-class="active-link bg-primary bg-opacity-10 text-primary fw-bold">
                        <i class="bi bi-person-fill fs-5 opacity-75"></i> Profile
                    </NuxtLink>
                </nav>
            </div>

            <div class="sidebar-footer p-4 border-top border-white border-opacity-25 mt-auto">
                <button @click="logout" class="btn btn-link text-danger text-decoration-none fw-bold p-0 d-flex align-items-center gap-2 w-100 transition-hover">
                    <i class="bi bi-box-arrow-right fs-5"></i> Log out
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content flex-grow-1 max-w-full d-flex flex-column h-100 min-vh-100">
            <!-- Top Navbar -->
            <header class="top-nav px-4 py-3 d-flex align-items-center justify-content-between position-sticky top-0 z-1 glass-card border-top-0 border-end-0 border-start-0 rounded-0 shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-dark p-0 d-lg-none" @click="sidebarOpen = true">
                        <i class="bi bi-list fs-3"></i>
                    </button>
                    <!-- Search could go here -->
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <span class="d-none d-sm-inline fw-bold text-dark small" v-if="authStore.user">
                        Hello, {{ authStore.user.name.split(' ')[0] }}! 👋
                    </span>
                    <NuxtLink to="/dashboard/profile">
                        <img :src="`https://api.dicebear.com/7.x/initials/svg?seed=${authStore.user?.name || 'User'}`" class="avatar rounded-circle border border-2 border-white shadow-sm transition-hover" alt="Profile">
                    </NuxtLink>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content-wrapper p-4 flex-grow-1 overflow-x-hidden">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const sidebarOpen = ref(false);

// Ensure user is loaded
onMounted(async () => {
    if (!authStore.user && authStore.isAuthenticated) {
        await authStore.fetchUser();
    }
});

const logout = () => {
    authStore.logout();
    router.push('/login');
};
</script>

<style scoped>
.dashboard-wrapper {
    overflow-x: hidden;
}

.sidebar {
    width: 280px;
    height: 100vh;
    position: fixed;
    left: -280px;
    top: 0;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1050; /* Above regular content and overlay */
}

.sidebar-open {
    left: 0 !important;
}

.sidebar-overlay {
    z-index: 1040;
    backdrop-filter: blur(4px);
}

.main-content {
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    width: 100%;
}

.avatar {
    width: 40px;
    height: 40px;
}

.active-link {
    pointer-events: none;
}
.active-link i {
    opacity: 1 !important;
}

@media (min-width: 992px) {
    .sidebar {
        left: 0;
        z-index: 10;
        position: sticky;
        top: 0;
    }
    
    .main-content {
        width: calc(100% - 280px);
    }
}
</style>
