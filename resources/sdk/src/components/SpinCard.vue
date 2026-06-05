<template>
  <div class="ccsdk-spin">
    <div class="ccsdk-spin-header">
      <span class="ccsdk-spin-title">Vòng Quay May Mắn</span>
      <span class="ccsdk-spin-badge" :class="{ 'ccsdk-spin-badge--empty': spinsRemaining === 0 }">
        {{ spinsRemaining }}/{{ spinStatus.daily_limit }} lượt
      </span>
    </div>

    <!-- Wheel -->
    <div class="ccsdk-wheel-wrap">
      <div class="ccsdk-wheel-pointer"></div>
      <div ref="wheelContainer" class="ccsdk-wheel-canvas"></div>

      <!-- Result overlay (fades in after wheel stops) -->
      <transition name="ccsdk-result-fade">
        <div v-if="resultVisible" class="ccsdk-wheel-result" :class="resultClass">
          <span class="ccsdk-wheel-result-icon">{{ resultIcon }}</span>
          <span class="ccsdk-wheel-result-text">{{ resultText }}</span>
        </div>
      </transition>
    </div>

    <!-- Milestone bar -->
    <div class="ccsdk-spin-milestones">
      <div class="ccsdk-spin-bar-wrap">
        <div class="ccsdk-spin-bar-fill" :style="{ width: milestoneWidth + '%' }"></div>
        <div class="ccsdk-spin-bar-marker" style="left:50%"><span>10</span></div>
        <div class="ccsdk-spin-bar-marker" style="left:100%"><span>20</span></div>
      </div>
      <div class="ccsdk-spin-bar-label">
        <span>{{ spinStatus.spins_today }} lượt hôm nay</span>
        <span v-if="nextMilestone" class="ccsdk-spin-bar-hint">thưởng tại lượt {{ nextMilestone }}</span>
      </div>
    </div>

    <!-- Action row -->
    <div class="ccsdk-spin-action">
      <span class="ccsdk-spin-cost" :class="{ 'ccsdk-spin-cost--free': spinStatus.has_free_spin }">
        {{ spinStatus.has_free_spin ? 'FREE' : spinStatus.next_cost + ' POINT' }}
      </span>
      <button
        class="ccsdk-spin-btn"
        :class="{ 'ccsdk-spin-btn--active': canSpin && !isAnimating }"
        :disabled="isAnimating || spinsRemaining === 0"
        @click="onSpin"
      >
        <span v-if="isAnimating" class="ccsdk-spin-btn-spinner">◌</span>
        <span v-else-if="spinsRemaining === 0">Hết lượt</span>
        <span v-else>🔥 QUAY</span>
      </button>
    </div>

    <!-- Toast -->
    <transition name="ccsdk-toast">
      <div v-if="toastMsg" class="ccsdk-spin-toast" :class="toastClass">{{ toastMsg }}</div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Wheel } from 'spin-wheel'
import { useSdkState } from '../composables/useSdkState.js'

const { state, loadSpinStatus, doSpin } = useSdkState()
const wheelContainer = ref(null)
let wheelInstance = null

const isAnimating = ref(false)
const resultVisible = ref(false)
const resultClass = ref('')
const resultIcon = ref('')
const resultText = ref('')
const toastMsg = ref('')
const toastClass = ref('')

const SPIN_DURATION = 4000
const WHEEL_COLORS = ['#b91c1c', '#ea580c', '#ca8a04', '#15803d', '#4338ca', '#0369a1', '#be185d', '#7c3aed', '#0f766e']

const spinStatus = computed(() => state.spinStatus)
const spinsRemaining = computed(() => spinStatus.value.spins_remaining ?? 0)
const canSpin = computed(() => spinsRemaining.value > 0)

const milestoneWidth = computed(() => {
  const today = spinStatus.value.spins_today ?? 0
  const limit = spinStatus.value.daily_limit || 20
  return Math.min(100, (today / limit) * 100)
})

const nextMilestone = computed(() => {
  const today = spinStatus.value.spins_today ?? 0
  return [10, 20].find(m => today < m) ?? null
})

