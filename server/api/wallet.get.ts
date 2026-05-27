import { apiSuccess } from '../utils/api-response'
import { readWalletForSession } from '../services/wallet.server'

export default defineEventHandler(async (event) => {
  const result = await readWalletForSession(event)
  return apiSuccess(result)
})
