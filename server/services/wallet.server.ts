/**
 * Read-only wallet lookup.
 *
 * Verifies signed launch token from query, then reads wcoin/wpoint from
 * muh5_ccgame.users by verified username. NEVER reads userId/user from query
 * directly. Returns sealed when DB not configured or row missing so the UI
 * shows "—" instead of fake zeros.
 */
import { getQuery, type H3Event } from 'h3'
import type { RowDataPacket } from 'mysql2'
import { getPortalDbConfig, getPool } from '../utils/muh5Db'
import { verifyLaunchToken } from './launch-token.server'
import type { WalletReadResult, WalletSealedReason } from '~~/types/sdk'

const sealed = (reason: WalletSealedReason): WalletReadResult => ({
  sealed: true,
  reason,
  balance: { wcoin: null, wpoint: null },
})

const safeInt = (value: unknown): number => {
  const n = Number(value)
  return Number.isFinite(n) ? Math.trunc(n) : 0
}

export const readWalletForSession = async (event: H3Event): Promise<WalletReadResult> => {
  const query = getQuery(event)
  const launchRaw = query.launch
  if (!launchRaw || typeof launchRaw !== 'string') {
    return sealed('session_untrusted')
  }

  const payload = verifyLaunchToken(launchRaw)
  if (!payload) {
    return sealed('session_untrusted')
  }

  const username = payload.player?.username?.trim()
  if (!username) {
    return sealed('username_missing')
  }

  const config = useRuntimeConfig()
  const portalDb = getPortalDbConfig(config)
  if (!portalDb) {
    return sealed('db_not_configured')
  }

  try {
    const pool = getPool(portalDb)
    const [rows] = await pool.execute<RowDataPacket[]>(
      `SELECT wcoin, wpoint FROM users WHERE username = ? LIMIT 1`,
      [username],
    )
    const row = rows[0]
    if (!row) {
      return sealed('account_not_found')
    }
    return {
      sealed: false,
      balance: {
        wcoin: safeInt(row.wcoin),
        wpoint: safeInt(row.wpoint),
      },
    }
  }
  catch (err: unknown) {
    const msg = err instanceof Error ? err.message : String(err)
    console.warn('MUH5 wallet query failed:', msg)
    return sealed('db_error')
  }
}
