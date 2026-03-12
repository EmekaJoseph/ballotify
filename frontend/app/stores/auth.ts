import { defineStore } from 'pinia';
import { adminApi } from '../api/endpoints/admin';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as any | null,
        token: null as string | null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        setToken(token: string) {
            this.token = token;
            if (typeof window !== 'undefined') {
                localStorage.setItem('auth_token', token);
            }
        },
        setUser(user: any) {
            this.user = user;
        },
        async fetchUser() {
            try {
                if (!this.token && typeof window !== 'undefined') {
                    this.token = localStorage.getItem('auth_token');
                }
                if (!this.token) return;

                const response = await adminApi.getMe();
                this.user = response.data;
            } catch (error) {
                this.logout();
            }
        },
        async login(credentials: any) {
            const response = await adminApi.login(credentials);
            this.setToken(response.data.token);
            this.setUser(response.data.user);
        },
        async register(data: any) {
            const response = await adminApi.register(data);
            this.setToken(response.data.token);
            this.setUser(response.data.user);
        },
        logout() {
            this.user = null;
            this.token = null;
            if (typeof window !== 'undefined') {
                localStorage.removeItem('auth_token');
            }
            adminApi.logout().catch(() => {});
        },
        initAuth() {
            if (typeof window !== 'undefined') {
                const token = localStorage.getItem('auth_token');
                if (token) {
                    this.token = token;
                }
            }
        }
    },
});
