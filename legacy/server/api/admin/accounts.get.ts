export default defineEventHandler(async (event) => {
  // Verify owner session
  const token = getCookie(event, 'muh5_admin_session')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Not authenticated' })

  const storage = useStorage('admin')
  const session = await storage.getItem(`session:${token}`) as any
  if (!session || session.role !== 'owner') {
    throw createError({ statusCode: 403, statusMessage: 'Owner only' })
  }

  const accounts = (await storage.getItem('accounts')) as Record<string, string> | null
  // Return list without passwords
  const list = accounts
    ? Object.keys(accounts).map(name => ({ name }))
    : []

  return { accounts: list }
})
