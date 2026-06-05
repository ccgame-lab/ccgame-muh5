<template>
  <div class="ccsdk-spin">
    <div class="ccsdk-spin-header">
      <span class="ccsdk-spin-title">Vòng Quay May Mắn</span>
      <span class="ccsdk-spin-badge" :class="spinsRemaining === 0 && 'ccsdk-spin-badge--empty'">
        {{ spinsRemaining }}/{{ spinStatus.daily_limit }} lượt
      </span>
    </div>

    <!-- Prize display -->
    <div class="ccsdk-spin-display" :class="displayClass" @animationend="clearDisplay">
      <span class="ccsdk-spin-display-icon">{{ displayIcon }}</span>
      <span class="ccsdk-spin-display-label">{{ displayLabel }}</span>
    </div>

    <!-- Milestone bar -->
    <div class="ccsdk-spin-milestones">
      <div class="ccsdk-spin-bar-wrap">
        <div class="ccsdk-spin-bar-fill" :style="{ width: milestoneWidth + '%' }"></div>
        <div class="ccsdk-spin-bar-marker" style="left:50%">
          <span>10</span>
        </div>
        <div class="ccsdk-spin-bar-marker" style="left:100%">
          <span>20</span>
        </div>
      </div>
      <div class="ccsdk-spin-bar-label">
        <span>{{ spinStatus.spins_today }} lượt hôm nay</span>
        <span v-if="nextMilestone" class="ccsdk-spin-bar-hint">thưởng tại lượt {{ nextMilestone }}</span>
      </div>
    </div>

    <!-- Action row -->
    <div class="ccsdk-spin-action">
      <span class="ccsdk-spin-cost" :class="spinStatus.has_free_spin && 'ccsdk-spin-cost--free'">
        {{ spinStatus.has_free_spin ? 'FREE' : spinStatus.next_cost + ' POINT' }}
      </span>
      <button
        class="ccsdk-spin-btn"
        :class="{ 'ccsdk-spin-btn--spinning': spinning }"
        :disabled="spinning || spinsRemaining === 0"
        @click="onSpin"
      >
        <span v-if="spinning" class="ccsdk-spin-btn-spinner">◌</span>
        <span v-else-if="spinsRemaining === 0">Hết lượt</span>
        <span v-else>QUAY</span>
      </button>
    </div>

    <!-- Toast result -->
    <transition name="ccsdk-toast">
      <div v-if="toastMsg" class="ccsdk-spin-toast" :class="toastClass">{{ toastMsg }}</div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSdkState } from '../composables/useSdkState.js'

const { state, loadSpinStatus, doSpin } = useSdkState()

const spinning = computed(() => state.spinning)
const spinStatus = computed(() => state.spinStatus)
const spinsRemaining = computed(() => spinStatus.value.spins_remaining ?? 0)

const displayState = ref('idle') // idle | spinning | win | lose | extra
const displayIcon = ref('?')
const displayLabel = ref('Nhấn QUAY để thử vận may')
const toastMsg = ref('')
const toastClass = ref('')

const MILESTONES = [10, 20]

const milestoneWidth = computed(() => {
  const today = spinStatus.value.spins_today ?? 0
  const limit = spinStatus.value.daily_limit || 20
  return Math.min(100, (today / limit) * 100)
})

const nextMilestone = computed(() => {
  const today = spinStatus.value.spins_today ?? 0
  return MILESTONES.find(m => today < m) ?? null
})

const displayClass = computed(() => ({
  'ccsdk-spin-display--spinning': displayState.value === 'spinning',
  'ccsdk-spin-display--win': displayState.value === 'win',
  'ccsdk-spin-display--extra': displayState.value === 'extra',
  'ccsdk-spin-display--lose': displayState.value === 'lose',
}))

function clearDisplay() {
  // keep result visible after animation — don't reset
}

function showResult(data) {
  const type = data.prize_type
  if (type === 'lose_turn') {
    displayState.value = 'lose'
    displayIcon.value = '×'
    displayLabel.value = 'Mất lượt'
  } else if (type === 'extra_turn') {
    displayState.value = 'extra'
    displayIcon.value = '↻'
    displayLabel.value = 'Thêm lượt!'
  } else if (type === 'wcoin') {
    displayState.value = 'win'
    displayIcon.value = '⭐'
    displayLabel.value = `+${data.prize_value} POINT`
  } else if (type === 'yuanbao') {
    displayState.value = 'win'
    displayIcon.value = '💎'
    displayLabel.value = `+${data.prize_value * 1000} KC`
  }

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
  displayState.value = 'spinning'
  displayIcon.value = '◌'
  displayLabel.value = '...'

  const result = await doSpin()

  if (!result.success) {
    displayState.value = 'idle'
    displayIcon.value = '!'
    displayLabel.value = result.message || 'Lỗi'
    showToast(result.message || 'Quay thất bại', 'ccsdk-spin-toast--error')
    return
  }

  showResult(result)
}

