<template>
    <div class="err-wrap">
        <div class="text-center" style="max-width: 440px">
            <AppLogo class="mb-5" />
            <div class="code text-gradient">{{ error?.statusCode || 500 }}</div>
            <h1 class="h3 fw-800 mb-2">{{ is404 ? 'Page not found' : 'Something went wrong' }}</h1>
            <p class="text-muted mb-4">
                {{ is404 ? 'The page you\'re looking for doesn\'t exist or has moved.' : 'An unexpected error occurred. Please try again.' }}
            </p>
            <button class="btn btn-primary" @click="clearError({ redirect: '/' })">Back to home</button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app';

const props = defineProps<{ error: NuxtError | null }>();
const is404 = computed(() => props.error?.statusCode === 404);
useHead({ title: computed(() => (is404.value ? 'Not found · Ballotify' : 'Error · Ballotify')) });
</script>

<style scoped>
.err-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; background: var(--bg); }
.code { font-size: 5.5rem; font-weight: 800; letter-spacing: -.05em; line-height: 1; margin-bottom: 1rem; }
</style>
