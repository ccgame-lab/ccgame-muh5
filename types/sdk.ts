export interface UserProfile {
  id: string
  username: string
  avatar: string
}

export interface WalletBalance {
  wcoin: number
  wpoint: number
}

export interface Transaction {
  id: string
  amount: number
  type: 'deposit' | 'withdraw' | 'spend'
  description: string
  createdAt: string
}

export interface ShopItem {
  id: string
  name: string
  description: string
  price: number
  currency: 'wcoin' | 'wpoint'
  image?: string
}

export interface EventStatus {
  id: string
  name: string
  isActive: boolean
  description: string
}

export interface LeaderboardEntry {
  rank: number
  username: string
  score: number
}
