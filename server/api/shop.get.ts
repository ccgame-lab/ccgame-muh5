import { apiSuccess } from '../utils/api-response'
import { getShopItems } from '../services/mock-data.server'

export default defineEventHandler(() => {
  return apiSuccess(getShopItems())
})
