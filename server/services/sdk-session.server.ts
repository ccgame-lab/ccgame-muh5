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
  vip: number | null
  tier: string | null
  wcoin: number
  wpoint: number
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
      `SELECT id, username, name, vip, tier, wcoin, wpoint
       FROM users
       WHERE username = ?
       LIMIT 1`,
      [username],
    )
    const row = rows[0]
    if (!row) {
      return { ok: false, reason: 'account_not_found' }
    }

    return {
      ok: true,
      payload,
      user: {
        id: safeInt(row.id),
        username: safeStringOrNull(row.username) || username,
        name: safeStringOrNull(row.name),
        vip: row.vip == null ? null : safeInt(row.vip),
        tier: safeStringOrNull(row.tier),
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
