export default defineEventHandler(async (event) => {
  const token = getCookie(event, 'muh5_admin_session')

  if (token) {
    const storage = useStorage('admin')
    await storage.removeItem(`session:${token}`)
  }

  deleteCookie(event, 'muh5_admin_session')
  return { ok: true }
})
