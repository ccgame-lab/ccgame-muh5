import { apiSuccess } from '../utils/api-response'
import { readTransactionHistory } from '../services/history.server'

export default defineEventHandler(async (event) => {
  const result = await readTransactionHistory(event)
  return apiSuccess(result)
})
