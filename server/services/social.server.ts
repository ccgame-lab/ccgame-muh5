/**
 * Read-only social activity feed from legacy portal DB.
 */
import type { RowDataPacket } from 'mysql2'
import { getPortalDbConfig, getPool } from '../utils/muh5Db'
import { logDbWarn, safeInt, safeString, safeStringOrNull } from '../utils/values'
import type { SocialEvent, SocialReadResult } from '~~/types/sdk'

const LIST_LIMIT = 15

const parseMetadata = (raw: unknown): Record<string, unknown> | null => {
  if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
    return raw as Record<string, unknown>
  }
  if (typeof raw === 'string' && raw.trim()) {
    try {
      const parsed = JSON.parse(raw)
      if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
        return parsed as Record<string, unknown>
      }
    }
    catch {
      // ignore malformed legacy JSON
    }
  }
  return null
}

export const readSocialEvents = async (): Promise<SocialReadResult> => {
  const portalDb = getPortalDbConfig(useRuntimeConfig())
  if (!portalDb) {
    return { sealed: true, reason: 'db_not_configured', items: [] }
  }

  try {
    const pool = getPool(portalDb)
    const [rows] = await pool.execute<RowDataPacket[]>(
      `SELECT id, username, event_type, template, metadata, created_at
       FROM social_events
       ORDER BY priority DESC, created_at DESC
       LIMIT ?`,
      [LIST_LIMIT],
    )

    const items: SocialEvent[] = rows.map(row => ({
      id: safeInt(row.id),
      username: safeStringOrNull(row.username),
      eventType: safeString(row.event_type) || 'unknown',
      template: safeStringOrNull(row.template),
      metadata: parseMetadata(row.metadata),
      createdAt: safeString(row.created_at),
    }))

    return { sealed: false, items }
  }
  catch (err: unknown) {
    logDbWarn('social_events query failed', err)
    return { sealed: true, reason: 'db_error', items: [] }
  }
}
