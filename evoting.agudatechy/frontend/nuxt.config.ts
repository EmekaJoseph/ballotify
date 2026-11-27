// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // E-voting by Agudatechy runs as a client-side SPA that talks to the Laravel API.
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
      htmlAttrs: { lang: 'en', 'data-theme': 'light', 'data-bs-theme': 'light' },
      title: 'E-voting by Agudatechy',
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1, viewport-fit=cover' },
        { name: 'description', content: 'Secret ballot elections online by Agudatechy: one-time voter codes, a clean ballot on any device, and certified results.' },
        { name: 'theme-color', content: '#058580' },
      ],
      link: [
        { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon-32x32.png' },
        { rel: 'icon', href: '/favicon.ico', sizes: '48x48' },
        { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap',
        },
        // Plus Jakarta Sans has no Hausa hooked letters (ƙ ɗ ɓ ƴ): a tiny Noto Sans subset with only those
        // glyphs fills them in, so Hausa voting pages don't fall back to a mismatched serif font.
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700;800&display=swap&text=%C6%81%C9%93%C6%8A%C9%97%C6%98%C6%99%C6%B3%C6%B4',
        },
      ],
    },
  },

  // Every value can be overridden with NUXT_PUBLIC_<NAME> (e.g. NUXT_PUBLIC_API_BASE, NUXT_PUBLIC_REVERB_KEY).
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8883/api',
      // Live results through Pusher (hosted; nothing to run on the server). Set the key and
      // cluster from the Pusher dashboard; the backend needs BROADCAST_CONNECTION=pusher.
      // Takes priority over Reverb. Both empty = results refresh every 15 s instead.
      pusherKey: '',
      pusherCluster: '',
      // Or live results via Laravel Reverb (self-hosted). Leave reverbKey empty to not use it.
      reverbKey: '',
      reverbHost: 'localhost',
      reverbPort: 8080,
      reverbScheme: 'http',
      // Shown on the Privacy / Terms pages.
      companyName: 'E-voting by Agudatechy',
      contactEmail: '',
      // Organiser support on WhatsApp, international format without + (e.g. 2348012345678). Empty = no button.
      supportWhatsapp: '',
      // YouTube or Vimeo link to a short tour, shown on the Help centre. Empty = hidden.
      helpVideoUrl: '',
      // Cloudflare Turnstile site key (bot protection). Empty = off.
      turnstileSiteKey: '',
    },
  },
})
