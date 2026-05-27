// @ts-expect-error: crypto is a built-in node module
import { createHmac, timingSafeEqual } from 'crypto'
import type { Muh5LaunchPayload } from '~~/types/launch'

/** Dev-only fallback; must match ccgame-web when MUH5_LAUNCH_SECRET is unset locally. */
export const DEV_MUH5_LAUNCH_SECRET = 'ccgame-dev-muh5-launch-secret-local-only'

export const base64UrlEncode = (strOrObj: string | object): string => {
  const str = typeof strOrObj === 'string' ? strOrObj : JSON.stringify(strOrObj)
  // @ts-expect-error: Buffer is global in node environment
  return Buffer.from(str)
    .toString('base64')
    .replace(/=/g, '')
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
}

export const hmacSha256Base64url = (payloadBase64: string, secret: string): string => {
  return createHmac('sha256', secret).update(payloadBase64).digest('base64url')
}

export const safeEqual = (a: string, b: string): boolean => {
  // @ts-expect-error: Buffer is global in node environment
  const bufA = Buffer.from(a)
  // @ts-expect-error: Buffer is global in node environment
  const bufB = Buffer.from(b)
  if (bufA.length !== bufB.length) {
    return false
  }
  return timingSafeEqual(bufA, bufB)
}

export const getLaunchSecret = (): string | null => {
  // @ts-expect-error: process is global in node/nitro server environment
  const secret = process.env.MUH5_LAUNCH_SECRET
  if (secret) {
    return secret
  }
  if (import.meta.dev) {
    console.warn('DEV: MUH5_LAUNCH_SECRET not set; using local dev-only secret.')
    return DEV_MUH5_LAUNCH_SECRET
  }
  return null
}

export const verifyLaunchToken = (token: string): Muh5LaunchPayload | null => {
  if (!token) {
    return null
  }

  const secret = getLaunchSecret()
  if (!secret) {
    console.warn('MUH5_LAUNCH_SECRET is not configured; rejecting launch token.')
    return null
  }

  const parts = token.split('.')
  if (parts.length !== 2) {
    console.warn('Invalid launch token format: missing parts')
    return null
  }

  const payloadBase64 = parts[0]
  const signature = parts[1]
  if (!payloadBase64 || !signature) {
    return null
  }

  const expectedSignature = hmacSha256Base64url(payloadBase64, secret)

  if (!safeEqual(signature, expectedSignature)) {
    console.warn('Invalid launch token signature')
    return null
  }

  try {
    // @ts-expect-error: Buffer is global in node environment
    const jsonStr = Buffer.from(payloadBase64, 'base64url').toString('utf8')
    const payload = JSON.parse(jsonStr) as Muh5LaunchPayload

    if (payload.gameId !== 'muh5') {
      console.warn('Invalid launch token: incorrect gameId')
      return null
    }

    const now = Math.floor(Date.now() / 1000)
    if (payload.expiresAt < now) {
      console.warn('Launch token has expired')
      return null
    }

    if (payload.server?.key !== 's1') {
      console.warn('Invalid launch token: incorrect server key')
      return null
    }

    return payload
  }
  catch (err: unknown) {
    const errMsg = err instanceof Error ? err.message : String(err)
    console.warn('Failed to parse launch token payload:', errMsg)
    return null
  }
}

/** Roundtrip helper for local verification against ccgame-web signing. */
export const signLaunchPayload = (payload: Muh5LaunchPayload, secret: string): string => {
  const payloadBase64 = base64UrlEncode(payload)
  const signature = hmacSha256Base64url(payloadBase64, secret)
  return `${payloadBase64}.${signature}`
}
