import { reactive, readonly } from 'vue'

const state = reactive({
  loaded: false,
  refreshing: false,
  error: null,
  server: { id: '', name: '' },
  player: { id: 0, name: '', level: 0, vip: 0 },
  wallet: { coin: 0, points: 0 },
  tabs: [],
  features: [],
  changelog: [],
  checkin: { checked_today: false, streak: 0, week: [] },
  modules: [],

  // Lazy ranking
  rankingLoaded: false,
  rankingLoading: false,
  rankingError: null,
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
    state.rankingLoading = true
    state.rankingError = null
    try {
      const res = await fetch('/api/sdk/ranking')
      if (!res.ok) throw new Error('500')
      const d = await res.json()
      state.rankingTypes = d.types || []
      state.rankingItems = d.items || {}
      state.rankingActive = d.types?.[0]?.key || ''
      state.rankingLoaded = true
    } catch {
      state.rankingError = 'Không tải được bảng xếp hạng. Thử lại sau.'
    } finally {
      state.rankingLoading = false
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
       if (data.status === 'ok') {
         // Update wallet points
         state.wallet.points += data.reward.tom
         // Update checkin state
         state.checkin.checked_today = true
         state.checkin.streak = data.streak
       }
       return data
     } catch {
       return { status: 'error', message: 'Lỗi kết nối.' }
     }
   }

   async function applyPointsReward(points) {
     state.wallet.points += points
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

  async function loadModules() {
    const u = window.ccgame?.user || state.player.name
    if (!u) return
    try {
      const res = await fetch(`/api/mining/modules?u=${encodeURIComponent(u)}`)
      if (!res.ok) throw new Error()
      state.modules = await res.json()
    } catch {
      state.modules = []
    }
  }

  async function equipModule(moduleId, slotIndex) {
    const u = window.ccgame?.user || state.player.name
    try {
      const res = await fetch('/api/mining/equip-module', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ u, module_id: moduleId, slot_index: slotIndex })
      })
      if (!res.ok) throw new Error()
      await loadModules()
      return { success: true }
    } catch {
      return { success: false, message: 'Lỗi kết nối.' }
    }
  }

  async function unequipModule(moduleId) {
    const u = window.ccgame?.user || state.player.name
    try {
      const res = await fetch('/api/mining/unequip-module', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ u, module_id: moduleId })
      })
      if (!res.ok) throw new Error()
      await loadModules()
      return { success: true }
    } catch {
      return { success: false, message: 'Lỗi kết nối.' }
    }
  }

   return {
     state: readonly(state),
     loadBootstrap,
     loadRanking,
     doCheckin,
     applyPointsReward,
     refreshWallet,
     loadModules,
     equipModule,
     unequipModule,
     setRankingActive(key) { state.rankingActive = key },
   }
}
