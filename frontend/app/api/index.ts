import axios from 'axios';
import { ballotLang } from '~/utils/i18n';

export const TOKEN_KEY = 'auth_token';

/** Safe localStorage access (it can throw in private mode / blocked storage). */
export const tokenStorage = {
    get(): string | null {
        try { return localStorage.getItem(TOKEN_KEY); } catch { return null; }
    },
    set(token: string) {
        try { localStorage.setItem(TOKEN_KEY, token); } catch { /* ignore */ }
    },
    clear() {
        try { localStorage.removeItem(TOKEN_KEY); } catch { /* ignore */ }
    },
};

/**
 * Shared axios instance. `baseURL` is set from runtime config by plugins/api.ts,
 * so the API location is configured with NUXT_PUBLIC_API_BASE, not hard-coded.
 */
const api = axios.create({
    headers: { Accept: 'application/json' },
    timeout: 30000,
});

export type SignOutReason = 'expired' | 'suspended';
let onUnauthorized: ((reason: SignOutReason) => void) | null = null;

/** Registered by plugins/api.ts to send the user to /login when their session ends. */
export function setUnauthorizedHandler(handler: (reason: SignOutReason) => void) {
    onUnauthorized = handler;
}

api.interceptors.request.use((config) => {
    const token = tokenStorage.get();
    if (token) config.headers.Authorization = `Bearer ${token}`;
    // Voter-facing messages from the API come back in the voting page's language.
    if (config.url?.startsWith('/public/') && ballotLang.value !== 'en') config.headers['X-Locale'] = ballotLang.value;
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const url: string = error?.config?.url ?? '';
        const isAuthCall = url.startsWith('/auth/');
        const status = error?.response?.status;
        if (status === 401 && !isAuthCall && tokenStorage.get()) {
            tokenStorage.clear();
            onUnauthorized?.('expired');
        } else if (status === 403 && error?.response?.data?.suspended && tokenStorage.get()) {
            tokenStorage.clear();
            onUnauthorized?.('suspended');
        }
        return Promise.reject(error);
    },
);

export default api;
