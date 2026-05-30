import { createHmac } from 'node:crypto'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const { username, password } = await readBody<{ username: string, password: string }>(event)

  if (!config.adminPassword) {
    throw createError({ statusCode: 503, statusMessage: 'Admin not configured' })
  }

  if (!username || !password) {
    throw createError({ statusCode: 400, statusMessage: 'Missing credentials' })
  }

  let role: 'owner' | 'admin' | null = null

  if (username === 'Owner') {
    // Owner uses env password
    if (password === config.adminPassword) {
      role = 'owner'
    }
  }
  else if (username === 'Admin') {
    // Admin accounts stored in server storage
    const storage = useStorage('admin')
    const admins = (await storage.getItem('accounts')) as Record<string, string> | null
    if (admins && admins[username] === password) {
      role = 'admin'
    }
    // Fallback: if no admin accounts exist, use same env password
    if (!admins && password === config.adminPassword) {
      role = 'admin'
    }
  }

  if (!role) {
    throw createError({ statusCode: 401, statusMessage: 'Invalid password' })
  }

  // Create session token
  const secret = config.adminSessionSecret || config.adminPassword
  const payload = `${role}:${Date.now()}`
  const token = createHmac('sha256', secret).update(payload).digest('hex')

  setCookie(event, 'muh5_admin_session', token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: 60 * 60 * 24,
    path: '/',
  })

  const storage = useStorage('admin')
  await storage.setItem(`session:${token}`, {
    username,
    role,
    createdAt: Date.now(),
    expiresAt: Date.now() + 60 * 60 * 24 * 1000,
  })

  return { ok: true, role }
})
