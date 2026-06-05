<template>
  <section class="mc" v-if="quote">
    <div class="mc-head">
      <div class="mc-head-left">
        <span class="mc-title">🏭 Lò KC</span>
        <span v-if="quote.is_veteran" class="mc-badge">VETERAN</span>
        <button class="mc-reload" @click.stop="doReload" :disabled="loading" title="Làm mới">
          <svg :class="{ 'mc-spin': refreshing }" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        </button>
      </div>
      <span class="mc-level">Lv {{ quote.machine_level }}</span>
    </div>
    <div v-if="quote.legacy_bonus > 0" class="mc-legacy">Legacy Bonus: +{{ quote.legacy_bonus }}%</div>
    <div class="mc-stats">
      <div class="mc-stat-row"><span>Rate</span><strong>{{ fmt(quote.rate_per_hour) }} 💎/h</strong></div>
      <div class="mc-stat-row"><span>Hạn mức ngày</span><strong>{{ fmt(quote.today_claimed) }}/{{ fmt(quote.daily_cap) }} 💎</strong></div>
    </div>
    <div class="mc-eff">
      <div class="mc-eff-bar"><div class="mc-eff-fill" :style="{ width: pct(quote.efficiency) + '%', background: effColor }" /></div>
      <div class="mc-eff-label"><span>Hiệu suất</span><span :style="{ color: effColor }">{{ pct(quote.efficiency) }}%</span></div>
    </div>
    <div class="mc-modules">
      <div class="mc-modules-head">
        <span class="mc-modules-title">MODULES</span>
        <span class="mc-modules-inv" v-if="inventoryModules.length">{{ inventoryModules.length }} khả dụng</span>
      </div>
      <div class="mc-slots">
        <div v-for="i in 3" :key="i" class="mc-slot" :class="{ 'mc-slot--locked': (i-1) >= quote.slots_available }">
          <template v-if="(i-1) < quote.slots_available">
            <div class="mc-slot-info">
              <span class="mc-slot-num">S{{ i-1 }}</span>
              <span class="mc-slot-name" v-if="getEquippedInSlot(i-1)">{{ moduleLabel(getEquippedInSlot(i-1).module_type) }}</span>
              <span class="mc-slot-empty" v-else>Trống</span>
            </div>
            <div class="mc-slot-actions">
              <button v-if="getEquippedInSlot(i-1)" class="mc-slot-remove" @click="doUnequip(getEquippedInSlot(i-1).id)" :disabled="loading">×</button>
              <select v-else-if="inventoryModules.length" class="mc-slot-select" :disabled="loading" @change="e => doEquip(Number(e.target.value), i-1)">
                <option value="">Lắp...</option>
                <option v-for="m in inventoryModules" :key="m.id" :value="m.id">{{ moduleLabel(m.module_type) }}</option>
              </select>
              <span v-else class="mc-slot-empty">—</span>
            </div>
          </template>
          <template v-else>
            <span class="mc-slot-num">S{{ i-1 }}</span>
            <span class="mc-slot-lock">🔒 Lv{{ i===2?3:5 }}</span>
          </template>
        </div>
      </div>
    </div>
    <div class="mc-actions-wrap">
      <div class="mc-pending"><span>Tích lũy KC</span><strong>{{ fmt(quote.pending_amount) }} 💎</strong></div>
      <div class="mc-btns">
        <button class="mc-btn" :class="claimBtnClass" :disabled="loading" @click="doClaim">{{ claimLoading ? '...' : 'NHẬN KC' }}</button>
        <button class="mc-btn" :class="maintainBtnClass" :disabled="loading" @click="doMaintain">{{ maintainLoading ? '...' : 'BẢO TRÌ +' + maintainGain + '%' }}</button>
      </div>
    </div>
    <transition name="mc-flash">
      <div v-if="claimFlash" class="mc-claim-flash">✅ +{{ fmt(claimFlash) }} KC đã gửi vào game!</div>
    </transition>
    <p v-if="result && result.amount === 0" class="mc-msg mc-msg--muted">Đã đạt hạn mức hôm nay</p>
    <p v-if="error" class="mc-msg mc-msg--err">{{ error }}</p>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSdkState } from '../composables/useSdkState'

const { state, refreshWallet, loadModules, equipModule, unequipModule } = useSdkState()
const quote = ref(null)
const loading = ref(false)
const claimLoading = ref(false)
const maintainLoading = ref(false)
const refreshing = ref(false)
const result = ref(null)
const error = ref(null)
const claimFlash = ref(null)
const userId = window.ccgame?.user || ''