function buildCenterOverlay() {
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="14" fill="%23facc15"/><circle cx="50" cy="50" r="9" fill="%23ca8a04"/></svg>`
  return 'data:image/svg+xml;utf8,' + svg
}

function initWheel(prizes) {
  if (!wheelContainer.value || !prizes?.length || wheelInstance) return
  const items = prizes.map((p, i) => ({
    label: p.label,
    backgroundColor: WHEEL_COLORS[i % WHEEL_COLORS.length],
    labelColor: '#ffffff',
  }))
  wheelInstance = new Wheel(wheelContainer.value, {
    items,
    lineWidth: 2,
    lineColor: '#facc15',
    radius: 0.9,
    innerRadius: 0.12,
    pointerAngle: 0,
    isInteractive: false,
    itemLabelFont: 'Arial Bold',
    itemLabelFontSizeMax: 18,
    itemLabelRadius: 0.78,
    overlayImage: buildCenterOverlay(),
  })
}

// Initialize wheel when prizes arrive
watch(() => state.spinStatus.prizes, (prizes) => {
  if (prizes?.length && !wheelInstance) {
    initWheel(prizes)
  }
}, { immediate: true })

function showResult(data) {
  const type = data.prize_type
  if (type === 'lose_turn') {
    resultClass.value = 'ccsdk-wheel-result--lose'
    resultIcon.value = '✗'
    resultText.value = 'Mất lượt'
  } else if (type === 'extra_turn') {
    resultClass.value = 'ccsdk-wheel-result--extra'
    resultIcon.value = '↻'
    resultText.value = 'Thêm lượt!'
  } else if (type === 'wcoin') {
    resultClass.value = 'ccsdk-wheel-result--win'
    resultIcon.value = '⭐'
    resultText.value = `+${data.prize_value} POINT`
  } else if (type === 'yuanbao') {
    resultClass.value = 'ccsdk-wheel-result--win'
    resultIcon.value = '💎'
    resultText.value = `+${data.prize_value * 1000} KC`
  }
  resultVisible.value = true
  setTimeout(() => { resultVisible.value = false }, 3000)

  if (data.milestone_bonus) {
    showToast(`+${data.milestone_bonus} POINT thưởng cột mốc!`, 'ccsdk-spin-toast--milestone')
  } else if (type === 'extra_turn') {
    showToast('Lượt quay thêm!', 'ccsdk-spin-toast--extra')
  }
}

function showToast(msg, cls) {
  toastMsg.value = msg
  toastClass.value = cls
  setTimeout(() => { toastMsg.value = '' }, 2800)
}

async function onSpin() {
  if (isAnimating.value || !wheelInstance || spinsRemaining.value === 0) return
  isAnimating.value = true
  resultVisible.value = false

  const result = await doSpin()

  if (!result.success) {
    isAnimating.value = false
    showToast(result.message || 'Lỗi vòng quay', 'ccsdk-spin-toast--error')
    return
  }

  wheelInstance.spinToItem(result.prize_index, SPIN_DURATION, true, 4, 1)

  setTimeout(() => {
    isAnimating.value = false
    showResult(result)
    if (result.extra_spin) {
      setTimeout(() => onSpin(), 2400)
    }
  }, SPIN_DURATION + 200)
}

onMounted(async () => {
  if (!state.spinStatusLoaded && !state.spinStatusLoading) {
    await loadSpinStatus()
  }
  if (state.spinStatus.prizes?.length) {
    initWheel(state.spinStatus.prizes)
  }
})

onBeforeUnmount(() => {
  wheelInstance = null
})
</script>

<style scoped>
.ccsdk-spin {
  background: #161626;
  border: 1px solid rgba(120, 100, 255, 0.18);
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 12px;
  position: relative;
  overflow: hidden;
}

/* ── Header ── */
.ccsdk-spin-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.ccsdk-spin-title {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #8888aa;
}

.ccsdk-spin-badge {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  background: rgba(124, 111, 247, 0.2);
  color: #9c8fff;
  border: 1px solid rgba(124, 111, 247, 0.3);
}

.ccsdk-spin-badge--empty {
  background: rgba(100, 100, 120, 0.2);
  color: #666688;
  border-color: rgba(100, 100, 120, 0.3);
}

/* ── Wheel ── */
.ccsdk-wheel-wrap {
  position: relative;
  width: 240px;
  height: 240px;
  margin: 0 auto 10px;
  filter: drop-shadow(0 0 14px rgba(250, 204, 21, 0.12));
}

.ccsdk-wheel-pointer {
  position: absolute;
  top: -10px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
  width: 0;
  height: 0;
  border-left: 12px solid transparent;
  border-right: 12px solid transparent;
  border-top: 24px solid #dc2626;
  filter: drop-shadow(0 3px 4px rgba(0, 0, 0, 0.7));
}

.ccsdk-wheel-canvas {
  width: 240px;
  height: 240px;
  border-radius: 50%;
  overflow: hidden;
}

/* Result overlay */
.ccsdk-wheel-result {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  pointer-events: none;
  backdrop-filter: blur(1px);
}

.ccsdk-wheel-result--win {
  background: radial-gradient(circle, rgba(255, 200, 50, 0.88) 0%, rgba(180, 130, 10, 0.72) 100%);
}

.ccsdk-wheel-result--extra {
  background: radial-gradient(circle, rgba(50, 220, 150, 0.88) 0%, rgba(10, 120, 80, 0.72) 100%);
}

.ccsdk-wheel-result--lose {
  background: radial-gradient(circle, rgba(40, 40, 60, 0.82) 0%, rgba(20, 20, 40, 0.72) 100%);
}

.ccsdk-wheel-result-icon {
  font-size: 28px;
  line-height: 1;
}

.ccsdk-wheel-result-text {
  font-size: 13px;
  font-weight: 800;
  color: #fff;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
  letter-spacing: 0.04em;
}

.ccsdk-result-fade-enter-active { animation: ccsdk-result-pop 0.3s ease-out; }
.ccsdk-result-fade-leave-active { transition: opacity 0.4s; }
.ccsdk-result-fade-leave-to { opacity: 0; }

@keyframes ccsdk-result-pop {
  0% { opacity: 0; transform: scale(0.8); }
  70% { transform: scale(1.06); }
  100% { opacity: 1; transform: scale(1); }
}

/* ── Milestone bar ── */
.ccsdk-spin-milestones { margin-bottom: 8px; }

.ccsdk-spin-bar-wrap {
  position: relative;
  height: 6px;
  background: rgba(255, 255, 255, 0.07);
  border-radius: 3px;
  overflow: visible;
  margin-bottom: 4px;
}

.ccsdk-spin-bar-fill {
  height: 100%;
  border-radius: 3px;
  background: linear-gradient(90deg, #7c6ff7, #9c8fff);
  transition: width 0.4s ease;
}

.ccsdk-spin-bar-marker {
  position: absolute;
  top: -3px;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.ccsdk-spin-bar-marker::before {
  content: '';
  width: 2px;
  height: 12px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 1px;
}

.ccsdk-spin-bar-marker span {
  font-size: 8px;
  color: #5a5a7a;
  font-weight: 600;
  margin-top: 2px;
}

.ccsdk-spin-bar-label {
  display: flex;
  justify-content: space-between;
  font-size: 9px;
  color: #5a5a7a;
  margin-top: 14px;
}

.ccsdk-spin-bar-hint { color: #7c6ff7; }

/* ── Action row ── */
.ccsdk-spin-action {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ccsdk-spin-cost {
  font-size: 10px;
  font-weight: 700;
  color: #8888aa;
  flex: 1;
}

.ccsdk-spin-cost--free {
  color: #32dc96;
  animation: ccsdk-free-pulse 1.4s ease-in-out infinite;
}

.ccsdk-spin-btn {
  padding: 7px 18px;
  border: none;
  border-radius: 6px;
  background: #333355;
  color: #666688;
  font-size: 11px;
  font-weight: 700;
  cursor: default;
  letter-spacing: 0.05em;
  min-width: 72px;
  text-align: center;
  transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
}

.ccsdk-spin-btn--active {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
  cursor: pointer;
  box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.6);
  animation: ccsdk-spin-pulse 2s infinite;
}

.ccsdk-spin-btn--active:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(245, 158, 11, 0.5);
  animation: none;
}

.ccsdk-spin-btn:disabled { opacity: 0.5; cursor: not-allowed; animation: none; }

.ccsdk-spin-btn-spinner {
  display: inline-block;
  animation: ccsdk-rotate 0.7s linear infinite;
}

/* ── Toast ── */
.ccsdk-spin-toast {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  pointer-events: none;
}

.ccsdk-spin-toast--milestone { background: rgba(240, 168, 32, 0.92); color: #1a1208; }
.ccsdk-spin-toast--extra     { background: rgba(50, 220, 150, 0.92); color: #0a2a1a; }
.ccsdk-spin-toast--error     { background: rgba(220, 80, 80, 0.92); color: #fff; }

.ccsdk-toast-enter-active { animation: ccsdk-toast-in 0.25s ease-out; }
.ccsdk-toast-leave-active { animation: ccsdk-toast-out 0.3s ease-in forwards; }

/* ── Keyframes ── */
@keyframes ccsdk-spin-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.6); }
  70%  { box-shadow: 0 0 0 12px rgba(245, 158, 11, 0); }
  100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

@keyframes ccsdk-rotate { to { transform: rotate(360deg); } }

@keyframes ccsdk-free-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.55; }
}

@keyframes ccsdk-toast-in {
  from { opacity: 0; transform: translateX(-50%) translateY(8px); }
  to   { opacity: 1; transform: translateX(-50%) translateY(0); }
}

@keyframes ccsdk-toast-out {
  from { opacity: 1; }
  to   { opacity: 0; transform: translateX(-50%) translateY(-4px); }
}
</style>
