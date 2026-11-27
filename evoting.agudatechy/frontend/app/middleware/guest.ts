import { useAuthStore } from '~/stores/auth';

export default defineNuxtRouteMiddleware(() => {
    const auth = useAuthStore();
    auth.initAuth();
    if (auth.isAuthenticated) {
        return navigateTo('/dashboard');
    }
});
