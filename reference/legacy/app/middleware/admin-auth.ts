export default defineNuxtRouteMiddleware(async () => {
  try {
    await $fetch('/api/admin/session')
  }
  catch {
    return navigateTo('/admin/login')
  }
})
