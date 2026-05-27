import { apiSuccess } from '../utils/api-response'
import { getWalletBalance, getTransactionHistory } from '../services/mock-data.server'

export default defineEventHandler(() => {
  return apiSuccess({
    balance: getWalletBalance(),
    history: getTransactionHistory(),
  })
})
