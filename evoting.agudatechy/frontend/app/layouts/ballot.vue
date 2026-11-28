<template>
    <div class="ballot-shell">
        <header class="ballot-header">
            <div class="container d-flex align-items-center justify-content-between" :style="{ maxWidth: wide ? '1140px' : '760px' }">
                <AppLogo />
                <div class="d-flex align-items-center gap-2 gap-sm-3">
                    <span v-if="mode === 'nominate'" class="small text-muted d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-megaphone"></i> {{ t('layout.nominations') }}</span>
                    <span v-else-if="mode === 'codes'" class="small text-muted d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-shield-lock"></i> {{ t('layout.secretBallot') }}</span>
                </div>
            </div>
        </header>
        <main class="container py-4 py-md-5" :style="{ maxWidth: wide ? '1140px' : '760px' }">
            <slot />
        </main>
        <footer class="text-center small text-muted pb-4">
            {{ t('common.poweredBy') }} <NuxtLink to="/" class="fw-bold">Agudatechy</NuxtLink>
        </footer>
    </div>
</template>

<script setup lang="ts">
const route = useRoute();
const wide = computed(() => Boolean(route.meta.wide));
const mode = useBallotMode();
</script>

<style scoped>
.ballot-shell { min-height: 100vh; display: flex; flex-direction: column; background: var(--bg); }
.ballot-shell > main { flex: 1; }
.ballot-header { position: sticky; top: 0; z-index: 20; padding: .8rem 0; background: var(--header-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
</style>
