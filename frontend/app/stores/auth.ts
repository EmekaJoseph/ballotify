import { defineStore } from 'pinia';
import { adminApi } from '~/api/endpoints/admin';
import { tokenStorage } from '~/api';
import type { User } from '~/types';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null,
        token: null as string | null,
        initialised: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        firstName: (state) => state.user?.name?.trim().split(/\s+/)[0] ?? '',
    },

    actions: {
        /** Load the token saved from a previous session (idempotent). */
        initAuth() {
            if (this.initialised) return;
            this.token = tokenStorage.get();
            this.initialised = true;
        },

        setSession(token: string, user: User) {
            this.token = token;
            this.user = user;
            this.initialised = true;
            tokenStorage.set(token);
        },

        async fetchUser() {
            this.initAuth();
            if (!this.token) return;
            try {
                const { data } = await adminApi.getMe();
                this.user = data;
            } catch {
                // An invalid token is cleared by the API 401 interceptor.
            }
        },

        async login(credentials: { email: string; password: string }) {
            const { data } = await adminApi.login(credentials);
            this.setSession(data.token, data.user);
        },

        async register(payload: { name: string; email: string; password: string }) {
            const { data } = await adminApi.register(payload);
            this.setSession(data.token, data.user);
        },

        /** Revoke the token on the server first (it must still be attached), then clear locally. */
        async logout() {
            if (this.token) {
                try { await adminApi.logout(); } catch { /* already invalid: fine */ }
            }
            this.clearSession();
        },

        clearSession() {
            this.user = null;
            this.token = null;
            tokenStorage.clear();
        },
    },
});