const MODULE_LABELS = {
  speed_core: '⚡ Tốc độ',
  durability_plate: '🛡️ Bền bỉ',
  overflow_tank: '💰 Nạp thêm',
  lucky_crystal: '🎲 May mắn',
}
const moduleLabel = type => MODULE_LABELS[type] || type
const inventoryModules = computed(() => state.modules.filter(m => m.slot_index === null || m.slot_index === undefined))
const getEquippedInSlot = index => quote.value?.modules?.find(m => Number(m.slot_index) === index)

async function doReload() {
  refreshing.value = true; error.value = null; result.value = null; claimFlash.value = null
  await Promise.all([fetchQuote(), loadModules()])
  setTimeout(() => { refreshing.value = false }, 600)
}
async function fetchQuote() {
  if (!userId) { quote.value = null; return }
  try {
    const res = await fetch(`/api/mining/quote?u=${encodeURIComponent(userId)}`)
    if (!res.ok) throw new Error()
    quote.value = await res.json()
  } catch { quote.value = null }
}
async function doEquip(moduleId, slotIndex) {
  if (!moduleId) return
  loading.value = true; error.value = null
  const res = await equipModule(moduleId, slotIndex)
  if (!res.success) error.value = res.message || 'Không thể lắp module.'
  await fetchQuote()
  loading.value = false
}
async function doUnequip(moduleId) {
  loading.value = true; error.value = null
  const res = await unequipModule(moduleId)
  if (!res.success) error.value = res.message || 'Không thể tháo module.'
  await fetchQuote()
  loading.value = false
}
async function doClaim() {
  loading.value = true; claimLoading.value = true; error.value = null; result.value = null; claimFlash.value = null
  try {
    const res = await fetch('/api/mining/claim', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ u: userId, server_id: 1 }),
    })
    const d = await res.json()
    if (!res.ok) { error.value = res.status === 401 ? 'Phiên chơi chưa xác thực, hãy tải lại trang.' : (d.error || 'Chưa thể xử lý, thử lại sau.'); return }
    result.value = d
    if (d.amount > 0) { claimFlash.value = d.amount; setTimeout(() => { claimFlash.value = null }, 4000) }
    await fetchQuote()
    refreshWallet()
    window.dispatchEvent(new CustomEvent('mining:claim'))
  } catch { error.value = 'Lỗi kết nối.' }
  finally { loading.value = false; claimLoading.value = false }
}
async function doMaintain() {
  loading.value = true; maintainLoading.value = true; error.value = null; result.value = null; claimFlash.value = null
  try {
    const res = await fetch('/api/mining/maintain', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ u: userId }),
    })
    const d = await res.json()
    if (!res.ok) { error.value = res.status === 401 ? 'Phiên chơi chưa xác thực, hãy tải lại trang.' : (d.error || 'Chưa thể xử lý, thử lại sau.'); return }
    await fetchQuote()
    window.dispatchEvent(new CustomEvent('mining:maintain'))
  } catch { error.value = 'Lỗi kết nối.' }
  finally { loading.value = false; maintainLoading.value = false }
}
function csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }

const maintainGain = computed(() => Math.round((1 - (quote.value?.efficiency ?? 0.35)) * 100))
const effColor = computed(() => { const e = quote.value?.efficiency ?? 0.35; return e >= 0.9 ? '#4caf50' : e >= 0.6 ? '#c9a94e' : '#e6543a' })
const claimBtnClass = computed(() => (quote.value?.efficiency ?? 0) >= 0.7 ? 'mc-btn--primary' : '')
const maintainBtnClass = computed(() => (quote.value?.efficiency ?? 1) < 0.7 ? 'mc-btn--primary' : '')
function fmt(n) { return ((n || 0) * 10).toLocaleString() }
function pct(n) { return Math.round((n || 0) * 100) }

onMounted(async () => { await Promise.all([fetchQuote(), loadModules()]) })
</script>

