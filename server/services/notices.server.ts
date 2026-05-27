/**
 * Server-side static notices for the MUH5 SDK NoticesPanel.
 *
 * P0 scope: hand-curated static list. No DB, no ack write, no public mutation.
 * Replace with a portal DB read (announcements.active) when admin tooling exists.
 */
import type { Notice } from '~~/types/sdk'

const NOTICES: Notice[] = [
  {
    id: 1,
    title: 'Chào mừng đến MU H5 S1',
    body: 'Phiên truy cập đang ở giai đoạn cộng đồng (community test). Mọi tính năng ví, nạp, sự kiện đều đang niêm phong cho đến khi đợt kiểm thử hoàn tất.',
    type: 'info',
    icon: 'i-heroicons-megaphone',
    publishedAt: '2026-05-28T00:00:00+07:00',
  },
  {
    id: 2,
    title: 'Phiên launch chỉ qua CCGame',
    body: 'Vào game từ ccgame.org để có signed launch token. Đường dẫn legacy unsigned đã bị niêm phong.',
    type: 'warning',
    icon: 'i-heroicons-shield-check',
    publishedAt: '2026-05-26T00:00:00+07:00',
  },
  {
    id: 3,
    title: 'Nhân vật khách',
    body: 'Khách (guest) tự sinh nhân vật tạm thời theo phiên. Tài khoản chính thức cần đăng nhập qua CCGame.',
    type: 'info',
    icon: 'i-heroicons-user',
    publishedAt: '2026-05-25T00:00:00+07:00',
  },
]

export const getNotices = (): Notice[] => NOTICES
