<template>
    <div class="ballot-shell">
        <header class="ballot-header">
            <div class="container d-flex align-items-center justify-content-between" :style="{ maxWidth: wide ? '1140px' : '760px' }">
                <AppLogo />
                <div class="d-flex align-items-center gap-2 gap-sm-3">
                    <span v-if="mode === 'paid'" class="small text-muted d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-lock"></i> {{ t('layout.securePayments') }}</span>
                    <span v-else-if="mode === 'nominate'" class="small text-muted d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-megaphone"></i> {{ t('layout.nominations') }}</span>
                    <span v-else-if="mode === 'codes'" class="small text-muted d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-shield-lock"></i> {{ t('layout.secretBallot') }}</span>
                    <LangSwitcher />
                    <ThemeToggle />
                </div>
            </div>
        </header>
        <main class="container py-4 py-md-5" :style="{ maxWidth: wide ? '1140px' : '760px' }">
            <slot />
        </main>
        <!-- Shown on Free events (and demo pages); Pro events hide it. -->
        <footer v-if="branding" class="text-center small text-muted pb-4">
            {{ t('common.poweredBy') }} <NuxtLink to="/" class="fw-bold">Agudatechy</NuxtLink>
        </footer>
    </div>
</template>

<script setup lang="ts">
const route = useRoute();
const wide = computed(() => Boolean(route.meta.wide));
const branding = useBallotBranding();
const mode = useBallotMode();

// Public pages speak the voter's language; the rest of the app stays English.
enterBallotPages();
onBeforeUnmount(leaveBallotPages);
useHead({ htmlAttrs: { lang: ballotLang } });
</script>

<style scoped>
.ballot-shell { min-height: 100vh; display: flex; flex-direction: column; background: var(--bg); }
.ballot-shell > main { flex: 1; }
.ballot-header { position: sticky; top: 0; z-index: 20; padding: .8rem 0; background: var(--header-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
</style>
