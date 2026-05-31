/**
 * Read-only hall of fame (legends) from legacy portal DB.
 */
import type { RowDataPacket } from 'mysql2'
import { getPortalDbConfig, getPool } from '../utils/muh5Db'
import { logDbWarn, safeInt, safeString } from '../utils/values'
import type { HallOfFameEntry, HallOfFameReadResult } from '~~/types/sdk'

const LIST_LIMIT = 12

const parseRewards = (raw: unknown): string[] => {
  if (Array.isArray(raw)) {
    return raw.map(item => safeString(item)).filter(Boolean)
  }
  if (typeof raw === 'string' && raw.trim()) {
    try {
      const parsed = JSON.parse(raw)
      if (Array.isArray(parsed)) {
        return parsed.map(item => safeString(item)).filter(Boolean)
      }
    }
    catch {
      // ignore malformed legacy JSON
    }
  }
  return []
}

export const readHallOfFame = async (): Promise<HallOfFameReadResult> => {
  const portalDb = getPortalDbConfig(useRuntimeConfig())
  if (!portalDb) {
    return { sealed: true, reason: 'db_not_configured', items: [] }
  }

  try {
    const pool = getPool(portalDb)
    const [rows] = await pool.execute<RowDataPacket[]>(
      `SELECT id, server_name, server_key, server_status, category,
              category_label, player_name, score_value, score_label, rewards
       FROM hall_of_fame_legends
       ORDER BY sort_order ASC, id DESC
       LIMIT ?`,
      [LIST_LIMIT],
    )

    const items: HallOfFameEntry[] = rows.map((row) => {
      const status = safeString(row.server_status) === 'ongoing' ? 'ongoing' : 'completed'
      const category = safeString(row.category) === 'donate' ? 'donate' : 'combat'
      return {
        id: safeInt(row.id),
        serverName: safeString(row.server_name),
        serverKey: safeString(row.server_key),
        serverStatus: status,
        category,
        categoryLabel: safeString(row.category_label),
        playerName: row.player_name == null ? null : safeString(row.player_name),
        scoreValue: row.score_value == null ? null : safeInt(row.score_value),
        scoreLabel: safeString(row.score_label),
        rewards: parseRewards(row.rewards),
      }
    })

    return { sealed: false, items }
  }
  catch (err: unknown) {
    logDbWarn('hall_of_fame_legends query failed', err)
    return { sealed: true, reason: 'db_error', items: [] }
  }
}
