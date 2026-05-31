import type { SdkReadReason } from '~~/types/sdk'

const BASE: Partial<Record<SdkReadReason, string>> = {
  session_untrusted: 'Phiên launch không hợp lệ. Vào lại từ CCGame.',
  username_missing: 'Phiên launch thiếu tên tài khoản game.',
  account_not_found: 'Chưa có hồ sơ tài khoản legacy cho phiên này.',
  db_not_configured: 'Chưa cấu hình DB legacy portal.',
  db_error: 'Tạm thời không đọc được dữ liệu từ legacy.',
  no_legacy_source: 'Chưa tìm thấy nguồn dữ liệu legacy cho tính năng này.',
  no_legacy_data: 'Chưa có dữ liệu từ legacy cho tài khoản này.',
}

export function sdkReadMessage(
  reason: SdkReadReason | undefined,
  fallback: string,
  overrides?: Partial<Record<SdkReadReason, string>>,
): string {
  if (!reason) return fallback
  return overrides?.[reason] ?? BASE[reason] ?? fallback
}
