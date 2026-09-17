export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: false },
  modules: ['@nuxtjs/tailwindcss'],

  // Explicitly disable dev mode
  dev: false,

  // Disable build analysis in production
  builder: 'vite',

  nitro: {
    minify: true,
  },

  // Disable experimental features that cause build metadata requests
  experimental: {
    payloadExtraction: false,
    renderJsonPayloads: false,
  },

  runtimeConfig: {
    // Server-only runtime config (not exposed to client)
    apiBaseSSR: process.env.NUXT_API_BASE_SSR || 'http://laravel.test/api/v1',

    // Public runtime config (exposed to client)
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost/api/v1',
      backendUrl: process.env.NUXT_PUBLIC_BACKEND_URL || 'http://localhost'
    }
  },

  app: {
    buildAssetsDir: '/_nuxt/',
    head: {
      title: 'WEBNOVA - Ghanaian Academic and Research Network',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Ghanaian Academic and Research Network - Connecting Ghana\'s education sector' }
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }
      ]
    }
  },

  css: ['~/assets/css/main.css'],
})