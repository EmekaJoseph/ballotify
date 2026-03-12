export default defineNuxtConfig({
  ssr: false,
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  future: {
    compatibilityVersion: 4,
  },

  css: [
    'bootstrap/dist/css/bootstrap.min.css',
    'bootstrap-icons/font/bootstrap-icons.css',
    '~/assets/css/base.css'
  ],
  modules: ['@pinia/nuxt'],


  plugins: [],

  // app: {
  //   head: {
  //     link: [
  //       { rel: 'stylesheet', href: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' },
  //       { rel: 'stylesheet', href: 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css' }
  //     ]
  //   }
  // },
  runtimeConfig: {
    public: {
      pusherKey: 'your-key',
      pusherCluster: 'mt1',
    }
  }
})
