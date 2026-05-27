/**
 * Read-only leaderboard service.
 *
 * Reads top actors from actor_s1.actors (game DB) when env is configured.
 * Cached in-memory 60s. Falls back to [] on any error / missing config so
 * /play never blocks on leaderboard failure.
 */
import type { RowDataPacket } from 'mysql2'
import { getGameDbConfig, getPool } from '../utils/muh5Db'
import type { LeaderboardEntry, LeaderboardTab } from '~~/types/sdk'

type CacheEntry = {
  fetchedAt: number
  data: LeaderboardEntry[]
}

const CACHE_TTL_MS = 60_000
const TOP_LIMIT = 20
const cache = new Map<LeaderboardTab, CacheEntry>()

const VALID_TABS: ReadonlyArray<LeaderboardTab> = ['power', 'level']

export const normalizeTab = (raw: unknown): LeaderboardTab => {
  const value = typeof raw === 'string' ? raw.toLowerCase() : ''
  return VALID_TABS.includes(value as LeaderboardTab) ? (value as LeaderboardTab) : 'power'
}

const safeNumber = (value: unknown): number => {
  const n = Number(value)
  return Number.isFinite(n) ? n : 0
}

const safeString = (value: unknown): string => {
  if (value == null) return ''
  return String(value)
}

export const getLeaderboard = async (tab: LeaderboardTab): Promise<LeaderboardEntry[]> => {
  const now = Date.now()
  const cached = cache.get(tab)
  if (cached && now - cached.fetchedAt < CACHE_TTL_MS) {
    return cached.data
  }

  const config = useRuntimeConfig()
  const gameDb = getGameDbConfig(config)
  if (!gameDb) {
    return []
  }

  try {
    const pool = getPool(gameDb)
    const orderBy = tab === 'level'
      ? 'level DESC, totalpower DESC'
      : 'totalpower DESC'

    const [rows] = await pool.execute<RowDataPacket[]>(
      `SELECT actorname, accountname, level, job, totalpower
       FROM actors
       WHERE level > 1
       ORDER BY ${orderBy}
       LIMIT ?`,
      [TOP_LIMIT],
    )

    const data: LeaderboardEntry[] = rows.map((row, idx) => {
      const level = safeNumber(row.level)
      const totalpower = safeNumber(row.totalpower)
      const score = tab === 'level' ? level : totalpower
      return {
        rank: idx + 1,
        username: safeString(row.actorname) || safeString(row.accountname) || `Player ${idx + 1}`,
        score,
        level,
        job: safeNumber(row.job),
        accountname: safeString(row.accountname),
      }
    })

    cache.set(tab, { fetchedAt: now, data })
    return data
  }
  catch (err: unknown) {
    const msg = err instanceof Error ? err.message : String(err)
    console.warn(`MUH5 leaderboard query failed (tab=${tab}):`, msg)
    return []
  }
}