<style scoped>
.mc { background: #12121d; border: 1px solid #1e1e32; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; overflow: hidden; }
.mc-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.mc-head-left { display: flex; align-items: center; gap: 6px; min-width: 0; }
.mc-title { font-size: 11px; font-weight: 700; color: #e2e2f0; white-space: nowrap; }
.mc-badge { background: #c9a94e; color: #0d0d14; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: 700; letter-spacing: 0.04em; flex-shrink: 0; }
.mc-level { font-size: 11px; font-weight: 700; color: #c9a94e; flex-shrink: 0; }
.mc-legacy { color: #c9a94e; font-size: 10px; font-weight: 600; margin-bottom: 6px; }
.mc-reload { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 4px; border: none; background: transparent; color: #6a6a8a; cursor: pointer; padding: 0; flex-shrink: 0; transition: color 0.15s, background 0.15s; }
.mc-reload:hover { color: #c9a94e; background: rgba(201,169,78,0.1); }
.mc-reload:disabled { opacity: 0.4; cursor: not-allowed; }
.mc-spin { animation: spin 0.6s ease; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.mc-stats { margin: 8px 0 6px; }
.mc-stat-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #8a8aaa; padding: 2px 0; }
.mc-stat-row strong { color: #e2e2f0; font-variant-numeric: tabular-nums; }
.mc-eff { margin: 6px 0 10px; }
.mc-eff-bar { height: 3px; background: #1a1a2e; border-radius: 2px; overflow: hidden; margin-bottom: 4px; }
.mc-eff-fill { height: 100%; border-radius: 2px; transition: width 0.4s ease; }
.mc-eff-label { display: flex; justify-content: space-between; font-size: 9px; color: #6a6a8a; }
.mc-modules { border-top: 1px solid #1a1a2e; padding-top: 8px; margin-bottom: 8px; }
.mc-modules-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.mc-modules-title { font-size: 9px; font-weight: 700; color: #5a5a7a; letter-spacing: 0.08em; }
.mc-modules-inv { font-size: 9px; color: #c9a94e; font-weight: 600; }
.mc-slots { display: flex; flex-direction: column; gap: 4px; }
.mc-slot { display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.2); border-radius: 5px; padding: 5px 8px; font-size: 11px; min-height: 28px; }
.mc-slot--locked { opacity: 0.4; }
.mc-slot-info { display: flex; align-items: center; gap: 6px; flex: 1; min-width: 0; }
.mc-slot-num { font-size: 9px; color: #5a5a7a; font-weight: 700; flex-shrink: 0; }
.mc-slot-name { color: #c9a94e; font-size: 11px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mc-slot-empty { color: #3a3a5a; font-size: 11px; }
.mc-slot-lock { color: #3a3a5a; font-size: 10px; margin-left: 6px; }
.mc-slot-actions { flex-shrink: 0; margin-left: 8px; }
.mc-slot-remove { background: transparent; border: none; color: #e6543a; font-size: 14px; cursor: pointer; padding: 0 4px; line-height: 1; }
.mc-slot-remove:disabled { opacity: 0.4; cursor: not-allowed; }
.mc-slot-remove:hover:not(:disabled) { color: #ff6b55; }
.mc-slot-select { background: #1a1a2e; border: 1px solid #2a2a3d; border-radius: 4px; color: #8a8aaa; font-size: 10px; padding: 2px 4px; cursor: pointer; max-width: 110px; }
.mc-slot-select:disabled { opacity: 0.4; cursor: not-allowed; }
.mc-actions-wrap { border-top: 1px solid #1a1a2e; padding-top: 8px; }
.mc-pending { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 12px; color: #8a8aaa; }
.mc-pending strong { color: #fff; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; }
.mc-btns { display: flex; gap: 6px; }
.mc-btn { flex: 1; padding: 7px 0; border-radius: 6px; border: 1px solid #2a2a3d; background: #1a1a2a; color: #8a8aaa; font-size: 10px; font-weight: 700; cursor: pointer; text-transform: uppercase; letter-spacing: 0.05em; transition: background 0.15s, color 0.15s; }
.mc-btn:hover:not(:disabled) { background: #22223a; }
.mc-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.mc-btn:active:not(:disabled) { transform: scale(0.97); }
.mc-btn--primary { background: #c9a94e; color: #0d0d14; border-color: #c9a94e; box-shadow: 0 2px 10px rgba(201,169,78,0.2); }
.mc-btn--primary:hover:not(:disabled) { background: #d4b55e; }
.mc-claim-flash { margin-top: 8px; padding: 6px 10px; background: rgba(76,175,80,0.12); border: 1px solid rgba(76,175,80,0.3); border-radius: 6px; color: #4caf50; font-size: 11px; font-weight: 600; text-align: center; }
.mc-flash-enter-active { animation: flash-in 0.3s ease; }
.mc-flash-leave-active { animation: flash-out 0.3s ease; }
@keyframes flash-in { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
@keyframes flash-out { from { opacity: 1; } to { opacity: 0; } }
.mc-msg { font-size: 10px; text-align: center; margin-top: 6px; font-weight: 600; }
.mc-msg--muted { color: #5a5a7a; font-weight: 500; }
.mc-msg--err { color: #f44336; font-size: 9px; }
</style>
