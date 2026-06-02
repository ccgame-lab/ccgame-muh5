<template>
  <section class="ccgame-sdk-mining-card" v-if="quote">
    <div class="ccgame-sdk-mining-header">
      <div class="ccgame-sdk-mining-core" :style="coreStyle">
        <div class="ccgame-sdk-mining-core-inner" />
        <div class="ccgame-sdk-mining-core-glow" />
      </div>
      <div class="ccgame-sdk-mining-title">
        <span class="ccgame-sdk-mining-label">LÒ KC</span>
        <strong class="ccgame-sdk-mining-rate">{{ fmt(quote.rate_per_hour) }} KC/h</strong>
      </div>
    </div>

    <div class="ccgame-sdk-mining-stats">
      <div class="ccgame-sdk-mining-stat-row">
        <span>Hiệu suất lò</span>
        <span class="ccgame-sdk-mining-eff" :style="{ color: effColor }">{{ pct(quote.efficiency) }}%</span>
      </div>
      <div class="ccgame-sdk-mining-bar">
        <div class="ccgame-sdk-mining-bar-fill" :style="{ width: pct(quote.efficiency) + '%', background: effColor }" />
      </div>

      <div class="ccgame-sdk-mining-stat-row">
        <span>Hôm nay</span>
        <span>{{ fmt(claimedToday) }} / {{ fmt(quote.daily_cap) }}</span>
      </div>
      <div class="ccgame-sdk-mining-bar">
        <div class="ccgame-sdk-mining-bar-fill" :style="{ width: capPct + '%', background: '#c9a94e' }" />
      </div>

      <p v-if="quote.boost_multiplier > 1" class="ccgame-sdk-mining-boost">
        Boost x{{ quote.boost_multiplier }} đang chạy
      </p>
    </div>

    <div class="ccgame-sdk-mining-actions">
      <button
        class="ccgame-sdk-mining-btn"
        :class="claimBtnClass"
        :disabled="loading"
        @click="doClaim"
      >Nhận KC</button>
      <button
        class="ccgame-sdk-mining-btn"
        :class="maintainBtnClass"
        :disabled="loading"
        @click="doMaintain"
      >Bảo trì +{{ maintainGain }}%</button>
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

const quote = ref(null)
const claimedToday = ref(0)
const loading = ref(false)
const result = ref(null)
const error = ref(null)

const userId = window.ccgame?.user || ''

async function fetchQuote() {
  try {
    const res = await fetch(`/api/mining/quote?u=${encodeURIComponent(userId)}`)
    if (!res.ok) throw new Error('failed')
    quote.value = await res.json()
    claimedToday.value = quote.value.daily_cap - (quote.value._daily_remaining ?? quote.value.daily_cap)
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

const capPct = computed(() => {
  if (!quote.value) return 0
  return Math.min(100, (claimedToday.value / quote.value.daily_cap) * 100)
})

const coreStyle = computed(() => ({
  '--eff': quote.value?.efficiency ?? 0.35,
}))

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

function fmt(n) { return (n || 0).toLocaleString() }
function pct(n) { return Math.round((n || 0) * 100) }

onMounted(fetchQuote)
</script>
