import { getQuery } from 'h3'
import type { H3Event } from 'h3'
import type { UserProfile } from '~~/types/sdk'

export const getSessionUser = (event?: H3Event): UserProfile => {
  let id = 'guest'
  let username = 'Guest'

  if (event) {
    const query = getQuery(event)
    if (query.user) {
      username = String(query.user)
      id = query.userId ? String(query.userId) : username
    }
  }

  // quocquoc can only be a test override when explicitly configured by env or authenticated session
  // @ts-expect-error: process is global in node runtime
  const testUser = typeof process !== 'undefined' ? process.env?.TEST_USER || process.env?.NUXT_TEST_USER : ''
  const envOverride = testUser || ''
  if (envOverride === 'quocquoc') {
    id = 'greenjade'
    username = 'quocquoc'
  }

  return {
    id,
    username,
    avatar: 'https://avatars.githubusercontent.com/u/739984?v=4',
  }
}
