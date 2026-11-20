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
      script: [
        {
          // Set the theme before first paint so dark mode never flashes white.
          tagPosition: 'head',
          innerHTML: "(function(){try{var t=localStorage.getItem('theme')||'system';var d=t==='dark'||(t==='system'&&window.matchMedia('(prefers-color-scheme: dark)').matches);var e=document.documentElement;e.setAttribute('data-theme',d?'dark':'light');e.setAttribute('data-bs-theme',d?'dark':'light')}catch(x){}})()",
        },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
        { rel: 'icon', href: '/favicon.ico', sizes: '48x48' },
        { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap',
        },
      ],
    },
  },

  // Every value can be overridden with NUXT_PUBLIC_<NAME> (e.g. NUXT_PUBLIC_API_BASE, NUXT_PUBLIC_REVERB_KEY).
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8883/api',
      // Live results via Laravel Reverb. Leave reverbKey empty to refresh results every 15 s instead.
      reverbKey: '',
      reverbHost: 'localhost',
      reverbPort: 8080,
      reverbScheme: 'http',
      // Shown on the Privacy / Terms pages.
      companyName: 'Ballotify',
      contactEmail: '',
      // Cloudflare Turnstile site key (bot protection). Empty = off.
      turnstileSiteKey: '',
    },
  },
})
