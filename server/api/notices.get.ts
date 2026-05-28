import { apiSuccess } from '../utils/api-response'
import { readNotices } from '../services/notices.server'

export default defineEventHandler(async () => {
  const result = await readNotices()
  return apiSuccess(result)
})
