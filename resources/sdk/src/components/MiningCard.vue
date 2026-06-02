<template>
  <section class="ccgame-sdk-mining-card" v-if="quote">
    <div class="ccgame-sdk-mining-header">
      <div class="ccgame-sdk-mining-crystal" :style="crystalStyle">
        <div class="ccgame-sdk-mining-crystal-core" />
      </div>
      <div class="ccgame-sdk-mining-title">
        <span class="ccgame-sdk-mining-label">Máy Đào KC</span>
        <strong class="ccgame-sdk-mining-rate">{{ fmt(quote.rate_per_hour) }} KC/h</strong>
      </div>
    </div>

    <div class="ccgame-sdk-mining-stats">
      <div class="ccgame-sdk-mining-stat-row">
        <span>Hiệu suất</span>
        <span :style="{ color: effColor }">{{ pct(quote.efficiency) }}%</span>
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
        class="ccgame-sdk-mining-btn ccgame-sdk-mining-btn--claim"
        :disabled="loading"
        @click="doClaim"
      >Nhận KC</button>
      <button
        class="ccgame-sdk-mining-btn"
        :disabled="loading"
        @click="doMaintain"
      >Bảo trì</button>
    </div>

    <p v-if="result" class="ccgame-sdk-mining-result">
      +{{ fmt(result.amount) }} KC
      <span v-if="result.daily_remaining !== undefined">(còn {{ fmt(result.daily_remaining) }})</span>
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
    if (!res.ok) { error.value = d.error || 'Claim thất bại.'; return }
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
    if (!res.ok) { error.value = d.error || 'Bảo trì thất bại.'; return }
    await fetchQuote()
    window.dispatchEvent(new CustomEvent('mining:maintain'))
  } catch (e) {
    error.value = 'Lỗi kết nối.'
  } finally { loading.value = false }
}

function csrf() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

const effColor = computed(() => {
  const e = quote.value?.efficiency ?? 0.35
  if (e >= 0.9) return '#4caf50'
  if (e >= 0.6) return '#c9a94e'
  return '#ff8a65'
})

const capPct = computed(() => {
  if (!quote.value) return 0
  return Math.min(100, (claimedToday.value / quote.value.daily_cap) * 100)
})

const crystalStyle = computed(() => ({
  '--eff': quote.value?.efficiency ?? 0.35,
}))

function fmt(n) { return (n || 0).toLocaleString() }
function pct(n) { return Math.round((n || 0) * 100) }

onMounted(fetchQuote)
</script>
