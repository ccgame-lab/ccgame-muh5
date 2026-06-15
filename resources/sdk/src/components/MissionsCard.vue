<template>
  <section class="msn" v-if="missionsLoaded || missions.length">
    <div class="msn-head">
      <span class="msn-title">NHIỆM VỤ NGÀY</span>
      <span v-if="allDone && !bonusClaimed" class="msn-badge msn-badge--ready">THƯỞNG SẴN SÀNG</span>
      <span v-else-if="allDone && bonusClaimed" class="msn-badge msn-badge--done">✓ ĐÃ NHẬN</span>
      <span v-else class="msn-badge">{{ doneCnt }}/{{ missions.length }}</span>
    </div>

    <div class="msn-list">
      <div v-for="m in missions" :key="m.key" class="msn-item" :class="{ 'msn-item--done': m.done }">
        <span class="msn-check">{{ m.done ? '✓' : '○' }}</span>
        <span class="msn-label">{{ m.label }}</span>
        <span v-if="!m.done && m.progress != null" class="msn-progress">{{ m.progress }}/{{ m.target }}</span>
      </div>
    </div>

    <button
      v-if="allDone && !bonusClaimed"
      class="msn-claim-btn"
      :disabled="claiming"
      @click="doClaim"
    >
      {{ claiming ? '...' : `NHẬN +${bonusPoints} POINT` }}
    </button>

    <transition name="msn-toast">
      <div v-if="toast" class="msn-toast">{{ toast }}</div>
    </transition>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useSdkState } from '../composables/useSdkState.js'

const { state, loadMissions, claimMissionsBonus } = useSdkState()

const claiming = ref(false)
const toast = ref(null)

const missions = computed(() => state.missions)
const missionsLoaded = computed(() => state.missionsLoaded)
const allDone = computed(() => state.missionsAllDone)
const bonusClaimed = computed(() => state.missionsBonusClaimed)
const bonusPoints = computed(() => state.missionsBonusPoints)
const doneCnt = computed(() => missions.value.filter(m => m.done).length)

async function doClaim() {
  claiming.value = true
  const res = await claimMissionsBonus()
  claiming.value = false
  if (res.success) {
    showToast(res.message || `+${bonusPoints.value} POINT!`)
  } else {
    showToast(res.message || 'Thử lại sau.')
  }
}

function showToast(msg) {
  toast.value = msg
  setTimeout(() => { toast.value = null }, 3000)
}

// Re-check after checkin, spin, mining events
const EVENTS = ['mining:claim', 'spin:done', 'checkin:done']

onMounted(() => {
  loadMissions()
  EVENTS.forEach(e => window.addEventListener(e, loadMissions))
})

onUnmounted(() => {
  EVENTS.forEach(e => window.removeEventListener(e, loadMissions))
})
</script>

<style scoped>
.msn {
  background: #12121d;
  border: 1px solid #1e1e32;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 12px;
  position: relative;
  overflow: hidden;
}

.msn-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.msn-title {
  font-size: 9px;
  font-weight: 700;
  color: #5a5a7a;
  letter-spacing: 0.08em;
}

.msn-badge {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 3px;
  background: rgba(120,100,255,0.12);
  color: #7c6ff7;
  border: 1px solid rgba(120,100,255,0.2);
}

.msn-badge--ready {
  background: rgba(240,192,96,0.12);
  color: #f0c060;
  border-color: rgba(240,192,96,0.3);
  animation: msn-pulse 1.4s ease-in-out infinite;
}

.msn-badge--done {
  background: rgba(76,175,80,0.12);
  color: #4caf50;
  border-color: rgba(76,175,80,0.2);
}

@keyframes msn-pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.55; }
}

.msn-list {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-bottom: 8px;
}

.msn-item {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 11px;
  color: #8888aa;
}

.msn-item--done {
  color: #4caf50;
}

.msn-check {
  width: 14px;
  flex-shrink: 0;
  font-weight: 700;
  font-size: 11px;
}

.msn-label {
  flex: 1;
}

.msn-progress {
  font-size: 10px;
  color: #5a5a7a;
  font-variant-numeric: tabular-nums;
}

.msn-claim-btn {
  width: 100%;
  padding: 7px 0;
  border-radius: 6px;
  border: none;
  background: linear-gradient(135deg, #c9a94e, #e2c46a);
  color: #0d0d14;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: opacity 0.15s;
}

.msn-claim-btn:hover:not(:disabled) { opacity: 0.9; }
.msn-claim-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.msn-toast {
  position: absolute;
  bottom: 8px;
  left: 12px;
  right: 12px;
  background: rgba(76,175,80,0.15);
  border: 1px solid rgba(76,175,80,0.3);
  border-radius: 5px;
  color: #4caf50;
  font-size: 10px;
  font-weight: 600;
  padding: 5px 8px;
  text-align: center;
}

.msn-toast-enter-active { animation: msn-fadein 0.25s ease; }
.msn-toast-leave-active { animation: msn-fadeout 0.25s ease; }
@keyframes msn-fadein  { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
@keyframes msn-fadeout { from { opacity: 1; } to { opacity: 0; } }
</style>
