import { getQuery } from 'h3'
import { apiSuccess } from '../utils/api-response'
import { getLeaderboard, normalizeTab } from '../services/leaderboard.server'

export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const tab = normalizeTab(query.tab)
  const entries = await getLeaderboard(tab)
  return apiSuccess({
    tab,
    entries,
  })
})
