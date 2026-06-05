<template>
  <section class="ccgame-sdk-mining-card" v-if="quote">
    <!-- Header: Máy Đào + Level + Legacy Bonus -->
    <div class="ccgame-sdk-mining-header" style="flex-direction: column; align-items: flex-start; gap: 4px;">
      <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
        <span class="ccgame-sdk-mining-label" style="display: flex; align-items: center;">
          🏭 Lò KC 
          <span v-if="quote.is_veteran" style="background: #c9a94e; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 6px;">VETERAN</span>
          <button @click="fetchQuote" :disabled="loading" style="background: transparent; border: none; cursor: pointer; padding: 0 4px; margin-left: 4px; color: #888;" title="Làm mới">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 1 0 2.13-5.87L9 8"></path></svg>
          </button>
        </span>
        <strong>Lv {{ quote.machine_level }}</strong>
      </div>
      <div v-if="quote.legacy_bonus > 0" style="color: #c9a94e; font-size: 12px; font-weight: bold;">
        Legacy Bonus: +{{ quote.legacy_bonus }}%
      </div>
    </div>

    <!-- Rate & Efficiency -->
    <div style="margin: 12px 0;">
      <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
        <span>Rate:</span>
        <strong>{{ fmt(quote.rate_per_hour) }} 💎/h</strong>
      </div>
      <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
        <span>Hạn mức ngày:</span>
        <strong>{{ fmt(quote.today_claimed) }}/{{ fmt(quote.daily_cap) }} 💎</strong>
      </div>
      
      <div class="ccgame-sdk-mining-bar" style="margin-top: 8px;">
        <div class="ccgame-sdk-mining-bar-fill" :style="{ width: pct(quote.efficiency) + '%', background: effColor }" />
      </div>
      <div class="ccgame-sdk-mining-eff-row">
        <span>Hiệu suất</span>
        <span :style="{ color: effColor }">{{ pct(quote.efficiency) }}%</span>
      </div>
    </div>

    <!-- MODULES -->
    <div style="margin: 16px 0; border-top: 1px dashed #333; padding-top: 12px;">
      <h4 style="font-size: 12px; color: #888; margin: 0 0 8px 0;">MODULES KHẢ DỤNG: {{ state.modules.filter(m => !m.slot_index && m.slot_index !== 0).length }}</h4>
      <div style="display: flex; flex-direction: column; gap: 6px;">
        <div v-for="i in 3" :key="i" style="background: rgba(0,0,0,0.2); border-radius: 6px; padding: 6px 10px; font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
          <!-- Slot is 0-indexed: 0, 1, 2 -->
          <template v-if="(i - 1) < quote.slots_available">
            <span style="color: #bbb;">Slot {{ i - 1 }}</span>
            <span v-if="getEquippedInSlot(i - 1)" style="color: #c9a94e; flex: 1; text-align: right; margin-right: 8px;">
              {{ getEquippedInSlot(i - 1).module_type }}
            </span>
            <span v-else style="color: #666; flex: 1; text-align: right; margin-right: 8px;">Trống</span>
            
            <button v-if="getEquippedInSlot(i - 1)" @click="doUnequip(getEquippedInSlot(i - 1).id)" style="background: transparent; border: none; color: #ff4444; cursor: pointer; padding: 0 4px;" :disabled="loading">×</button>
            <button v-else-if="firstAvailableModule" @click="doEquip(firstAvailableModule.id, i - 1)" style="background: transparent; border: 1px solid #c9a94e; color: #c9a94e; border-radius: 4px; cursor: pointer; padding: 2px 8px; font-size: 11px;" :disabled="loading">Lắp</button>
          </template>
          <template v-else>
            <span style="color: #555;">Slot {{ i - 1 }} 🔒</span>
            <span style="color: #444; font-size: 11px;">Lv {{ i === 2 ? 3 : 5 }}</span>
          </template>
        </div>
      </div>
    </div>

    <!-- Tích lũy & Actions -->
    <div style="margin-top: 16px; border-top: 1px solid #222; padding-top: 12px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <span style="color: #aaa; font-size: 13px;">Tích lũy KC:</span>
        <strong style="color: #fff; font-size: 15px;">{{ fmt(quote.pending_amount) }} 💎</strong>
      </div>
      
      <div class="ccgame-sdk-mining-actions">
        <button
          class="ccgame-sdk-mining-btn"
          :class="claimBtnClass"
          :disabled="loading"
          @click="doClaim"
        >CLAIM</button>
        <button
          class="ccgame-sdk-mining-btn"
          :class="maintainBtnClass"
          :disabled="loading"
          @click="doMaintain"
        >MAINTAIN +{{ maintainGain }}%</button>
      </div>
    </div>

    <p v-if="result && result.amount > 0" class="ccgame-sdk-mining-result ccgame-sdk-mining-result--claim">
      +{{ fmt(result.amount) }} KC
    </p>
    <p v-else-if="result && result.amount === 0" class="ccgame-sdk-mining-result ccgame-sdk-mining-result--muted">
      Còn hạn mức: {{ fmt(result.daily_remaining) }} KC
    </p>

    <p v-if="!result && permaMessage" class="ccgame-sdk-mining-result ccgame-sdk-mining-result--muted">
      {{ permaMessage }}
    </p>
    <p v-if="error" class="ccgame-sdk-mining-error">{{ error }}</p>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSdkState } from '../composables/useSdkState'

