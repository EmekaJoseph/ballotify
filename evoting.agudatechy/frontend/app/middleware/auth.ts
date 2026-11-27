import { useAuthStore } from '~/stores/auth';

export default defineNuxtRouteMiddleware((to) => {
    const auth = useAuthStore();
    auth.initAuth();
    if (!auth.isAuthenticated) {
        return navigateTo({ path: '/login', query: { redirect: to.fullPath } });
    }
});
