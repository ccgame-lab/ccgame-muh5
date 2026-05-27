import { apiSuccess } from '../utils/api-response'
import { getSessionUser } from '../services/session.server'
import { sdkConfig } from '~~/config/sdk.config'

export default defineEventHandler((event) => {
  const user = getSessionUser(event)

  return apiSuccess({
    user,
    config: sdkConfig,
  })
})
