export default defineEventHandler(async (event) => {
  // Verify owner session
  const token = getCookie(event, 'muh5_admin_session')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Not authenticated' })

  const storage = useStorage('admin')
  const session = await storage.getItem(`session:${token}`) as any
  if (!session || session.role !== 'owner') {
    throw createError({ statusCode: 403, statusMessage: 'Owner only' })
  }

  const { name, password } = await readBody<{ name: string, password: string }>(event)
  if (!name || !password) {
    throw createError({ statusCode: 400, statusMessage: 'Name and password required' })
  }

  const accounts = (await storage.getItem('accounts')) as Record<string, string> || {}
  accounts[name] = password
  await storage.setItem('accounts', accounts)

  return { ok: true }
})
