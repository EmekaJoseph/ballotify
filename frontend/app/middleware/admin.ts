import { useAuthStore } from '~/stores/auth';

/** Platform-owner pages. The API enforces this too; this just avoids a dead page. */
export default defineNuxtRouteMiddleware(async () => {
    const auth = useAuthStore();
    if (!auth.user) await auth.fetchUser();
    if (!auth.user?.is_admin) return navigateTo('/dashboard');
});
