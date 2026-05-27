/**
 * Local roundtrip: ccgame-web signing contract vs muh5 verification.
 * Run: bun scripts/launch-roundtrip.mjs
 */
import {
  DEV_MUH5_LAUNCH_SECRET,
  signLaunchPayload,
  verifyLaunchToken,
} from '../server/services/launch-token.server.ts'

const secret = DEV_MUH5_LAUNCH_SECRET
const now = Math.floor(Date.now() / 1000)

const payload = {
  gameId: 'muh5',
  authMode: 'guest',
  player: { id: 'guest_test', displayName: 'Test Guest' },
  server: {
    id: 1,
    key: 's1',
    name: 'S1',
    srvaddr: 'muh5-ws.ccgame.org',
    srvport: '443',
  },
  issuedAt: now,
  expiresAt: now + 300,
  nonce: 'roundtrip-test',
}

process.env.MUH5_LAUNCH_SECRET = secret

const token = signLaunchPayload(payload, secret)
const verified = verifyLaunchToken(token)

if (!verified || verified.player.id !== payload.player.id) {
  console.error('FAIL: valid token did not verify')
  process.exit(1)
}

const tampered = `${token}x`
if (verifyLaunchToken(tampered)) {
  console.error('FAIL: tampered token should reject')
  process.exit(1)
}

console.log('OK: launch token roundtrip passed')
