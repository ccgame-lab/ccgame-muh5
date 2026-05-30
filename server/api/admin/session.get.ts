export default defineEventHandler(async (event) => {
  const token = getCookie(event, 'muh5_admin_session')

  if (!token) {
    throw createError({ statusCode: 401, statusMessage: 'Not authenticated' })
  }

  const storage = useStorage('admin')
  const session = await storage.getItem(`session:${token}`)

  if (!session || (session as any).expiresAt < Date.now()) {
    // Clean up expired session
    if (session) await storage.removeItem(`session:${token}`)
    deleteCookie(event, 'muh5_admin_session')
    throw createError({ statusCode: 401, statusMessage: 'Session expired' })
  }

  return { authenticated: true }
})
