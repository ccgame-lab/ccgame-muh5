<template>
  <div class="ccsdk-checkin">
    <div class="ccsdk-checkin-header">
      <span class="ccsdk-checkin-title">Check-in hằng ngày</span>
      <span v-if="streak > 0" class="ccsdk-checkin-streak">&#x1F525; {{ streak }} ngày</span>
    </div>

    <div class="ccsdk-checkin-week">
      <div
        v-for="d in week"
        :key="d.day"
        class="ccsdk-checkin-day"
        :class="dayClass(d)"
      >
        <span class="ccsdk-checkin-day-label">{{ d.day }}</span>
        <span class="ccsdk-checkin-day-icon">{{ dayIcon(d) }}</span>
      </div>
    </div>

    <button
      class="ccsdk-checkin-btn"
      :disabled="checkedToday || checkinLoading"
      @click="onCheckin"
    >
      {{ checkedToday ? 'Đã điểm danh' : 'Điểm danh' }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  week: { type: Array, default: () => [] },
  streak: { type: Number, default: 0 },
  checkedToday: { type: Boolean, default: false },
})

const emit = defineEmits(['checkin'])
const checkinLoading = ref(false)

function dayClass(d) {
  if (d.done) return 'ccsdk-checkin-day--done'
  if (d.today) return 'ccsdk-checkin-day--today'
  return 'ccsdk-checkin-day--future'
}

function dayIcon(d) {
  if (d.done) return '\u2713'
  if (d.today) return '\u2605'
  return '\u00B7'
}

function todayIndex() {
  return new Date().getDay() // 0=Sun, 1=Mon... we use T2=index0 => offset
}

async function onCheckin() {
  checkinLoading.value = true
  await emit('checkin')
  // Ensure loading state is visible for at least 100ms
  await new Promise(resolve => setTimeout(resolve, 100))
  checkinLoading.value = false
}
</script>

<style scoped>
.ccsdk-checkin {
  background: #161626;
  border: 1px solid rgba(120,100,255,0.18);
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 12px;
}

.ccsdk-checkin-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.ccsdk-checkin-title {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #8888aa;
}

.ccsdk-checkin-streak {
  font-size: 10px;
  font-weight: 700;
  color: #f0c060;
}

.ccsdk-checkin-week {
  display: flex;
  gap: 4px;
  margin-bottom: 10px;
}

.ccsdk-checkin-day {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 6px 2px;
  border-radius: 6px;
  border: 1px solid transparent;
  transition: background 0.15s, border-color 0.15s;
}

.ccsdk-checkin-day-label {
  font-size: 9px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.ccsdk-checkin-day-icon {
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
}

/* done */
.ccsdk-checkin-day--done {
  background: rgba(124,111,247,0.2);
  border-color: #7c6ff7;
}
.ccsdk-checkin-day--done .ccsdk-checkin-day-label {
  color: #7c6ff7;
}
.ccsdk-checkin-day--done .ccsdk-checkin-day-icon {
  color: #7c6ff7;
}

/* today */
.ccsdk-checkin-day--today {
  background: #7c6ff7;
  border-color: #7c6ff7;
}
.ccsdk-checkin-day--today .ccsdk-checkin-day-label {
  color: #fff;
}
.ccsdk-checkin-day--today .ccsdk-checkin-day-icon {
  color: #fff;
}

/* future */
.ccsdk-checkin-day--future {
  background: rgba(255,255,255,0.04);
  border-color: transparent;
}
.ccsdk-checkin-day--future .ccsdk-checkin-day-label {
  color: #4a4a6a;
}
.ccsdk-checkin-day--future .ccsdk-checkin-day-icon {
  color: #4a4a6a;
}

.ccsdk-checkin-btn {
  width: 100%;
  padding: 8px 0;
  border-radius: 6px;
  border: none;
  background: #7c6ff7;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  transition: background 0.15s, opacity 0.15s;
}

.ccsdk-checkin-btn:hover:not(:disabled) {
  background: #6a5ee0;
}

.ccsdk-checkin-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
