// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({

  modules: ['@nuxt/eslint', '@nuxt/ui'],
  devtools: { enabled: false },

  app: {
    head: {
      link: [
        { rel: 'preconnect', href: 'https://cdn.ccgame.org' },
        { rel: 'dns-prefetch', href: 'https://cdn.ccgame.org' },
        { rel: 'preconnect', href: 'https://muh5-ws.ccgame.org' },
        { rel: 'dns-prefetch', href: 'https://muh5-ws.ccgame.org' },
      ],
    },
  },
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
    '/play': {
      headers: {
        'Cache-Control': 'no-store, must-revalidate',
      },
    },
    '/play/**': {
      headers: {
        'Cache-Control': 'no-store, must-revalidate',
      },
    },
    '/muh5-client/h5/ccgame-entrance.js': {
      headers: {
        'Cache-Control': 'no-cache, must-revalidate',
      },
    },
    // Egret bundles are not content-hashed; avoid long immutable cache (stale JS trap).
    '/muh5-client/h5/**': {
      headers: {
        'Cache-Control': 'public, max-age=86400, must-revalidate',
      },
    },
    '/muh5-client/config.js': {
      headers: {
        'Cache-Control': 'no-cache, must-revalidate',
      },
    },
    '/muh5-client/manifest.json': {
      headers: {
        'Cache-Control': 'no-cache, must-revalidate',
      },
    },
    '/muh5-client/*.json': {
      headers: {
        'Cache-Control': 'no-cache, must-revalidate',
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
  vite: {
    server: {
      allowedHosts: ['muh5.ccgame.org', 'ccgame.org', 'www.ccgame.org'],
    },
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
