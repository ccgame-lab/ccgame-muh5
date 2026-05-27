/**
 * MUH5 SDK read-only DB pool helpers.
 *
 * Read-only by design. These pools are never used for INSERT/UPDATE/DELETE
 * from MUH5 SDK; write paths belong to ccgame-web bridge or future server
 * services. Pool returns null when env not configured so endpoints can
 * gracefully fall back to sealed/empty state.
 */
import mysql from 'mysql2/promise'

type RuntimeConfigLike = {
  muh5PortalDbHost?: string
  muh5PortalDbPort?: string | number
  muh5PortalDbName?: string
  muh5PortalDbUser?: string
  muh5PortalDbPassword?: string
  muh5GameDbHost?: string
  muh5GameDbPort?: string | number
  muh5GameDbName?: string
  muh5GameDbUser?: string
  muh5GameDbPassword?: string
}

export type Muh5DbConfig = {
  host: string
  port: number
  database: string
  user: string
  password: string
}

const DB_IDENTIFIER_PATTERN = /^[A-Za-z0-9_]+$/
const pools = new Map<string, mysql.Pool>()

const toString = (value: unknown): string => {
  if (value == null) return ''
  if (typeof value === 'string') return value.trim()
  if (typeof value === 'number') return String(value)
  return ''
}

const toPort = (value: unknown): number => {
  const n = Number(value)
  return Number.isFinite(n) && n > 0 ? n : 3306
}

const assertDbIdentifier = (name: string, label: string): string => {
  if (!DB_IDENTIFIER_PATTERN.test(name)) {
    throw new Error(`Invalid MUH5 ${label}: ${name}`)
  }
  return name
}

export const getPortalDbConfig = (config: RuntimeConfigLike): Muh5DbConfig | null => {
  const host = toString(config.muh5PortalDbHost)
  const user = toString(config.muh5PortalDbUser)
  const database = toString(config.muh5PortalDbName) || 'muh5_ccgame'

  if (!host || !user || !database) {
    return null
  }

  return {
    host,
    port: toPort(config.muh5PortalDbPort),
    database: assertDbIdentifier(database, 'portal database'),
    user,
    password: toString(config.muh5PortalDbPassword),
  }
}

export const getGameDbConfig = (config: RuntimeConfigLike): Muh5DbConfig | null => {
  const host = toString(config.muh5GameDbHost) || toString(config.muh5PortalDbHost)
  const user = toString(config.muh5GameDbUser) || toString(config.muh5PortalDbUser)
  const password = config.muh5GameDbPassword != null && toString(config.muh5GameDbPassword) !== ''
    ? toString(config.muh5GameDbPassword)
    : toString(config.muh5PortalDbPassword)
  const database = toString(config.muh5GameDbName) || 'actor_s1'

  if (!host || !user || !database) {
    return null
  }

  return {
    host,
    port: toPort(config.muh5GameDbPort || config.muh5PortalDbPort),
    database: assertDbIdentifier(database, 'game database'),
    user,
    password,
  }
}

export const getPool = (config: Muh5DbConfig): mysql.Pool => {
  const key = `${config.host}:${config.port}/${config.database}:${config.user}`
  const existing = pools.get(key)
  if (existing) return existing

  const pool = mysql.createPool({
    host: config.host,
    port: config.port,
    database: config.database,
    user: config.user,
    password: config.password,
    waitForConnections: true,
    connectionLimit: 2,
    connectTimeout: 5000,
    charset: 'utf8mb4',
  })
  pools.set(key, pool)
  return pool
}
