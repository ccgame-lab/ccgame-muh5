import type { WalletBalance, Transaction, ShopItem, EventStatus, LeaderboardEntry } from '~~/types/sdk'

export const getWalletBalance = (): WalletBalance => {
  return {
    wcoin: 50000,
    wpoint: 1500,
  }
}

export const getTransactionHistory = (): Transaction[] => {
  return [
    { id: 'tx_1', amount: 10000, type: 'deposit', description: '[Mock] Nhận xu thử nghiệm', createdAt: '2026-05-20T10:00:00Z' },
    { id: 'tx_2', amount: 5000, type: 'spend', description: '[Mock] Khởi tạo nhân vật thử nghiệm', createdAt: '2026-05-21T14:30:00Z' },
    { id: 'tx_3', amount: 200, type: 'withdraw', description: '[Mock] Thưởng thử nghiệm sự kiện', createdAt: '2026-05-25T09:15:00Z' },
  ]
}

export const getShopItems = (): ShopItem[] => {
  return [
    { id: 'item_1', name: '[Mock] Gói Tân Thủ', description: '[Mock] Nhận ngay vũ khí siêu cấp', price: 10000, currency: 'wcoin' },
    { id: 'item_2', name: '[Mock] Vé Quay May Mắn', description: '[Mock] Dùng cho Vòng Quay', price: 50, currency: 'wpoint' },
    { id: 'item_3', name: '[Mock] Thẻ Đổi Tên', description: '[Mock] Đổi tên nhân vật', price: 200, currency: 'wpoint' },
  ]
}

export const getEventStatus = (): EventStatus[] => {
  return [
    { id: 'evt_1', name: '[Mock] Vòng Quay May Mắn', isActive: true, description: '[Dữ liệu mẫu] Đang diễn ra. Nhận quà cực khủng!' },
    { id: 'evt_2', name: '[Mock] Đua Top Lực Chiến', isActive: true, description: '[Dữ liệu mẫu] Kết thúc trong 3 ngày tới.' },
    { id: 'evt_3', name: '[Mock] Nạp Tích Lũy', isActive: false, description: '[Dữ liệu mẫu] Đã kết thúc.' },
  ]
}

export const getLeaderboard = (): LeaderboardEntry[] => {
  return [
    { rank: 1, username: '[Mock] vip_pro_99', score: 999999 },
    { rank: 2, username: '[Mock] muh5_player_01', score: 850000 },
    { rank: 3, username: '[Mock] goku_ssj', score: 700000 },
    { rank: 4, username: '[Mock] saitama', score: 650000 },
    { rank: 5, username: '[Mock] noob_master', score: 500000 },
  ]
}
