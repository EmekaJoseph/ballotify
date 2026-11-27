import axios from 'axios';

export const TOKEN_KEY = 'auth_token';

/** Safe localStorage access (it can throw in private mode / blocked storage). */
export const tokenStorage = {
    get(): string | null {
        try { return localStorage.getItem(TOKEN_KEY); } catch { return null; }
    },
    set(token: string) {
        sessionToken = token;
        try { localStorage.setItem(TOKEN_KEY, token); } catch { /* ignore */ }
    },
    clear() {
        sessionToken = null;
        try { localStorage.removeItem(TOKEN_KEY); } catch { /* ignore */ }
    },
};
/** In-memory copy, so a session still works (and still ends) when localStorage is blocked. */
let sessionToken: string | null = null;

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
    const token = tokenStorage.get() ?? sessionToken;
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

/** Sign-in calls where a 401 means "wrong details", not "your session ended". */
const CREDENTIAL_CALLS = ['/auth/login', '/auth/register', '/auth/two-factor', '/auth/forgot-password', '/auth/reset-password'];

/** Several requests can fail together: sign out once, not once per request. */
let signingOut = false;
function signOut(reason: SignOutReason) {
    tokenStorage.clear();
    if (signingOut) return;
    signingOut = true;
    try { onUnauthorized?.(reason); } finally { setTimeout(() => { signingOut = false; }, 1000); }
}

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const url: string = error?.config?.url ?? '';
        const status = error?.response?.status;
        // Only requests sent with a session count (the token may live only in memory if storage is blocked).
        const hadSession = !!error?.config?.headers?.Authorization;
        if (status === 401 && hadSession && !CREDENTIAL_CALLS.includes(url)) {
            signOut('expired');
        } else if (status === 403 && error?.response?.data?.suspended && hadSession) {
            signOut('suspended');
        }
        return Promise.reject(error);
    },
);

export default api;
