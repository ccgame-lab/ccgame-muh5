// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({

  modules: ['@nuxt/eslint', '@nuxt/ui'],
  devtools: { enabled: false },
  css: ['~/assets/css/main.css'],

  routeRules: {
    '/**': {
      headers: {
        'Content-Security-Policy': 'frame-ancestors \'self\' https://ccgame.org https://www.ccgame.org',
      },
    },
    '/muh5-client/index.html': {
      headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
      },
    },
  },
  compatibilityDate: '2025-07-15',
  nitro: {
    preset: 'bun',
  },

  typescript: {
    typeCheck: true,
  },
  eslint: {
    config: {
      stylistic: true,
    },
  },
})
