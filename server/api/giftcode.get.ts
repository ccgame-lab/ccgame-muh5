import { apiSuccess } from '../utils/api-response'
import { readGiftcodes } from '../services/giftcode.server'

export default defineEventHandler(async (event) => {
  const result = await readGiftcodes(event)
  return apiSuccess(result)
})
