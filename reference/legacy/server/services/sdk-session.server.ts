/**
 * Verified launch session + portal user lookup for SDK read APIs.
 */
import type { H3Event } from 'h3'
import type { RowDataPacket } from 'mysql2'
import { getPortalDbConfig, getPool } from '../utils/muh5Db'
import { logDbWarn, safeInt, safeStringOrNull } from '../utils/values'
import { verifyLaunchToken } from './launch-token.server'
import type { Muh5LaunchPayload } from '~~/types/launch'
import type { SdkReadReason } from '~~/types/sdk'

export type PortalUser = {
  id: number
  username: string
  name: string | null
  vip: number
  tier: string | null
  wcoin: number
  wpoint: number
}

/**
 * Portal `users` has no numeric `vip` column; tier is a string (default 'free').
 * Derive a best-effort VIP level from any trailing digits in tier ('vip3' -> 3),
 * otherwise non-free tiers map to 1 and 'free'/empty maps to 0.
 */
export const deriveVipFromTier = (tier: string | null): number => {
  if (!tier) return 0
  const normalized = tier.trim().toLowerCase()
  if (!normalized || normalized === 'free') return 0
  const match = normalized.match(/(\d+)/)
  if (match) return safeInt(match[1])
  return 1
}

export type ResolvedSdkSession
  = | { ok: true, payload: Muh5LaunchPayload, user: PortalUser }
    | { ok: false, reason: SdkReadReason }

export const getLaunchTokenFromEvent = (event: H3Event): string | null => {
  const query = getQuery(event)
  const launchRaw = query.launch
  if (!launchRaw || typeof launchRaw !== 'string') {
    return null
  }
  return launchRaw
}

export const resolveSdkSession = async (event: H3Event): Promise<ResolvedSdkSession> => {
  const launchRaw = getLaunchTokenFromEvent(event)
  if (!launchRaw) {
    return { ok: false, reason: 'session_untrusted' }
  }

  const payload = verifyLaunchToken(launchRaw)
  if (!payload) {
    return { ok: false, reason: 'session_untrusted' }
  }

  const username = payload.player?.username?.trim()
  if (!username) {
    return { ok: false, reason: 'username_missing' }
  }

  const config = useRuntimeConfig()
  const portalDb = getPortalDbConfig(config)
  if (!portalDb) {
    return { ok: false, reason: 'db_not_configured' }
  }

  try {
    const pool = getPool(portalDb)
    const [rows] = await pool.execute<RowDataPacket[]>(
      `SELECT id, username, name, tier, wcoin, wpoint
       FROM users
       WHERE username = ?
       LIMIT 1`,
      [username],
    )
    const row = rows[0]
    if (!row) {
      return { ok: false, reason: 'account_not_found' }
    }

    const tier = safeStringOrNull(row.tier)

    return {
      ok: true,
      payload,
      user: {
        id: safeInt(row.id),
        username: safeStringOrNull(row.username) || username,
        name: safeStringOrNull(row.name),
        vip: deriveVipFromTier(tier),
        tier,
        wcoin: safeInt(row.wcoin),
        wpoint: safeInt(row.wpoint),
      },
    }
  }
  catch (err: unknown) {
    logDbWarn('SDK session lookup failed', err)
    return { ok: false, reason: 'db_error' }
  }
}
