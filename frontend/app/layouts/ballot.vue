<template>
    <div class="ballot-shell">
        <header class="ballot-header">
            <div class="container d-flex align-items-center justify-content-between" :style="{ maxWidth: wide ? '1140px' : '760px' }">
                <AppLogo />
                <span class="small text-muted d-none d-sm-inline-flex align-items-center gap-1"><i class="bi bi-shield-lock"></i> Secure ballot</span>
            </div>
        </header>
        <main class="container py-4 py-md-5" :style="{ maxWidth: wide ? '1140px' : '760px' }">
            <slot />
        </main>
        <footer class="text-center small text-muted pb-4">
            Powered by <NuxtLink to="/" class="fw-bold">Ballotify</NuxtLink>
        </footer>
    </div>
</template>

<script setup lang="ts">
const route = useRoute();
const wide = computed(() => Boolean(route.meta.wide));
</script>

<style scoped>
.ballot-shell { min-height: 100vh; display: flex; flex-direction: column; background: var(--bg); }
.ballot-shell > main { flex: 1; }
.ballot-header { position: sticky; top: 0; z-index: 20; padding: .8rem 0; background: rgba(255, 255, 255, .9); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
</style>
