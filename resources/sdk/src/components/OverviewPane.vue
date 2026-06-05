<template>
  <div class="ccsdk-pane">
    <!-- Player row -->
    <div class="ccsdk-player">
      <div class="ccsdk-player-avatar">{{ avatarText }}</div>
      <div class="ccsdk-player-info">
        <div class="ccsdk-player-name">{{ player.name }}</div>
        <div class="ccsdk-player-level">Lv.{{ player.level }}</div>
      </div>
      <div v-if="player.vip > 0" class="ccsdk-vip-badge">VIP {{ player.vip }}</div>
    </div>

    <!-- Stats header + refresh -->
    <div class="ccsdk-stats-header">
      <span class="ccsdk-stats-header-label">Ví &amp; Thông tin</span>
      <button
        class="ccsdk-refresh-btn"
        :class="{ 'ccsdk-refresh-btn--spin': refreshing }"
        :disabled="refreshing"
        @click="$emit('refresh')"
        title="Làm mới"
      >↻</button>
    </div>

    <!-- Stats grid 2x2 -->
    <div class="ccsdk-stats">
      <div class="ccsdk-stat-card ccsdk-stat-card--coin">
        <span class="ccsdk-stat-label">XU</span>
        <span class="ccsdk-stat-value">{{ fmt(wallet.coin) }}</span>
      </div>
      <div class="ccsdk-stat-card ccsdk-stat-card--points">
        <span class="ccsdk-stat-label">POINT</span>
        <span class="ccsdk-stat-value">{{ fmt(wallet.points) }}</span>
      </div>
      <div class="ccsdk-stat-card ccsdk-stat-card--level">
        <span class="ccsdk-stat-label">Cấp độ</span>
        <span class="ccsdk-stat-value">{{ player.level }}</span>
      </div>
      <div v-if="wallet.tom != null" class="ccsdk-stat-card ccsdk-stat-card--tom">
        <span class="ccsdk-stat-label">TÔM</span>
        <span class="ccsdk-stat-value ccsdk-tom-value">{{ fmt(wallet.tom) }}</span>
        <span class="ccsdk-tom-shimmer"></span>
      </div>
    </div>

    <!-- Live feed ticker -->
    <LiveFeedTicker />

    <!-- Daily missions -->
    <MissionsCard />

    <!-- Mining -->
    <MiningCard />

    <!-- Spin -->
    <SpinCard />

    <!-- Check-in -->
    <CheckinCard
      :week="checkinWeek"
      :streak="checkin.streak"
      :checked-today="checkin.checked_today"
      @checkin="onCheckin"
    />

    <!-- Giftcode -->
    <GiftcodeCard />

    <!-- Features -->
    <FeatureGrid :features="quickActions" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import LiveFeedTicker from './LiveFeedTicker.vue'
import MissionsCard from './MissionsCard.vue'
import MiningCard from './MiningCard.vue'
import SpinCard from './SpinCard.vue'
import CheckinCard from './CheckinCard.vue'
import GiftcodeCard from './GiftcodeCard.vue'
import FeatureGrid from './FeatureGrid.vue'

const props = defineProps({
  player: { type: Object, default: () => ({ id: 0, name: '', level: 0, vip: 0 }) },
  wallet: { type: Object, default: () => ({ coin: 0, points: 0 }) },
  features: { type: Array, default: () => [] },
  checkin: { type: Object, default: () => ({ checked_today: false, streak: 0, week: [] }) },
  refreshing: { type: Boolean, default: false },
})

const emit = defineEmits(['checkin', 'refresh'])

const avatarText = computed(() => {
  const name = props.player.name || '?'
  return name.slice(0, 2).toUpperCase()
})

const quickActions = computed(() => {
  return props.features.filter(f => f.active)
})

const checkinWeek = computed(() => {
  if (!props.checkin.week || !props.checkin.week.length) return []
  // todayIdx: T2(0) = Mon(getDay=1), ..., CN(6) = Sun(getDay=0)
  const todayIdx = (new Date().getDay() + 6) % 7
  return props.checkin.week.map((d, i) => ({
    ...d,
    today: i === todayIdx && !d.done,
  }))
})

