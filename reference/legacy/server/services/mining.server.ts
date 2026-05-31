/**
 * Read-only diamond generator / mining data from legacy portal DB.
 */
import type { H3Event } from 'h3'
import type { RowDataPacket } from 'mysql2'
import { getPortalDbConfig, getPool } from '../utils/muh5Db'
import { logDbWarn, safeInt, safeStringOrNull } from '../utils/values'
import { resolveSdkSession } from './sdk-session.server'
import type { MiningMachine, MiningReadResult } from '~~/types/sdk'

export const readMiningData = async (event: H3Event): Promise<MiningReadResult> => {
  const session = await resolveSdkSession(event)
  if (!session.ok) {
    return { sealed: true, reason: session.reason, balance: null, machines: [] }
  }

  const portalDb = getPortalDbConfig(useRuntimeConfig())
  if (!portalDb) {
    return { sealed: true, reason: 'db_not_configured', balance: null, machines: [] }
  }

  try {
    const pool = getPool(portalDb)
    const userId = session.user.id
    let balance: number | null = null
    const machines: MiningMachine[] = []

    try {
      const [walletRows] = await pool.execute<RowDataPacket[]>(
        `SELECT balance FROM diamond_wallets WHERE user_id = ? LIMIT 1`,
        [userId],
      )
      if (walletRows[0]) {
        balance = safeInt(walletRows[0].balance)
      }
    }
    catch (err: unknown) {
      logDbWarn('diamond_wallets query failed', err)
    }

    try {
      const [machineRows] = await pool.execute<RowDataPacket[]>(
        `SELECT machine_index, level, speed_level, storage_level, efficiency_level,
                base_rate, capacity, last_claim_at
         FROM diamond_machines
         WHERE user_id = ?
         ORDER BY machine_index ASC`,
        [userId],
      )
      for (const row of machineRows) {
        machines.push({
          machineIndex: safeInt(row.machine_index),
          level: safeInt(row.level),
          speedLevel: safeInt(row.speed_level),
          storageLevel: safeInt(row.storage_level),
          efficiencyLevel: safeInt(row.efficiency_level),
          baseRate: safeInt(row.base_rate),
          capacity: safeInt(row.capacity),
          lastClaimAt: safeStringOrNull(row.last_claim_at),
        })
      }
    }
    catch (err: unknown) {
      logDbWarn('diamond_machines query failed', err)
    }

    if (balance == null && machines.length === 0) {
      return { sealed: true, reason: 'no_legacy_data', balance: null, machines: [] }
    }

    return { sealed: false, balance, machines }
  }
  catch (err: unknown) {
    logDbWarn('mining query failed', err)
    return { sealed: true, reason: 'db_error', balance: null, machines: [] }
  }
}
