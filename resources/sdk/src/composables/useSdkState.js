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

  // Lazy pshop (Tom items)
  pshopItems: [],
  pshopLoaded: false,
  pshopLoading: false,
  pshopError: null,

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

  async function loadPshopItems() {
    if (state.pshopLoaded || state.pshopLoading) return
    state.pshopLoading = true
    state.pshopError = null
    try {
      const u = window.ccgame?.user || state.player.name || ''
      const sid = state.server.id || ''
      const res = await fetch(`/api/pshop/items?u=${encodeURIComponent(u)}&server_id=${encodeURIComponent(sid)}`)
      if (!res.ok) throw new Error()
      const d = await res.json()
      state.pshopItems = d.items || []
      state.pshopLoaded = true
    } catch {
      state.pshopError = 'Không tải được cửa hàng Tôm, thử lại sau.'
    } finally {
      state.pshopLoading = false
    }
  }

  async function buyWithTom(itemId) {
    const u = window.ccgame?.user || state.player.name
    if (!u) return { success: false, message: 'Phiên chơi chưa xác thực, hãy tải lại trang.' }
    try {
      const res = await fetch('/api/pshop/buy-tom', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ u, item_id: itemId, server_id: state.server.id || 1 }),
      })
      const data = await res.json()
      if (res.ok && data.success) {
        // Refresh purchased/limit state and wallet after a successful buy.
        state.pshopLoaded = false
        await loadPshopItems()
        await refreshWallet()
        return { success: true, message: data.message || 'Đổi Tôm thành công!', remaining_tom: data.remaining_tom }
      }
      return { success: false, message: data.error || 'Đổi Tôm thất bại, thử lại sau.' }
    } catch {
      return { success: false, message: 'Lỗi kết nối, thử lại sau.' }
    }
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
     loadPshopItems,
     buyWithTom,
     setRankingActive(key) { state.rankingActive = key },
   }
}
