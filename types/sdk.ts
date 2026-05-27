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
  level?: number
  job?: number
  /** legacy users.username (accountname in actors table); useful for self-highlight later */
  accountname?: string
}

export type LeaderboardTab = 'power' | 'level'

export interface Notice {
  id: number
  title: string
  body: string
  type: 'info' | 'success' | 'warning'
  icon?: string
  link?: string
  publishedAt?: string
}

export type WalletSealedReason
  = | 'db_not_configured'
    | 'session_untrusted'
    | 'username_missing'
    | 'account_not_found'
    | 'db_error'

export interface WalletReadResult {
  /** Whether the response carries real balance values from DB. */
  sealed: boolean
  reason?: WalletSealedReason
  balance: {
    wcoin: number | null
    wpoint: number | null
  }
}
