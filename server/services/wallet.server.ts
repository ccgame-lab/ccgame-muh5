/**
 * Read-only wallet balance from portal DB via verified launch session.
 */
import type { H3Event } from 'h3'
import { resolveSdkSession } from './sdk-session.server'
import type { SdkReadReason, WalletReadResult } from '~~/types/sdk'

const sealed = (reason: SdkReadReason): WalletReadResult => ({
  sealed: true,
  reason,
  balance: { wcoin: null, wpoint: null },
})

export const readWalletForSession = async (event: H3Event): Promise<WalletReadResult> => {
  const session = await resolveSdkSession(event)
  if (!session.ok) {
    return sealed(session.reason)
  }

  return {
    sealed: false,
    balance: {
      wcoin: session.user.wcoin,
      wpoint: session.user.wpoint,
    },
  }
}
