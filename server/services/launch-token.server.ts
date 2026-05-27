// @ts-expect-error: crypto is a built-in node module
import { createHmac } from 'crypto'
import type { Muh5LaunchPayload } from '~~/types/launch'

const getLaunchSecret = (): string => {
  // @ts-expect-error: process is global in node/nitro server environment
  return process.env.MUH5_LAUNCH_SECRET || 'ccgame-default-launch-secret-key-12345'
}

export const verifyLaunchToken = (token: string): Muh5LaunchPayload | null => {
  if (!token) return null

  const parts = token.split('.')
  if (parts.length !== 2) {
    console.warn('Invalid launch token format: missing parts')
    return null
  }

  const [payloadBase64, signature] = parts
  const secret = getLaunchSecret()

  // Calculate HMAC SHA256
  const hmac = createHmac('sha256', secret)
  hmac.update(payloadBase64)
  const expectedSignature = hmac.digest('hex')

  if (signature !== expectedSignature) {
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
