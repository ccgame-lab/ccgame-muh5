<template>
  <div class="ccsdk-pane">
    <!-- Cot trai: danh tinh + vi gon + diem danh -->
    <div class="ccsdk-col ccsdk-col--left">
      <!-- Player row -->
      <div class="ccsdk-player">
        <div class="ccsdk-player-avatar">{{ avatarText }}</div>
        <div class="ccsdk-player-info">
          <div class="ccsdk-player-name">{{ player.name }}</div>
          <div class="ccsdk-player-level">Lv.{{ player.level }}<span v-if="player.rs > 0"> · RS{{ player.rs }}</span></div>
        </div>
        <div v-if="player.vip > 0" class="ccsdk-vip-badge">VIP {{ player.vip }}</div>
        <button
          class="ccsdk-refresh-btn"
          :class="{ 'ccsdk-refresh-btn--spin': refreshing }"
          :disabled="refreshing"
          @click="$emit('refresh')"
          title="Làm mới"
        >↻</button>
      </div>

      <!-- Vi gon: dot indicators -->
      <div class="ccsdk-wallet-section">
        <div class="ccsdk-section-header">
          <span class="ccsdk-section-label">VÍ CỦA TÔI</span>
          <button class="ccsdk-detail-btn" @click="togglePanel('wallet')">Chi tiết ›</button>
        </div>
        <div class="ccsdk-wallet-rows">
          <div class="ccsdk-wallet-row">
            <span class="ccsdk-dot ccsdk-dot--tom"></span>
            <span class="ccsdk-wallet-row-label">Tôm</span>
            <span class="ccsdk-wallet-row-val ccsdk-tom-hl">{{ fmt(wallet.tom) }}</span>
          </div>
          <div class="ccsdk-wallet-row">
            <span class="ccsdk-dot ccsdk-dot--point"></span>
            <span class="ccsdk-wallet-row-label">Point</span>
            <span class="ccsdk-wallet-row-val">{{ fmt(wallet.points) }}</span>
          </div>
        </div>
        <transition name="ccsdk-panel-slide">
          <div v-if="activePanel === 'wallet'" class="ccsdk-wallet-detail">
            <div class="ccsdk-wi-row" v-if="wallet.diamond_blocks"><span>Block KC</span><strong>{{ fmt(wallet.diamond_blocks) }}</strong></div>
            <div class="ccsdk-wi-row"><span>Trùng Sinh</span><strong>{{ player.rs }}</strong></div>
          </div>
        </transition>
      </div>

      <!-- Diem danh giu o cot trai -->
      <CheckinCard
        :week="checkinWeek"
        :streak="checkin.streak"
        :checked-today="checkin.checked_today"
        @checkin="onCheckin"
      />
    </div>

    <!-- Cot phai: tai san hero + feed + missions + utilities -->
    <div class="ccsdk-col ccsdk-col--right">
      <!-- TÀI SẢN hero numbers -->
      <div class="ccsdk-assets">
        <div class="ccsdk-section-label">TÀI SẢN</div>
        <div class="ccsdk-asset-item ccsdk-asset-item--tom">
          <div class="ccsdk-asset-icon">🍤</div>
          <div class="ccsdk-asset-body">
            <div class="ccsdk-asset-name">Tôm</div>
            <div class="ccsdk-asset-value ccsdk-tom-value">{{ fmt(wallet.tom) }}<span class="ccsdk-tom-shimmer"></span></div>
            <div class="ccsdk-asset-sub">số dư khả dụng</div>
          </div>
        </div>
        <div class="ccsdk-asset-item ccsdk-asset-item--kc">
          <div class="ccsdk-asset-icon">⛏</div>
          <div class="ccsdk-asset-body">
            <div class="ccsdk-asset-name">Block KC</div>
            <div class="ccsdk-asset-value ccsdk-kc-value">{{ fmt(wallet.diamond_blocks) }}</div>
            <div class="ccsdk-asset-sub">khối đã đào</div>
          </div>
        </div>
      </div>

      <!-- Live feed ticker -->
      <LiveFeedTicker />

      <!-- Daily missions -->
      <MissionsCard />

      <!-- Compact utility grid -->
      <CompactUtilityGrid :active-panel="activePanel" @toggle-panel="togglePanel" />

      <!-- Expandable panels -->
      <transition name="ccsdk-panel-slide">
        <GiftcodeCard v-if="activePanel === 'giftcode'" />
      </transition>
      <transition name="ccsdk-panel-slide">
        <DonatePanel v-if="activePanel === 'shop'" :items="pshopItems" :items-loading="pshopLoading" :items-error="pshopError" :buy="buyWithTom" :compact="true" :supplies-url="suppliesUrl" :support-tiers="supportTiers" />
      </transition>
      <transition name="ccsdk-panel-slide">
        <SpinCard v-if="activePanel === 'spin'" />
      </transition>
      <transition name="ccsdk-panel-slide">
        <MiningCard v-if="activePanel === 'mining'" />
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import LiveFeedTicker from './LiveFeedTicker.vue'
import MissionsCard from './MissionsCard.vue'
import CompactUtilityGrid from './CompactUtilityGrid.vue'
import GiftcodeCard from './GiftcodeCard.vue'
import CheckinCard from './CheckinCard.vue'
import DonatePanel from './DonatePane.vue'

