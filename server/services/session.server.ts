import { getQuery } from 'h3'
import type { H3Event } from 'h3'
import { verifyLaunchToken } from './launch-token.server'

export type Muh5Session = {
  authMode: 'guest' | 'greenjade'
  source: 'signed_launch' | 'guest' | 'unsigned_legacy'
  trusted: boolean
  player: {
    id: string
    username?: string
    displayName: string
  }
  server: {
    id: number
    key: 's1'
    name: string
    srvaddr: string
    srvport: string
  }
}

export const getSessionUser = (event?: H3Event): Muh5Session => {
  // Default fallback (Guest / Sealed)
  const defaultSession: Muh5Session = {
    authMode: 'guest',
    source: 'guest',
    trusted: false,
    player: {
      id: 'guest',
      username: 'Guest',
      displayName: 'Gamer Khách',
    },
    server: {
      id: 1,
      key: 's1',
      name: 'Server S1',
      srvaddr: 'muh5-ws.ccgame.org/s1/',
      srvport: '443',
    },
  }

  if (!event) return defaultSession

  const query = getQuery(event)

  // 1. Try parsing signed launch token
  if (query.launch) {
    const verifiedPayload = verifyLaunchToken(String(query.launch))
    if (verifiedPayload) {
      return {
        authMode: verifiedPayload.authMode,
        source: 'signed_launch',
        trusted: true,
        player: {
          id: verifiedPayload.player.id,
          username: verifiedPayload.player.username,
          displayName: verifiedPayload.player.displayName,
        },
        server: {
          id: verifiedPayload.server.id,
          key: verifiedPayload.server.key,
          name: verifiedPayload.server.name,
          srvaddr: verifiedPayload.server.srvaddr,
          srvport: verifiedPayload.server.srvport,
        },
      }
    }
  }

  // 2. Try parsing unsigned legacy params if NUXT_ALLOW_UNSIGNED_LAUNCH is enabled
  let allowUnsigned = false
  try {
    // @ts-expect-error: process is global in node/nitro environment
    allowUnsigned = process.env.NUXT_ALLOW_UNSIGNED_LAUNCH === 'true'
  }
  catch {
    // ignore
  }

  if (allowUnsigned && query.user) {
    const username = String(query.user)
    const userId = query.userId ? String(query.userId) : username
    const authMode = username === 'quocquoc' ? 'greenjade' : 'guest'

    return {
      authMode,
      source: 'unsigned_legacy',
      trusted: false,
      player: {
        id: userId,
        username: username,
        displayName: username,
      },
      server: {
        id: 1,
        key: 's1',
        name: 'Server S1',
        srvaddr: 'muh5-ws.ccgame.org/s1/',
        srvport: '443',
      },
    }
  }

  // 3. Environment/dev overrides (strictly override only when configured by env)
  let envOverride = ''
  try {
    // @ts-expect-error: process is global in node/nitro environment
    envOverride = process.env.TEST_USER || process.env.NUXT_TEST_USER || ''
  }
  catch {
    // ignore
  }

  if (envOverride === 'quocquoc') {
    return {
      authMode: 'greenjade',
      source: 'unsigned_legacy',
      trusted: false,
      player: {
        id: 'greenjade',
        username: 'quocquoc',
        displayName: 'quocquoc (Dev Override)',
      },
      server: {
        id: 1,
        key: 's1',
        name: 'Server S1',
        srvaddr: 'muh5-ws.ccgame.org/s1/',
        srvport: '443',
      },
    }
  }

  return defaultSession
}
