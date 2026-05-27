import type { UserProfile } from '~~/types/sdk'

export const getSessionUser = (): UserProfile => {
  return {
    id: 'greenjade',
    username: 'quocquoc',
    avatar: 'https://avatars.githubusercontent.com/u/739984?v=4',
  }
}