// EAGER import (KHÔNG defineAsyncComponent): async component bọc trong <transition>+v-if gây
// "Cannot read properties of null (reading 'nextSibling')" -> crash cả SDK khi mở tile spin/mining.
// GiftcodeCard/DonatePanel (eager) dùng cùng transition này chạy ổn -> eager là pattern đúng.
import SpinCard from './SpinCard.vue'
import MiningCard from './MiningCard.vue'
import { useSdkState } from '../composables/useSdkState.js'

const props = defineProps({
  player: { type: Object, default: () => ({ id: 0, name: '', level: 0, vip: 0, rs: 0 }) },
  wallet: { type: Object, default: () => ({ coin: 0, points: 0, diamond_blocks: 0, tom: null }) },
  features: { type: Array, default: () => [] },
  checkin: { type: Object, default: () => ({ checked_today: false, streak: 0, week: [] }) },
  refreshing: { type: Boolean, default: false },
})

const emit = defineEmits(['checkin', 'refresh'])

const { state, loadPshopItems, buyWithTom } = useSdkState()

const pshopItems = computed(() => state.pshopItems)
const pshopLoading = computed(() => state.pshopLoading)
const pshopError = computed(() => state.pshopError)
const suppliesUrl = computed(() => state.suppliesUrl)
const supportTiers = computed(() => state.supportTiers)

const activePanel = ref(null)

function togglePanel(key) {
  if (activePanel.value === key) {
    activePanel.value = null
    return
  }
  activePanel.value = key
  if (key === 'shop' && !state.pshopLoaded) {
    loadPshopItems()
  }
}

const avatarText = computed(() => {
  const name = props.player.name || '?'
  return name.slice(0, 2).toUpperCase()
})

