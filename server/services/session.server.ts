import type { UserProfile } from '~~/types/sdk'

export const getSessionUser = (): UserProfile => {
  return {
    id: 'user_mock_123',
    username: 'muh5_player_01',
    avatar: 'https://avatars.githubusercontent.com/u/739984?v=4',
  }
}
