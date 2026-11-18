<template>
    <div class="dash">
        <!-- Sidebar -->
        <aside class="sidebar" :class="{ open: sidebarOpen }" aria-label="Dashboard navigation">
            <div class="d-flex align-items-center justify-content-between px-3 pt-4 pb-3">
                <AppLogo to="/dashboard" />
                <button class="btn btn-ghost btn-icon btn-sm d-lg-none" aria-label="Close menu" @click="sidebarOpen = false">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="px-3 mb-3">
                <NuxtLink to="/dashboard/events/create" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg"></i> New event
                </NuxtLink>
            </div>

            <nav class="px-2 d-grid gap-1">
                <div class="small-caps px-3 pt-2 pb-1">Workspace</div>
                <NuxtLink v-for="item in nav" :key="item.to" :to="item.to" class="side-link"
                    :class="{ active: isActive(item) }">
                    <i class="bi" :class="item.icon"></i>{{ item.label }}
                </NuxtLink>
            </nav>

            <div class="mt-auto p-3">
                <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                    <span class="small-caps">Theme</span>
                    <ThemeToggle />
                </div>
                <div class="user-card">
                    <span class="avatar sm" :style="{ background: colorFor(auth.user?.email) }">{{ initials(auth.user?.name) }}</span>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold small text-ink truncate">{{ auth.user?.name || '…' }}</div>
                        <div class="small text-muted truncate">{{ auth.user?.email }}</div>
                    </div>
                    <button class="btn btn-ghost btn-icon btn-sm" title="Sign out" aria-label="Sign out" @click="logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </div>
            </div>
        </aside>
        <div v-if="sidebarOpen" class="scrim d-lg-none" @click="sidebarOpen = false"></div>

        <!-- Main -->
        <div class="main">
            <header class="topbar d-lg-none">
                <button class="btn btn-ghost btn-icon" aria-label="Open menu" @click="sidebarOpen = true">
                    <i class="bi bi-list"></i>
                </button>
                <AppLogo to="/dashboard" />
                <NuxtLink to="/dashboard/profile" aria-label="Profile">
                    <span class="avatar sm" :style="{ background: colorFor(auth.user?.email) }">{{ initials(auth.user?.name) }}</span>
                </NuxtLink>
            </header>
            <main class="content">
                <div v-if="auth.user && !auth.isVerified" class="verify-banner">
                    <i class="bi bi-envelope-exclamation"></i>
                    <span class="flex-grow-1">Please confirm your email address <b>{{ auth.user.email }}</b> to send voting codes. Check your inbox for our link.</span>
                    <button class="btn btn-soft btn-sm flex-shrink-0" :disabled="resending" @click="resendVerification">
                        <span v-if="resending" class="spinner-border spinner-border-sm"></span> Resend email
                    </button>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';
import { adminApi } from '~/api/endpoints/admin';

const auth = useAuthStore();
const route = useRoute();
const sidebarOpen = ref(false);

const nav = [
    { to: '/dashboard', label: 'Overview', icon: 'bi-grid-1x2', exact: true },
    { to: '/dashboard/events', label: 'Events', icon: 'bi-calendar2-check', exact: false },
    { to: '/dashboard/voters', label: 'Voters', icon: 'bi-people', exact: false },
    { to: '/dashboard/profile', label: 'Account', icon: 'bi-person-gear', exact: false },
];

const isActive = (item: { to: string; exact: boolean }) =>
    item.exact ? route.path === item.to : route.path.startsWith(item.to);

watch(() => route.fullPath, () => { sidebarOpen.value = false; });

onMounted(() => {
    if (!auth.user && auth.isAuthenticated) auth.fetchUser();
});

const resending = ref(false);
async function resendVerification() {
    resending.value = true;
    try {
        const { data } = await adminApi.resendVerification();
        useToast().success(data.message);
    } catch (e) {
        useToast().error(apiErrorMessage(e));
    } finally {
        resending.value = false;
    }
}

async function logout() {
    await auth.logout();
    await navigateTo('/login');
}
</script>

<style scoped>
.dash { min-height: 100vh; display: flex; background: var(--bg); }
.sidebar {
    width: 264px; flex-shrink: 0; background: var(--surface); border-right: 1px solid var(--line);
    display: flex; flex-direction: column; position: fixed; inset: 0 auto 0 0; z-index: 1200;
    transform: translateX(-100%); transition: transform .25s var(--ease);
}
.sidebar.open { transform: none; box-shadow: var(--shadow-lg); }
.scrim { position: fixed; inset: 0; background: rgba(10, 18, 34, .45); z-index: 1100; }
.main { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; }
.topbar {
    position: sticky; top: 0; z-index: 50; display: flex; align-items: center; justify-content: space-between;
    padding: .7rem 1rem; background: var(--header-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line);
}
.content { padding: 1.5rem 1rem 3rem; width: 100%; max-width: 1240px; margin: 0 auto; }
@media (min-width: 768px) { .content { padding: 2rem 2rem 4rem; } }
@media (min-width: 992px) {
    .sidebar { position: sticky; top: 0; height: 100vh; transform: none; }
    .content { padding: 2.25rem 2.5rem 4rem; }
}
.side-link {
    display: flex; align-items: center; gap: .75rem; padding: .65rem .9rem; border-radius: 12px;
    color: var(--ink-2); font-weight: 600; font-size: .95rem; transition: background-color .15s, color .15s;
}
.side-link .bi { font-size: 1.05rem; color: var(--muted); }
.side-link:hover { background: var(--surface-2); color: var(--ink); }
.side-link.active { background: var(--brand-50); color: var(--brand-700); }
.side-link.active .bi { color: var(--brand-600); }
.verify-banner {
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .85rem 1.1rem; margin-bottom: 1.5rem;
    border-radius: var(--radius); background: var(--warning-50); color: var(--warning-text); font-size: .9rem;
}
.verify-banner > .bi { font-size: 1.2rem; }
.user-card { display: flex; align-items: center; gap: .65rem; padding: .65rem; border: 1px solid var(--line); border-radius: 14px; background: var(--surface-2); }
</style>
