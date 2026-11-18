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
        if (reason === 'suspended') {
            navigateTo({ path: '/login', query: { suspended: '1' } });
        } else if (route.path.startsWith('/dashboard')) {
            navigateTo({ path: '/login', query: { redirect: route.fullPath, expired: '1' } });
        }
    });
});
