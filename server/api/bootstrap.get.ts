import { apiSuccess } from '../utils/api-response'
import { getSessionUser } from '../services/session.server'
import { sdkConfig } from '~~/config/sdk.config'

export default defineEventHandler((event) => {
  const sessionData = getSessionUser(event)

  return apiSuccess({
    session: {
      authMode: sessionData.authMode,
      source: sessionData.source,
      trusted: sessionData.trusted,
      playAllowed: sessionData.playAllowed,
    },
    player: sessionData.player,
    server: sessionData.server,
    config: sdkConfig,
  })
})
