import { apiSuccess } from '../utils/api-response'
import { getEventStatus } from '../services/mock-data.server'

export default defineEventHandler(() => {
  return apiSuccess(getEventStatus())
})
