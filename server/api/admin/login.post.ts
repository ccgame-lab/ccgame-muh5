import { createHmac, randomBytes } from 'node:crypto'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const { password } = await readBody<{ password: string }>(event)

  if (!config.adminPassword) {
    throw createError({ statusCode: 503, statusMessage: 'Admin not configured' })
  }

  if (!password || password !== config.adminPassword) {
    throw createError({ statusCode: 401, statusMessage: 'Invalid password' })
  }

  // Create a signed session token
  const secret = config.adminSessionSecret || config.adminPassword
  const payload = `admin:${Date.now()}`
  const token = createHmac('sha256', secret).update(payload).digest('hex')

  // Set cookie (httpOnly, 24h)
  setCookie(event, 'muh5_admin_session', token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: 60 * 60 * 24, // 24 hours
    path: '/',
  })

  // Store token server-side (in-memory for simplicity)
  const storage = useStorage('admin')
  await storage.setItem(`session:${token}`, {
    createdAt: Date.now(),
    expiresAt: Date.now() + 60 * 60 * 24 * 1000,
  })

  return { ok: true }
})
