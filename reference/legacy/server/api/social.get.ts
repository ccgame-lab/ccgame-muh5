import { apiSuccess } from '../utils/api-response'
import { readSocialEvents } from '../services/social.server'

export default defineEventHandler(async () => {
  const result = await readSocialEvents()
  return apiSuccess(result)
})
