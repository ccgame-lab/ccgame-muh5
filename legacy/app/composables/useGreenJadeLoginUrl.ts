/**
 * GreenJade SSO entry via CCGame portal (parent frame). No token in URL.
 */
export function useGreenJadeLoginUrl(returnTo = '/play/muh5') {
  const runtimeConfig = useRuntimeConfig()

  return computed(() => {
    const base = String(runtimeConfig.public.ccgamePortalUrl || 'https://ccgame.org').replace(/\/+$/, '')
    return `${base}/api/auth/greenjade/start?returnTo=${encodeURIComponent(returnTo)}`
  })
}
