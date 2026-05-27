// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },

  typescript: {
    typeCheck: true,
  },

  modules: ['@nuxt/eslint'],
  eslint: {
    config: {
      stylistic: true
    }
  }
})