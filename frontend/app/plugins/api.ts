import api, { setUnauthorizedHandler } from '~/api';
import { useAuthStore } from '~/stores/auth';

/** Wires the axios instance to runtime config and handles expired sessions. */
export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig();
    let base = String(config.public.apiBase).trim().replace(/\/+$/, '');
    // "api.example.com/api" (no scheme) would be read as a path on this site
    // (http://this-site/api.example.com/api/...): treat a bare host name as https.
    if (/^[a-z0-9-]+(\.[a-z0-9-]+)+(:\d+)?(\/|$)/i.test(base)) {
        console.warn(`NUXT_PUBLIC_API_BASE "${base}" has no http:// or https://; using https://${base}`);
        base = `https://${base}`;
    }
    api.defaults.baseURL = base;

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
