/**
 * Read-only notices from legacy portal DB announcements table.
 */
import type { RowDataPacket } from 'mysql2'
import { getPortalDbConfig, getPool } from '../utils/muh5Db'
import { logDbWarn, safeString } from '../utils/values'
import type { Notice, SdkReadMeta } from '~~/types/sdk'

export type NoticesReadResult = SdkReadMeta & {
  items: Notice[]
}

export const readNotices = async (): Promise<NoticesReadResult> => {
  const portalDb = getPortalDbConfig(useRuntimeConfig())
  if (!portalDb) {
    return { sealed: true, reason: 'db_not_configured', items: [] }
  }

  try {
    const pool = getPool(portalDb)
    const [rows] = await pool.execute<RowDataPacket[]>(
      `SELECT id, title, body, type, icon, link, created_at
       FROM announcements
       WHERE is_active = 1
         AND (expires_at IS NULL OR expires_at > NOW())
       ORDER BY created_at DESC
       LIMIT 5`,
    )

    const items: Notice[] = rows.map((row) => {
      const noticeType = safeString(row.type)
      const mappedType: Notice['type']
        = noticeType === 'success' || noticeType === 'warning' ? noticeType : 'info'

      return {
        id: Number(row.id),
        title: safeString(row.title) || 'Thông báo hệ thống',
        body: safeString(row.body),
        type: mappedType,
        icon: safeString(row.icon) || 'i-heroicons-megaphone',
        link: safeString(row.link) || undefined,
        publishedAt: row.created_at ? safeString(row.created_at) : undefined,
      }
    })

    return { sealed: false, items }
  }
  catch (err: unknown) {
    logDbWarn('notices query failed', err)
    return { sealed: true, reason: 'db_error', items: [] }
  }
}