onMounted(() => {
  if (!state.spinStatusLoaded && !state.spinStatusLoading) {
    loadSpinStatus()
  }
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

/* ── Prize display zone ── */
.ccsdk-spin-display {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  margin-bottom: 8px;
  min-height: 38px;
  transition: border-color 0.2s, background 0.2s;
}

.ccsdk-spin-display-icon {
  font-size: 20px;
  line-height: 1;
  min-width: 24px;
  text-align: center;
}

.ccsdk-spin-display-label {
  font-size: 12px;
  font-weight: 500;
  color: #8888aa;
  flex: 1;
}

/* spinning pulse */
.ccsdk-spin-display--spinning {
  border-color: rgba(124, 111, 247, 0.4);
  animation: ccsdk-spin-pulse 0.6s ease-in-out infinite alternate;
}

.ccsdk-spin-display--spinning .ccsdk-spin-display-icon {
  animation: ccsdk-spin-rotate 0.8s linear infinite;
  color: #7c6ff7;
}

.ccsdk-spin-display--spinning .ccsdk-spin-display-label {
  color: #7c6ff7;
}

/* win */
.ccsdk-spin-display--win {
  border-color: rgba(255, 200, 50, 0.5);
  background: rgba(255, 200, 50, 0.06);
  animation: ccsdk-spin-win-flash 0.5s ease-out;
}

.ccsdk-spin-display--win .ccsdk-spin-display-label {
  color: #ffd54f;
  font-weight: 700;
}

/* extra turn */
.ccsdk-spin-display--extra {
  border-color: rgba(50, 220, 150, 0.5);
  background: rgba(50, 220, 150, 0.06);
  animation: ccsdk-spin-win-flash 0.5s ease-out;
}

.ccsdk-spin-display--extra .ccsdk-spin-display-icon {
  color: #32dc96;
}

.ccsdk-spin-display--extra .ccsdk-spin-display-label {
  color: #32dc96;
  font-weight: 700;
}

/* lose */
.ccsdk-spin-display--lose .ccsdk-spin-display-icon {
  color: #555577;
}

.ccsdk-spin-display--lose .ccsdk-spin-display-label {
  color: #555577;
}

/* ── Milestone bar ── */
.ccsdk-spin-milestones {
  margin-bottom: 8px;
}

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
  gap: 2px;
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

.ccsdk-spin-bar-hint {
  color: #7c6ff7;
}

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
  animation: ccsdk-spin-free-pulse 1.4s ease-in-out infinite;
}

.ccsdk-spin-btn {
  padding: 7px 20px;
  border: none;
  border-radius: 6px;
  background: linear-gradient(135deg, #7c6ff7, #5b8af7);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  letter-spacing: 0.05em;
  transition: opacity 0.15s, transform 0.1s;
  min-width: 64px;
  text-align: center;
}

.ccsdk-spin-btn:hover:not(:disabled) {
  opacity: 0.88;
  transform: translateY(-1px);
}

.ccsdk-spin-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  transform: none;
}

.ccsdk-spin-btn--spinning {
  background: linear-gradient(135deg, #5a4ec0, #4a70c0);
}

.ccsdk-spin-btn-spinner {
  display: inline-block;
  animation: ccsdk-spin-rotate 0.7s linear infinite;
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

.ccsdk-spin-toast--milestone {
  background: rgba(240, 168, 32, 0.9);
  color: #1a1208;
}

.ccsdk-spin-toast--extra {
  background: rgba(50, 220, 150, 0.9);
  color: #0a2a1a;
}

.ccsdk-spin-toast--error {
  background: rgba(220, 80, 80, 0.9);
  color: #fff;
}

.ccsdk-toast-enter-active {
  animation: ccsdk-toast-in 0.25s ease-out;
}

.ccsdk-toast-leave-active {
  animation: ccsdk-toast-out 0.3s ease-in forwards;
}

/* ── Keyframes ── */
@keyframes ccsdk-spin-pulse {
  from { box-shadow: none; }
  to { box-shadow: 0 0 8px rgba(124, 111, 247, 0.3); }
}

@keyframes ccsdk-spin-rotate {
  to { transform: rotate(360deg); }
}

@keyframes ccsdk-spin-win-flash {
  0%   { opacity: 0.4; transform: scale(0.98); }
  60%  { opacity: 1; transform: scale(1.01); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes ccsdk-spin-free-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
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
