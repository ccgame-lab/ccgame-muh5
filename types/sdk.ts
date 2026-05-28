export type SdkReadReason
  = | 'db_not_configured'
    | 'session_untrusted'
    | 'username_missing'
    | 'account_not_found'
    | 'db_error'
    | 'no_legacy_source'
    | 'no_legacy_data'

export interface SdkReadMeta {
  sealed: boolean
  reason?: SdkReadReason
}

export interface Transaction {
  id: string
  currency: 'wcoin' | 'wpoint'
  amount: number
  type: string
  description: string
  createdAt: string
}

export interface HistoryReadResult extends SdkReadMeta {
  items: Transaction[]
}

export interface LeaderboardEntry {
  rank: number
  username: string
  score: number
  level?: number
  job?: number
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

export type NoticesReadResult = SdkReadMeta & {
  items: Notice[]
}

export interface GiftcodeItem {
  id: number
  code: string
  usedCount: number
  limitUsage: number
  rewardType: string
  expiresAt?: string
  redeemed?: boolean
}

export interface GiftcodeReadResult extends SdkReadMeta {
  redeemEnabled: false
  items: GiftcodeItem[]
  redeemedIds: number[]
}

export interface MiningMachine {
  machineIndex: number
  level: number
  speedLevel: number
  storageLevel: number
  efficiencyLevel: number
  baseRate: number
  capacity: number
  lastClaimAt: string | null
}

export interface MiningReadResult extends SdkReadMeta {
  balance: number | null
  machines: MiningMachine[]
}

export interface WalletReadResult {
  sealed: boolean
  reason?: SdkReadReason
  balance: {
    wcoin: number | null
    wpoint: number | null
  }
}
