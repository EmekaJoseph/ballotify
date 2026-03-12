import { useAuthStore } from '../stores/auth';

export default defineNuxtRouteMiddleware((to, from) => {
    if (import.meta.client) {
        const authStore = useAuthStore();
        authStore.initAuth();
        
        if (authStore.isAuthenticated) {
            return navigateTo('/dashboard');
        }
    }
});
