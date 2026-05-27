import { apiSuccess } from '../utils/api-response'
import { getSessionUser } from '../services/session.server'
import { sdkConfig } from '~~/config/sdk.config'

export default defineEventHandler(() => {
  const user = getSessionUser()

  return apiSuccess({
    user,
    config: sdkConfig,
  })
})
