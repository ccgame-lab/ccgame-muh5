import { apiSuccess } from '../utils/api-response'
import { getSessionUser } from '../services/session.server'
import { resolveSdkSession } from '../services/sdk-session.server'
import { sdkConfig } from '~~/config/sdk.config'

export default defineEventHandler(async (event) => {
  const sessionData = getSessionUser(event)

  let account: { tier: string | null, vip: number } | null = null
  if (sessionData.trusted) {
    const resolved = await resolveSdkSession(event)
    if (resolved.ok) {
      account = { tier: resolved.user.tier, vip: resolved.user.vip }
    }
  }

  return apiSuccess({
    session: {
      authMode: sessionData.authMode,
      source: sessionData.source,
      trusted: sessionData.trusted,
      playAllowed: sessionData.playAllowed,
    },
    player: sessionData.player,
    server: sessionData.server,
    account,
    config: sdkConfig,
  })
})
