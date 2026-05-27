export type Muh5LaunchPayload = {
  gameId: 'muh5'
  authMode: 'guest' | 'greenjade'
  player: {
    id: string
    username?: string
    displayName: string
  }
  server: {
    id: number
    key: 's1'
    name: string
    srvaddr: string
    srvport: string
  }
  issuedAt: number
  expiresAt: number
  nonce: string
}
