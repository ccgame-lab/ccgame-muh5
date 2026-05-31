/**
 * Read-only giftcode list + per-user redemption flags from legacy portal DB.
 */
import type { H3Event } from 'h3'
import type { RowDataPacket } from 'mysql2'
import { getPortalDbConfig, getPool } from '../utils/muh5Db'
import { logDbWarn, safeInt, safeString } from '../utils/values'
import { resolveSdkSession } from './sdk-session.server'
import type { GiftcodeItem, GiftcodeReadResult } from '~~/types/sdk'

const LIST_LIMIT = 10

const emptyResult = (sealed: boolean, reason?: GiftcodeReadResult['reason']): GiftcodeReadResult => ({
  sealed,
  reason,
  redeemEnabled: false,
  items: [],
  redeemedIds: [],
})

export const readGiftcodes = async (event: H3Event): Promise<GiftcodeReadResult> => {
  const portalDb = getPortalDbConfig(useRuntimeConfig())
  if (!portalDb) {
    return emptyResult(true, 'db_not_configured')
  }

  const session = await resolveSdkSession(event)
  const userId = session.ok ? session.user.id : null

  try {
    const pool = getPool(portalDb)
    const [rows] = await pool.execute<RowDataPacket[]>(
      `SELECT id, code, limit_usage, used_count, reward_type, expires_at
       FROM giftcodes
       ORDER BY id DESC
       LIMIT ?`,
      [LIST_LIMIT],
    )

    let redeemedIds: number[] = []
    if (userId != null) {
      try {
        const [redemptionRows] = await pool.execute<RowDataPacket[]>(
          `SELECT giftcode_id FROM giftcode_redemptions WHERE user_id = ?`,
          [userId],
        )
        redeemedIds = redemptionRows.map(row => safeInt(row.giftcode_id)).filter(id => id > 0)
      }
      catch (err: unknown) {
        logDbWarn('giftcode_redemptions query failed', err)
      }
    }

    const redeemedSet = new Set(redeemedIds)
    const items: GiftcodeItem[] = rows.map((row) => {
      const id = safeInt(row.id)
      return {
        id,
        code: safeString(row.code),
        usedCount: safeInt(row.used_count),
        limitUsage: safeInt(row.limit_usage),
        rewardType: safeString(row.reward_type) || 'unknown',
        expiresAt: safeString(row.expires_at) || undefined,
        redeemed: redeemedSet.has(id),
      }
    })

    return {
      sealed: !session.ok,
      reason: session.ok ? undefined : session.reason,
      redeemEnabled: false,
      items,
      redeemedIds,
    }
  }
  catch (err: unknown) {
    logDbWarn('giftcodes query failed', err)
    return emptyResult(true, 'db_error')
  }
}
