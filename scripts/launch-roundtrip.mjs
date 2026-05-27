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
  player: {
    id: 'guest_test',
    username: 'guest_a3a9e1ed',
    spverify: 'portal-auth',
    displayName: 'Test Guest',
  },
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

if (!verified.player.username) {
  console.error('FAIL: verified token missing player.username')
  process.exit(1)
}

const missingUsernamePayload = {
  ...payload,
  player: { id: 'guest_no_username', spverify: 'portal-auth', displayName: 'Bad' },
  nonce: 'roundtrip-missing-username',
}
const badToken = signLaunchPayload(missingUsernamePayload, secret)
const badVerified = verifyLaunchToken(badToken)
const badUsername = badVerified?.player?.username?.trim()
if (badVerified && badUsername) {
  console.error('FAIL: test token unexpectedly has username')
  process.exit(1)
}
// Mirrors session.server.ts: signed token without username => invalid_launch
if (badVerified && !badUsername) {
  // expected
}
else if (!badVerified) {
  console.error('FAIL: token without username should still verify crypto; check payload shape')
  process.exit(1)
}

const tampered = `${token}x`
if (verifyLaunchToken(tampered)) {
  console.error('FAIL: tampered token should reject')
  process.exit(1)
}

console.log('OK: launch token roundtrip passed')
