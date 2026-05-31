/**
 * Read-only wallet transaction history from legacy portal DB.
 */
import type { H3Event } from 'h3'
import type { RowDataPacket } from 'mysql2'
import { getPortalDbConfig, getPool } from '../utils/muh5Db'
import { logDbWarn, safeInt, safeString } from '../utils/values'
import { resolveSdkSession } from './sdk-session.server'
import type { HistoryReadResult, Transaction } from '~~/types/sdk'

const TX_LIMIT = 10

const mapTransaction = (row: RowDataPacket, currency: Transaction['currency']): Transaction => {
  const legacyType = safeString(row.type) || 'unknown'
  const reference = safeString(row.reference)
  return {
    id: `${currency}-${safeString(row.id)}`,
    currency,
    amount: safeInt(row.amount),
    type: legacyType,
    description: reference || legacyType,
    createdAt: safeString(row.created_at),
  }
}

export const readTransactionHistory = async (event: H3Event): Promise<HistoryReadResult> => {
  const session = await resolveSdkSession(event)
  if (!session.ok) {
    return { sealed: true, reason: session.reason, items: [] }
  }

  const portalDb = getPortalDbConfig(useRuntimeConfig())
  if (!portalDb) {
    return { sealed: true, reason: 'db_not_configured', items: [] }
  }

  try {
    const pool = getPool(portalDb)
    const userId = session.user.id
    const items: Transaction[] = []

    for (const [table, currency] of [
      ['wcoin_transactions', 'wcoin'],
      ['wpoint_transactions', 'wpoint'],
    ] as const) {
      try {
        const [rows] = await pool.execute<RowDataPacket[]>(
          `SELECT id, user_id, amount, type, reference, created_at
           FROM ${table}
           WHERE user_id = ?
           ORDER BY created_at DESC
           LIMIT ?`,
          [userId, TX_LIMIT],
        )
        items.push(...rows.map(row => mapTransaction(row, currency)))
      }
      catch (err: unknown) {
        logDbWarn(`${table} query failed`, err)
      }
    }

    items.sort((a, b) => {
      const ta = Date.parse(a.createdAt)
      const tb = Date.parse(b.createdAt)
      return Number.isFinite(ta) && Number.isFinite(tb) ? tb - ta : 0
    })

    return { sealed: false, items }
  }
  catch (err: unknown) {
    logDbWarn('history query failed', err)
    return { sealed: true, reason: 'db_error', items: [] }
  }
}
