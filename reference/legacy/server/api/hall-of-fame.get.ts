import { apiSuccess } from '../utils/api-response'
import { readHallOfFame } from '../services/hall-of-fame.server'

export default defineEventHandler(async () => {
  const result = await readHallOfFame()
  return apiSuccess(result)
})
