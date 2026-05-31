/**
 * Read-only leaderboard from actor_s1.actors (game DB).
 */
import type { RowDataPacket } from 'mysql2'
import { getGameDbConfig, getPool } from '../utils/muh5Db'
import { logDbWarn, safeNumber, safeString } from '../utils/values'
import type { LeaderboardEntry, LeaderboardTab } from '~~/types/sdk'

type CacheEntry = { fetchedAt: number, data: LeaderboardEntry[] }

const CACHE_TTL_MS = 60_000
const TOP_LIMIT = 20
const cache = new Map<LeaderboardTab, CacheEntry>()
const VALID_TABS: ReadonlyArray<LeaderboardTab> = ['power', 'level']

export const normalizeTab = (raw: unknown): LeaderboardTab => {
  const value = typeof raw === 'string' ? raw.toLowerCase() : ''
  return VALID_TABS.includes(value as LeaderboardTab) ? (value as LeaderboardTab) : 'power'
}

export const getLeaderboard = async (tab: LeaderboardTab): Promise<LeaderboardEntry[]> => {
  const now = Date.now()
  const cached = cache.get(tab)
  if (cached && now - cached.fetchedAt < CACHE_TTL_MS) {
    return cached.data
  }

  const gameDb = getGameDbConfig(useRuntimeConfig())
  if (!gameDb) {
    return []
  }

  try {
    const pool = getPool(gameDb)
    const orderBy = tab === 'level' ? 'level DESC, totalpower DESC' : 'totalpower DESC'

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
      return {
        rank: idx + 1,
        username: safeString(row.actorname) || safeString(row.accountname) || `Player ${idx + 1}`,
        score: tab === 'level' ? level : totalpower,
        level,
        job: safeNumber(row.job),
        accountname: safeString(row.accountname),
      }
    })

    cache.set(tab, { fetchedAt: now, data })
    return data
  }
  catch (err: unknown) {
    logDbWarn(`leaderboard query failed (tab=${tab})`, err)
    return []
  }
}
