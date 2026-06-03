import { reactive, readonly } from 'vue'

const state = reactive({
  loaded: false,
  refreshing: false,
  error: null,
  server: { id: '', name: '' },
  player: { id: 0, name: '', level: 0, vip: 0 },
  wallet: { tom: 0, wpoint: 0 },
  tabs: [],
  features: [],
  changelog: [],
  checkin: { checked_today: false, streak: 0, week: [] },

  // Lazy ranking
  rankingLoaded: false,
  rankingTypes: [],
  rankingItems: {},
  rankingActive: '',
})

export function useSdkState() {
  async function loadBootstrap() {
    try {
      const u = window.ccgame?.user || ''
      const url = u ? `/api/sdk/bootstrap?u=${encodeURIComponent(u)}` : '/api/sdk/bootstrap'
      const res = await fetch(url)
      if (res.status === 401) throw new Error('401')
      if (!res.ok) throw new Error('500')
      const d = await res.json()
      Object.assign(state, {
        server: d.server || state.server,
        player: d.player || state.player,
        wallet: d.wallet || state.wallet,
        tabs: d.tabs || [],
        features: d.features || [],
        changelog: d.changelog || [],
        checkin: d.checkin || { checked_today: false, streak: 0, week: [] },
        loaded: true,
        error: null,
      })
    } catch (e) {
      state.error = e.message === '401'
        ? 'Phiên chơi đã hết hạn. Vui lòng đăng nhập lại.'
        : 'Không tải được dữ liệu. F5 trang hoặc thử lại sau.'
    }
  }

  async function loadRanking() {
    if (state.rankingLoaded) return
    try {
      const res = await fetch('/api/sdk/ranking')
      if (!res.ok) throw new Error('500')
      const d = await res.json()
      state.rankingTypes = d.types || []
      state.rankingItems = d.items || {}
      state.rankingActive = d.types?.[0]?.key || ''
      state.rankingLoaded = true
    } catch {
      // silently fail, keep stub
    }
  }

  async function doCheckin() {
    const u = window.ccgame?.user || state.player.name
    if (!u) return { status: 'error', message: 'Chưa xác thực.' }
    try {
      const res = await fetch(`/api/sdk/checkin?u=${encodeURIComponent(u)}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf() },
      })
      const data = await res.json()
      // refresh bootstrap to update checkin state + wallet
      await loadBootstrap()
      return data
    } catch {
      return { status: 'error', message: 'Lỗi kết nối.' }
    }
  }

  let _refreshing = false

  async function refreshWallet() {
    if (_refreshing) return
    _refreshing = true
    state.refreshing = true
    try {
      const u = window.ccgame?.user || ''
      const url = u ? `/api/sdk/bootstrap?u=${encodeURIComponent(u)}` : '/api/sdk/bootstrap'
      const res = await fetch(url)
      if (res.status === 401) throw new Error('401')
      if (!res.ok) throw new Error('500')
      const d = await res.json()
      if (d.wallet) state.wallet = d.wallet
      if (d.player) state.player = d.player
    } catch {
      // silent — refresh failures don't disturb UX
    } finally {
      state.refreshing = false
      _refreshing = false
    }
  }

  function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  }

  return {
    state: readonly(state),
    loadBootstrap,
    loadRanking,
    doCheckin,
    refreshWallet,
    setRankingActive(key) { state.rankingActive = key },
  }
}
