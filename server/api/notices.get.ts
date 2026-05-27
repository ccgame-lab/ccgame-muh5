import { apiSuccess } from '../utils/api-response'
import { getNotices } from '../services/notices.server'

export default defineEventHandler(() => {
  return apiSuccess(getNotices())
})
