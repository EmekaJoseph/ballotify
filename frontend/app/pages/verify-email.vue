<template>
    <div class="fade-up text-center">
        <template v-if="state === 'working'">
            <div class="spinner-border text-brand mb-3"></div>
            <h1 class="h4 fw-800">Confirming your email…</h1>
        </template>
        <template v-else-if="state === 'done'">
            <h1 class="h2 fw-800 mb-2">Email confirmed</h1>
            <p class="text-muted mb-4">Thanks! You can now send voting codes to your voters.</p>
            <NuxtLink :to="auth.isAuthenticated ? '/dashboard' : '/login'" class="btn btn-primary btn-lg w-100">
                {{ auth.isAuthenticated ? 'Go to dashboard' : 'Sign in' }}
            </NuxtLink>
        </template>
        <template v-else>
            <h1 class="h2 fw-800 mb-2">Link not valid</h1>
            <p class="text-muted mb-4">This confirmation link is invalid or has expired (links last 24 hours). Sign in and request a new one from the dashboard.</p>
            <NuxtLink :to="auth.isAuthenticated ? '/dashboard' : '/login'" class="btn btn-primary btn-lg w-100">
                {{ auth.isAuthenticated ? 'Go to dashboard' : 'Sign in' }}
            </NuxtLink>
        </template>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';

definePageMeta({ layout: 'auth' });
useHead({ title: 'Confirm email · Ballotify' });

const route = useRoute();
const auth = useAuthStore();
const state = ref<'working' | 'done' | 'failed'>('working');

onMounted(async () => {
    const link = typeof route.query.link === 'string' ? route.query.link : '';
    // Only ever call our own API's verification endpoint.
    if (!/^\/api\/auth\/verify-email\/\d+\/[0-9a-f]+\?/.test(link)) {
        state.value = 'failed';
        return;
    }
    try {
        await adminApi.verifyEmail(link);
        state.value = 'done';
        if (auth.isAuthenticated) await auth.fetchUser();
    } catch {
        state.value = 'failed';
    }
});
</script>

<style scoped>
.icon-tile.danger { background: var(--danger-50); color: var(--danger); }
</style>
