import { getQuery } from 'h3'
import type { H3Event } from 'h3'
import { verifyLaunchToken } from './launch-token.server'

export type Muh5LaunchSource = 'signed_launch' | 'unsigned_legacy' | 'invalid_launch' | 'sealed'

export type Muh5Session = {
  authMode: 'guest' | 'greenjade'
  source: Muh5LaunchSource
  trusted: boolean
  playAllowed: boolean
  player: {
    id: string
    username?: string
    spverify?: string
    displayName: string
    suggestedCharacterName?: string
  }
  server: {
    id: number
    key: 's1'
    name: string
    srvaddr: string
    srvport: string
    srvpath?: string
  }
}

const sealedSession = (source: Muh5LaunchSource): Muh5Session => ({
  authMode: 'guest',
  source,
  trusted: false,
  playAllowed: false,
  player: {
    id: 'guest',
    username: 'Guest',
    displayName: 'Gamer Khách',
  },
  server: {
    id: 1,
    key: 's1',
    name: 'S1',
    srvaddr: 'muh5-ws.ccgame.org',
    srvport: '443',
    srvpath: '/s1/',
  },
})

export const normalizeSrvAddr = (addr: string): string => {
  if (!addr) return ''

  let host = addr
  try {
    host = decodeURIComponent(addr)
  }
  catch {
    // Fallback if decoding fails
  }

  host = host.replace(/^(wss?|https?):\/\//i, '')

  const slashIdx = host.indexOf('/')
  if (slashIdx !== -1) {
    host = host.substring(0, slashIdx)
  }

  const colonIdx = host.indexOf(':')
  if (colonIdx !== -1) {
    host = host.substring(0, colonIdx)
  }

  return host.trim()
}

export const extractSrvPath = (addr: string): string => {
  if (!addr) return ''

  let host = addr
  try {
    host = decodeURIComponent(addr)
  }
  catch {
    // Fallback if decoding fails
  }

  host = host.replace(/^(wss?|https?):\/\//i, '')
  const slashIdx = host.indexOf('/')
  if (slashIdx !== -1) {
    return host.substring(slashIdx)
  }
  return ''
}

const allowUnsignedLaunch = (): boolean => {
  try {
    return process.env.NUXT_ALLOW_UNSIGNED_LAUNCH === 'true'
  }
  catch {
    return false
  }
}

export const getSessionUser = (event?: H3Event): Muh5Session => {
  if (!event) {
    return sealedSession('sealed')
  }

  const query = getQuery(event)

  if (query.launch) {
    const verifiedPayload = verifyLaunchToken(String(query.launch))
    if (verifiedPayload) {
      const gameUsername = verifiedPayload.player.username?.trim()
      const spverify = verifiedPayload.player.spverify?.trim()
      if (!gameUsername || !spverify) {
        return sealedSession('invalid_launch')
      }

      return {
        authMode: verifiedPayload.authMode,
        source: 'signed_launch',
        trusted: true,
        playAllowed: true,
        player: {
          id: verifiedPayload.player.id,
          username: gameUsername,
          spverify,
          displayName: verifiedPayload.player.displayName,
          suggestedCharacterName: verifiedPayload.player.suggestedCharacterName,
        },
        server: {
          id: verifiedPayload.server.id,
          key: verifiedPayload.server.key,
          name: verifiedPayload.server.name,
          srvaddr: normalizeSrvAddr(verifiedPayload.server.srvaddr),
          srvport: verifiedPayload.server.srvport,
          srvpath: extractSrvPath(verifiedPayload.server.srvaddr) || '/s1/',
        },
      }
    }

    return sealedSession('invalid_launch')
  }

  if (allowUnsignedLaunch() && query.user) {
    const username = String(query.user)
    const userId = query.userId ? String(query.userId) : username

    return {
      authMode: 'guest',
      source: 'unsigned_legacy',
      trusted: false,
      playAllowed: true,
      player: {
        id: userId,
        username,
        displayName: username,
      },
      server: {
        id: 1,
        key: 's1',
        name: 'S1',
        srvaddr: 'muh5-ws.ccgame.org',
        srvport: '443',
        srvpath: '/s1/',
      },
    }
  }

  if (import.meta.dev) {
    let envOverride = ''
    try {
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
        playAllowed: true,
        player: {
          id: 'greenjade',
          username: 'quocquoc',
          displayName: 'quocquoc (Dev Override)',
        },
        server: {
          id: 1,
          key: 's1',
          name: 'S1',
          srvaddr: 'muh5-ws.ccgame.org',
          srvport: '443',
          srvpath: '/s1/',
        },
      }
    }
  }

  return sealedSession('sealed')
}