const { state, refreshWallet, loadModules, equipModule, unequipModule } = useSdkState()

const quote = ref(null)
const loading = ref(false)
const result = ref(null)
const error = ref(null)

const userId = window.ccgame?.user || ''

const firstAvailableModule = computed(() => {
  return state.modules.find(m => m.slot_index === null)
})

function getEquippedInSlot(index) {
  return quote.value?.modules?.find(m => m.slot_index === index)
}

async function doEquip(moduleId, slotIndex) {
  loading.value = true; error.value = null; result.value = null
  const res = await equipModule(moduleId, slotIndex)
  if (!res.success) {
    error.value = res.message
  } else {
    await fetchQuote()
  }
  loading.value = false
}

async function doUnequip(moduleId) {
  loading.value = true; error.value = null; result.value = null
  const res = await unequipModule(moduleId)
  if (!res.success) {
    error.value = res.message
  } else {
    await fetchQuote()
  }
  loading.value = false
}

async function fetchQuote() {
  if (!userId) { quote.value = null; return }
  try {
    const res = await fetch(`/api/mining/quote?u=${encodeURIComponent(userId)}`)
    if (!res.ok) throw new Error('failed')
    quote.value = await res.json()
  } catch {
    quote.value = null
  }
}

async function doClaim() {
  loading.value = true; error.value = null; result.value = null
  try {
    const res = await fetch('/api/mining/claim', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ u: userId, server_id: 1 }),
    })
    const d = await res.json()
    if (!res.ok) {
      error.value = res.status === 401
        ? 'Phiên chơi chưa xác thực, hãy tải lại trang.'
        : (d.error || 'Chưa thể xử lý, thử lại sau.')
      return
    }
    result.value = d
    await fetchQuote()
    refreshWallet() // Reload wallet balance
    window.dispatchEvent(new CustomEvent('mining:claim'))
  } catch (e) {
    error.value = 'Lỗi kết nối.'
  } finally { loading.value = false }
}

async function doMaintain() {
  loading.value = true; error.value = null; result.value = null
  try {
    const res = await fetch('/api/mining/maintain', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ u: userId }),
    })
    const d = await res.json()
    if (!res.ok) {
      error.value = res.status === 401
        ? 'Phiên chơi chưa xác thực, hãy tải lại trang.'
        : (d.error || 'Chưa thể xử lý, thử lại sau.')
      return
    }
    await fetchQuote()
    window.dispatchEvent(new CustomEvent('mining:maintain'))
  } catch (e) {
    error.value = 'Lỗi kết nối.'
  } finally { loading.value = false }
}

function csrf() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

const maintainGain = computed(() => {
  const e = quote.value?.efficiency ?? 0.35
  return Math.round((1 - e) * 100)
})

const effColor = computed(() => {
  const e = quote.value?.efficiency ?? 0.35
  if (e >= 0.9) return '#c9a94e'
  if (e >= 0.6) return '#d4a54a'
  return '#e6a832'
})

const claimBtnClass = computed(() => {
  const e = quote.value?.efficiency ?? 0.35
  return e >= 0.7 ? 'ccgame-sdk-mining-btn--primary' : ''
})

const maintainBtnClass = computed(() => {
  const e = quote.value?.efficiency ?? 0.35
  return e < 0.7 ? 'ccgame-sdk-mining-btn--primary' : ''
})

const permaMessage = computed(() => {
  if (!quote.value) return ''
  const e = quote.value?.efficiency ?? 0.35
  if (e >= 0.98) return 'Lò đã ổn định, quay lại sau để nhận KC.'
  return ''
})

function fmt(n) { return ((n || 0) * 10).toLocaleString() }
function pct(n) { return Math.round((n || 0) * 100) }

onMounted(async () => {
  await fetchQuote()
  await loadModules()
})
</script>
