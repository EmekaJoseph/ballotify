import api, { setUnauthorizedHandler } from '~/api';
import { useAuthStore } from '~/stores/auth';

/** Wires the axios instance to runtime config and handles expired sessions. */
export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig();
    api.defaults.baseURL = String(config.public.apiBase).replace(/\/+$/, '');

    const auth = useAuthStore();
    auth.initAuth();

    setUnauthorizedHandler((reason) => {
        auth.clearSession();
        const route = useRoute();
        // Any page that needs a login (dashboard, receipts, reports): straight to /login.
        const mw = ([] as unknown[]).concat(route.meta.middleware ?? []);
        const needsLogin = route.path.startsWith('/dashboard') || mw.includes('auth') || mw.includes('admin');
        if (reason === 'suspended') {
            navigateTo({ path: '/login', query: { suspended: '1' } }, { replace: true });
        } else if (needsLogin) {
            navigateTo({ path: '/login', query: { redirect: route.fullPath, expired: '1' } }, { replace: true });
        }
    });
});
