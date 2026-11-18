// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // Ballotify runs as a client-side SPA that talks to the Laravel API.
  ssr: false,
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: ['@pinia/nuxt'],

  css: [
    'bootstrap/dist/css/bootstrap.min.css',
    'bootstrap-icons/font/bootstrap-icons.css',
    '~/assets/css/main.css',
  ],

  app: {
    pageTransition: { name: 'page', mode: 'out-in' },
    head: {
      htmlAttrs: { lang: 'en' },
      title: 'Ballotify · Online voting made simple',
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1, viewport-fit=cover' },
        { name: 'description', content: 'Run secure elections, awards and polls online. One-time voter codes, a clean ballot on any device and live results.' },
        { name: 'theme-color', content: '#0e7c77' },
      ],
      link: [
        { rel: 'icon', type: 'image/png', href: '/ballotify-logo.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap',
        },
      ],
    },
  },

  // Override at build time with NUXT_PUBLIC_API_BASE, NUXT_PUBLIC_PUSHER_KEY, NUXT_PUBLIC_PUSHER_CLUSTER.
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8883/api',
      pusherKey: '',
      pusherCluster: 'mt1',
    },
  },
})
