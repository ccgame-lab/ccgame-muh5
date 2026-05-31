export type Muh5LaunchPayload = {
  gameId: 'muh5'
  authMode: 'guest' | 'greenjade'
  player: {
    id: string
    /** Legacy game account name (users.username); required for trusted /play launch */
    username: string
    /** Legacy WS verify string (globaluser.passwd / users.password) */
    spverify: string
    displayName: string
    /** Optional ≤6-char create-role hint (legacy client nickName query). */
    suggestedCharacterName?: string
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
