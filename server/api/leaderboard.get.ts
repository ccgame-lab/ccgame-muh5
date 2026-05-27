import { apiSuccess } from '../utils/api-response'
import { getLeaderboard } from '../services/mock-data.server'

export default defineEventHandler(() => {
  return apiSuccess(getLeaderboard())
})
