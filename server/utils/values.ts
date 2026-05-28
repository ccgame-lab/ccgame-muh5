export const safeInt = (value: unknown): number => {
  const n = Number(value)
  return Number.isFinite(n) ? Math.trunc(n) : 0
}

export const safeNumber = (value: unknown): number => {
  const n = Number(value)
  return Number.isFinite(n) ? n : 0
}

export const safeString = (value: unknown): string => {
  if (value == null) return ''
  return String(value)
}

export const safeStringOrNull = (value: unknown): string | null => {
  const s = safeString(value).trim()
  return s || null
}

export const logDbWarn = (scope: string, err: unknown): void => {
  const msg = err instanceof Error ? err.message : String(err)
  console.warn(`MUH5 ${scope}:`, msg)
}
