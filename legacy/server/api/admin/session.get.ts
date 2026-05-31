export default defineEventHandler(async (event) => {
  const token = getCookie(event, 'muh5_admin_session')

  if (!token) {
    throw createError({ statusCode: 401, statusMessage: 'Not authenticated' })
  }

  const storage = useStorage('admin')
  const session = await storage.getItem(`session:${token}`) as {
    username: string
    role: 'owner' | 'admin'
    expiresAt: number
  } | null

  if (!session || session.expiresAt < Date.now()) {
    if (session) await storage.removeItem(`session:${token}`)
    deleteCookie(event, 'muh5_admin_session')
    throw createError({ statusCode: 401, statusMessage: 'Session expired' })
  }

  return { authenticated: true, username: session.username, role: session.role }
})
