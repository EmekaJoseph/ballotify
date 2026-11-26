<template>
    <div class="d-flex flex-column min-vh-100">
        <header class="site-header" :class="{ scrolled }">
            <div class="container d-flex align-items-center justify-content-between gap-3">
                <AppLogo />

                <nav class="d-none d-xl-flex align-items-center gap-1" aria-label="Main">
                    <NuxtLink v-for="l in links" :key="l.to" :to="l.to" class="nav-l">{{ l.label }}</NuxtLink>
                </nav>

                <div class="d-none d-xl-flex align-items-center gap-2">
                    <ThemeToggle class="me-1" />
                    <template v-if="auth.isAuthenticated">
                        <NuxtLink to="/dashboard" class="btn btn-primary">Dashboard <i class="bi bi-arrow-right"></i></NuxtLink>
                    </template>
                    <template v-else>
                        <NuxtLink to="/login" class="btn btn-ghost">Sign in</NuxtLink>
                        <NuxtLink to="/signup" class="btn btn-primary">Get started</NuxtLink>
                    </template>
                </div>

                <button class="btn btn-ghost btn-icon d-xl-none" :aria-expanded="menuOpen" aria-label="Menu"
                    @click="menuOpen = !menuOpen">
                    <i class="bi" :class="menuOpen ? 'bi-x-lg' : 'bi-list'"></i>
                </button>
            </div>

            <Transition name="drop">
                <div v-if="menuOpen" class="mobile-menu d-xl-none">
                    <div class="container d-grid gap-1 py-3">
                        <div class="d-flex justify-content-between align-items-center px-2 pb-2"><span class="small-caps">Theme</span><ThemeToggle /></div>
                        <NuxtLink v-for="l in links" :key="l.to" :to="l.to" class="nav-l">{{ l.label }}</NuxtLink>
                        <div class="divider my-2"></div>
                        <NuxtLink v-if="auth.isAuthenticated" to="/dashboard" class="btn btn-primary">Dashboard</NuxtLink>
                        <template v-else>
                            <NuxtLink to="/login" class="btn btn-ghost">Sign in</NuxtLink>
                            <NuxtLink to="/signup" class="btn btn-primary">Get started free</NuxtLink>
                        </template>
                    </div>
                </div>
            </Transition>
        </header>

        <main class="flex-grow-1">
            <slot />
        </main>

        <TheFooter />
        <SupportButton :context="`Website ${route.path}`" />
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const menuOpen = ref(false);
const scrolled = ref(false);

const links = [
    { to: '/#features', label: 'Features' },
    { to: '/#how', label: 'How it works' },
    { to: '/#awards', label: 'Award nights' },
    { to: '/#pricing', label: 'Pricing' },
    { to: '/help', label: 'Help' },
    { to: '/demo/vote', label: 'Live demo' },
];

watch(() => route.fullPath, () => { menuOpen.value = false; });

const onScroll = () => { scrolled.value = window.scrollY > 8; };
onMounted(() => { onScroll(); window.addEventListener('scroll', onScroll, { passive: true }); });
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<style scoped>
.site-header {
    position: sticky; top: 0; z-index: 100; padding: .85rem 0;
    background: color-mix(in srgb, var(--bg) 80%, transparent); backdrop-filter: saturate(180%) blur(14px);
    border-bottom: 1px solid transparent; transition: border-color .2s, background-color .2s;
}
.site-header.scrolled { border-bottom-color: var(--line); background: var(--header-bg); }
.nav-l { padding: .5rem .7rem; border-radius: 10px; font-weight: 600; color: var(--ink-2); font-size: .95rem; white-space: nowrap; }
.site-header .btn { white-space: nowrap; }
.nav-l:hover { color: var(--brand-700); background: var(--brand-50); }
.mobile-menu { border-top: 1px solid var(--line); background: var(--surface); }
.drop-enter-active, .drop-leave-active { transition: opacity .18s var(--ease), transform .18s var(--ease); }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
