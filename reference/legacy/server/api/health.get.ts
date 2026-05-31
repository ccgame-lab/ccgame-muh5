import { apiSuccess } from '../utils/api-response'

export default defineEventHandler(() => {
  return apiSuccess({ status: 'ok', timestamp: new Date().toISOString() })
})