const checkinWeek = computed(() => {
  if (!props.checkin.week || !props.checkin.week.length) return []
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
.ccsdk-col { display: flex; flex-direction: column; min-width: 0; }

/* PC: 2 cot dashboard */
@media (min-width: 768px) {
  .ccsdk-pane {
    display: grid;
    grid-template-columns: minmax(0, 0.88fr) minmax(0, 1.12fr);
    gap: 18px;
    align-items: start;
    padding: 16px 18px;
  }
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
  background: linear-gradient(135deg, #c9a94e, #a3812d);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: #0d0d14;
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

.ccsdk-refresh-btn {
  background: none;
  border: none;
  color: #5a5a7a;
  font-size: 14px;
  cursor: pointer;
  padding: 2px 4px;
  line-height: 1;
  transition: color 0.15s;
  flex-shrink: 0;
}
.ccsdk-refresh-btn:hover { color: #c9a94e; }
.ccsdk-refresh-btn:disabled { cursor: not-allowed; opacity: 0.5; }
.ccsdk-refresh-btn--spin { animation: ccsdk-spin 0.8s linear infinite; }
@keyframes ccsdk-spin { to { transform: rotate(360deg); } }

/* ── Section header shared ── */
.ccsdk-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.ccsdk-section-label {
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #5a5a7a;
}

/* ── Wallet compact section ── */
.ccsdk-wallet-section {
  background: #12121d;
  border: 1px solid #1e1e32;
  border-radius: 10px;
  padding: 10px 12px;
  margin-bottom: 12px;
}

.ccsdk-detail-btn {
  background: none;
  border: none;
  color: #5a5a7a;
  font-size: 10px;
  cursor: pointer;
  padding: 0;
  transition: color 0.15s;
}
.ccsdk-detail-btn:hover { color: #c9a94e; }

.ccsdk-wallet-rows {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.ccsdk-wallet-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
}

.ccsdk-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}
.ccsdk-dot--tom   { background: #ffd54f; box-shadow: 0 0 5px rgba(255,213,79,0.6); }
.ccsdk-dot--point { background: #5b8af7; box-shadow: 0 0 5px rgba(91,138,247,0.5); }

.ccsdk-wallet-row-label {
  flex: 1;
  color: #8888aa;
}

.ccsdk-wallet-row-val {
  color: #e2e2f0;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}
.ccsdk-tom-hl { color: #ffd54f; }

.ccsdk-wallet-detail {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #1e1e32;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.ccsdk-wi-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
  color: #8888aa;
}
.ccsdk-wi-row strong {
  color: #e2e2f0;
  font-variant-numeric: tabular-nums;
}

/* ── TÀI SẢN hero ── */
.ccsdk-assets {
  background: #12121d;
  border: 1px solid #1e1e32;
  border-radius: 10px;
  padding: 10px 14px 12px;
  margin-bottom: 10px;
}

.ccsdk-asset-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.ccsdk-asset-item:last-child { border-bottom: none; padding-bottom: 0; }
.ccsdk-asset-item:first-of-type { padding-top: 8px; }

.ccsdk-asset-icon {
  font-size: 20px;
  line-height: 1;
  flex-shrink: 0;
  margin-top: 2px;
}

.ccsdk-asset-body {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.ccsdk-asset-name {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #8888aa;
}

.ccsdk-asset-value {
  font-size: 22px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  line-height: 1.1;
  position: relative;
  overflow: hidden;
}

.ccsdk-tom-value {
  color: #ffd54f;
  text-shadow: 0 0 12px rgba(255,190,40,0.7), 0 0 24px rgba(255,160,20,0.35);
  animation: ccsdk-tom-pulse 2.8s ease-in-out infinite;
}

.ccsdk-kc-value {
  color: #2ec4b6;
  text-shadow: 0 0 10px rgba(46,196,182,0.4);
}

.ccsdk-asset-sub {
  font-size: 10px;
  color: #5a5a7a;
  margin-top: 1px;
}

/* shimmer on TÔM value */
.ccsdk-tom-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    105deg,
    transparent 35%,
    rgba(255,220,100,0.22) 50%,
    transparent 65%
  );
  background-size: 200% 100%;
  animation: ccsdk-tom-shimmer 2.4s linear infinite;
  pointer-events: none;
}

@keyframes ccsdk-tom-pulse {
  0%, 100% { text-shadow: 0 0 12px rgba(255,190,40,0.7), 0 0 24px rgba(255,160,20,0.35); }
  50%       { text-shadow: 0 0 18px rgba(255,200,40,1.0), 0 0 36px rgba(255,160,20,0.55); }
}
@keyframes ccsdk-tom-shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ── Panel slide transition ── */
.ccsdk-panel-slide-enter-active { animation: ccsdk-slide-in 0.2s ease; }
.ccsdk-panel-slide-leave-active { animation: ccsdk-slide-out 0.15s ease; }
@keyframes ccsdk-slide-in {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes ccsdk-slide-out {
  from { opacity: 1; }
  to   { opacity: 0; }
}
</style>
