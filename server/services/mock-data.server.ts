import type { WalletBalance, Transaction, ShopItem, EventStatus, LeaderboardEntry } from '~~/types/sdk'

export const getWalletBalance = (): WalletBalance => {
  return {
    wcoin: 0,
    wpoint: 0,
  }
}

export const getTransactionHistory = (): Transaction[] => {
  return []
}

export const getShopItems = (): ShopItem[] => {
  return []
}

export const getEventStatus = (): EventStatus[] => {
  return []
}

export const getLeaderboard = (): LeaderboardEntry[] => {
  return []
}
