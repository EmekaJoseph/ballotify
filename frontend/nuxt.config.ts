// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  pages: true,
  modules: ['@nuxtjs/tailwindcss'],
  runtimeConfig: {
    public: {
      apiBase: '/api'
    }
  },
  nitro: {
    devProxy: {
      '/api': {
        target: 'http://localhost:8883/api',
        changeOrigin: true,
        prependPath: false
      }
    }
  }
})
