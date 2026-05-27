// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({

  modules: ['@nuxt/eslint', '@nuxt/ui'],
  devtools: { enabled: false },
  css: ['~/assets/css/main.css'],

  // Server-only runtime config. Never inline DB creds into client bundle.
  // Values come from environment (NUXT_MUH5_PORTAL_DB_*, NUXT_MUH5_GAME_DB_*).
  // Empty defaults => endpoints fall back to sealed/empty state safely.
  runtimeConfig: {
    muh5PortalDbHost: '',
    muh5PortalDbPort: '3306',
    muh5PortalDbName: 'muh5_ccgame',
    muh5PortalDbUser: '',
    muh5PortalDbPassword: '',
    muh5GameDbHost: '',
    muh5GameDbPort: '3306',
    muh5GameDbName: 'actor_s1',
    muh5GameDbUser: '',
    muh5GameDbPassword: '',
    public: {
      ccgamePortalUrl: process.env.NUXT_PUBLIC_CCGAME_PORTAL_URL || 'https://ccgame.org',
    },
  },

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