function onCheckin() {
  emit('checkin')
}

function fmt(n) {
  return (n || 0).toLocaleString()
}
</script>

<style scoped>
.ccsdk-pane {
  padding: 14px;
}

/* ── Player row ── */
.ccsdk-player {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.ccsdk-player-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7c6ff7, #5b8af7);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: #fff;
  flex-shrink: 0;
}

.ccsdk-player-info {
  flex: 1;
  min-width: 0;
}

.ccsdk-player-name {
  font-size: 13px;
  font-weight: 700;
  color: #e8e8f0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ccsdk-player-level {
  font-size: 10px;
  color: #8888aa;
  margin-top: 1px;
}

.ccsdk-vip-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 7px;
  border-radius: 4px;
  background: rgba(240, 192, 96, 0.15);
  border: 1px solid #f0c060;
  color: #f0c060;
  font-size: 10px;
  font-weight: 700;
  flex-shrink: 0;
}

/* ── Stats grid 2x2 ── */
.ccsdk-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 12px;
}

.ccsdk-stat-card {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 8px 10px;
  border-radius: 8px;
  background: #1e1e32;
  border: 1px solid rgba(120,100,255,0.18);
}

.ccsdk-stat-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #8888aa;
}

.ccsdk-stat-value {
  font-size: 15px;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
}

.ccsdk-stat-card--coin .ccsdk-stat-value { color: #f0c060; }
.ccsdk-stat-card--points .ccsdk-stat-value { color: #5b8af7; }
.ccsdk-stat-card--level .ccsdk-stat-value { color: #e8e8f0; }

/* ── TÔM card — GreenJade premium currency ── */
.ccsdk-stat-card--tom {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #1a1208 0%, #2a1e08 60%, #1a1208 100%);
  border-color: transparent;
  box-shadow:
    0 0 0 1px rgba(240, 168, 32, 0.55),
    0 0 8px rgba(240, 168, 32, 0.20),
    inset 0 1px 0 rgba(255, 210, 80, 0.10);
  animation: ccsdk-tom-pulse 2.8s ease-in-out infinite;
}

.ccsdk-stat-card--tom .ccsdk-stat-label {
  color: #c9892a;
  letter-spacing: 0.1em;
}

.ccsdk-tom-value {
  color: #ffd54f;
  text-shadow: 0 0 12px rgba(255, 190, 40, 0.7), 0 0 24px rgba(255, 160, 20, 0.35);
  font-weight: 700;
}

/* shimmer sweep */
.ccsdk-tom-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    105deg,
    transparent 35%,
    rgba(255, 220, 100, 0.18) 50%,
    transparent 65%
  );
  background-size: 200% 100%;
  animation: ccsdk-tom-shimmer 2.4s linear infinite;
  pointer-events: none;
}

@keyframes ccsdk-tom-pulse {
  0%, 100% { box-shadow: 0 0 0 1px rgba(240, 168, 32, 0.55), 0 0 8px rgba(240, 168, 32, 0.20), inset 0 1px 0 rgba(255, 210, 80, 0.10); }
  50%       { box-shadow: 0 0 0 1px rgba(240, 168, 32, 0.90), 0 0 14px rgba(240, 168, 32, 0.40), inset 0 1px 0 rgba(255, 210, 80, 0.18); }
}

@keyframes ccsdk-tom-shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ── Stats header + refresh ── */
.ccsdk-stats-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.ccsdk-stats-header-label {
  font-size: 9px;
  color: #5a5a7a;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 600;
}
.ccsdk-refresh-btn {
  background: none;
  border: none;
  color: #5a5a7a;
  font-size: 14px;
  cursor: pointer;
  padding: 2px 4px;
  line-height: 1;
  transition: color 0.15s;
}
.ccsdk-refresh-btn:hover { color: #c9a94e; }
.ccsdk-refresh-btn:disabled { cursor: not-allowed; opacity: 0.5; }
.ccsdk-refresh-btn--spin {
  animation: ccsdk-spin 0.8s linear infinite;
}
@keyframes ccsdk-spin {
  to { transform: rotate(360deg); }
}
</style>
