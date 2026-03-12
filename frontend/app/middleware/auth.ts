import { useAuthStore } from '../stores/auth';

export default defineNuxtRouteMiddleware((to, from) => {
    // Only run on client side (or ensure token logic works dynamically on SSR)
    if (import.meta.client) {
        const authStore = useAuthStore();
        authStore.initAuth();
        
        if (!authStore.isAuthenticated) {
            return navigateTo('/login');
        }
    }
});
