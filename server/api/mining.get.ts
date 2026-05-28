import { apiSuccess } from '../utils/api-response'
import { readMiningData } from '../services/mining.server'

export default defineEventHandler(async (event) => {
  const result = await readMiningData(event)
  return apiSuccess(result)
})
