<template>
  <div class="ov-pane">
    <!-- ─── LEFT COL: player · wallet · checkin ─── -->
    <div class="ov-col ov-col--left">
      <!-- Player card -->
      <div class="ov-card ov-player">
        <div class="ov-avatar">{{ avatarText }}</div>
        <div class="ov-player-info">
          <div class="ov-player-name">{{ player.name }}</div>
          <div class="ov-player-badges">
            <span class="ov-badge ov-badge--lv">Lv {{ player.level }}<span v-if="player.rs > 0"> · RS{{ player.rs }}</span></span>
            <span v-if="player.vip > 0" class="ov-badge ov-badge--vip">VIP {{ player.vip }}</span>
          </div>
        </div>
        <button class="ov-icon-btn" :class="{ 'ov-icon-btn--spin': refreshing }" :disabled="refreshing" @click="$emit('refresh')" title="Làm mới">
          <span class="mat-icon">refresh</span>
        </button>
      </div>

      <!-- Ví CỦA TÔI -->
      <div class="ov-card">
        <div class="ov-card-hdr">
          <span class="ov-lbl">VÍ CỦA TÔI</span>
          <button class="ov-text-btn" @click="togglePanel('wallet')">Chi tiết<span class="mat-icon" style="font-size:16px">chevron_right</span></button>
        </div>
        <div class="ov-wallet-rows">
          <div class="ov-wr">
            <span class="ov-wdot" style="background:#ffd54f"></span>
            <span class="ov-wlabel">Tôm</span>
            <span class="ov-wval">{{ fmt(wallet.tom) }}</span>
          </div>
          <div class="ov-wr">
            <span class="ov-wdot" style="background:#5b8af7"></span>
            <span class="ov-wlabel">Point</span>
            <span class="ov-wval">{{ fmt(wallet.points) }}</span>
          </div>
        </div>
        <transition name="ov-slide">
          <div v-if="activePanel === 'wallet'" class="ov-wallet-extra">
            <div class="ov-wr">
              <span class="ov-wdot" style="background:#2ec4b6"></span>
              <span class="ov-wlabel">Block KC</span>
              <span class="ov-wval">{{ fmt(wallet.diamond_blocks) }}</span>
            </div>
            <div class="ov-wr">
              <span class="ov-wdot" style="background:#a78bfa"></span>
              <span class="ov-wlabel">Trùng Sinh</span>
              <span class="ov-wval">{{ player.rs }}</span>
            </div>
          </div>
        </transition>
      </div>

      <!-- Điểm danh -->
      <div class="ov-card">
        <div class="ov-card-hdr">
          <span class="ov-lbl">ĐIỂM DANH 7 NGÀY</span>
          <span class="ov-streak"><span class="mat-icon" style="font-size:14px">local_fire_department</span>{{ checkin.streak }} ngày</span>
        </div>
        <CheckinCard
          :week="checkinWeek"
          :streak="checkin.streak"
          :checked-today="checkin.checked_today"
          @checkin="onCheckin"
        />
      </div>
    </div>

    <!-- ─── MID COL: assets · live · missions · widgets · panels ─── -->
    <div class="ov-col ov-col--mid">
      <!-- TÀI SẢN 2×2 -->
      <div class="ov-card">
        <div class="ov-card-hdr">
          <span class="ov-lbl">TÀI SẢN</span>
        </div>
        <div class="ov-stats-grid">
          <div v-for="s in assetStats" :key="s.key" class="ov-stat-card">
            <div class="ov-stat-top">
              <span class="ov-stat-icon" :style="{ background: s.tint }">
                <span class="mat-icon" :style="{ color: s.dot, fontSize: '15px' }">{{ s.icon }}</span>
              </span>
              <span class="ov-stat-label">{{ s.label }}</span>
            </div>
            <div class="ov-stat-val">{{ s.value }}</div>
            <div class="ov-stat-unit" :style="{ color: s.dot }">{{ s.unit }}</div>
          </div>
        </div>
      </div>

      <!-- Live feed -->
      <LiveFeedTicker />

      <!-- Missions -->
      <MissionsCard />

      <!-- Tiện ích -->
      <CompactUtilityGrid :active-panel="activePanel" @toggle-panel="togglePanel" />

      <!-- Expandable panels -->
      <transition name="ov-slide">
        <GiftcodeCard v-if="activePanel === 'giftcode'" />
      </transition>
      <transition name="ov-slide">
        <DonatePanel
          v-if="activePanel === 'shop'"
          :items="pshopItems"
          :items-loading="pshopLoading"
          :items-error="pshopError"
          :buy="buyWithTom"
          :compact="true"
          :supplies-url="suppliesUrl"
          :support-tiers="supportTiers"
        />
      </transition>
      <transition name="ov-slide">
        <SpinCard v-if="activePanel === 'spin'" />
      </transition>
      <transition name="ov-slide">
        <MiningCard v-if="activePanel === 'mining'" />
      </transition>
    </div>

    <!-- ─── RIGHT COL: thông báo mới (desktop only) ─── -->
    <div class="ov-col ov-col--right">
      <div class="ov-card">
        <div class="ov-card-hdr">
          <span class="ov-lbl">THÔNG BÁO MỚI</span>
          <button class="ov-text-btn" @click="$emit('switch-tab', 'notifications')">Xem tất cả</button>
        </div>
        <div class="ov-news-list">
          <div v-if="!newsItems.length" class="ov-news-empty">
            <span class="mat-icon">notifications_off</span>
            <span>Chưa có thông báo</span>
          </div>
          <div v-for="n in newsItems" :key="n.id" class="ov-news-item">
            <span class="ov-news-icon" :style="{ background: n.tint }">
              <span class="mat-icon" :style="{ color: n.color, fontSize: '18px' }">{{ n.icon }}</span>
            </span>
            <div class="ov-news-body">
              <div class="ov-news-meta">
                <span class="ov-news-type" :style="{ color: n.color, background: n.tint }">{{ n.typeLabel }}</span>
                <span class="ov-news-date">{{ n.dateStr }}</span>
              </div>
              <div class="ov-news-title">{{ n.title }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import LiveFeedTicker from './LiveFeedTicker.vue'
import MissionsCard from './MissionsCard.vue'
import CompactUtilityGrid from './CompactUtilityGrid.vue'
import GiftcodeCard from './GiftcodeCard.vue'
import CheckinCard from './CheckinCard.vue'
import DonatePanel from './DonatePane.vue'
// eager import: async component trong <transition>+v-if gây crash "Cannot read nextSibling"
import SpinCard from './SpinCard.vue'
import MiningCard from './MiningCard.vue'
import { useSdkState } from '../composables/useSdkState.js'

const props = defineProps({
  player:    { type: Object,  default: () => ({ id: 0, name: '', level: 0, vip: 0, rs: 0 }) },
  wallet:    { type: Object,  default: () => ({ coin: 0, points: 0, diamond_blocks: 0, tom: null }) },
  features:  { type: Array,   default: () => [] },
  checkin:   { type: Object,  default: () => ({ checked_today: false, streak: 0, week: [] }) },
  refreshing:{ type: Boolean, default: false },
})

const emit = defineEmits(['checkin', 'refresh', 'switch-tab'])

const { state, loadPshopItems, buyWithTom } = useSdkState()

const pshopItems   = computed(() => state.pshopItems)
const pshopLoading = computed(() => state.pshopLoading)
const pshopError   = computed(() => state.pshopError)
const suppliesUrl  = computed(() => state.suppliesUrl)
const supportTiers = computed(() => state.supportTiers)

const activePanel = ref(null)

function togglePanel(key) {
  activePanel.value = activePanel.value === key ? null : key
  if (key === 'shop' && activePanel.value === key && !state.pshopLoaded) loadPshopItems()
}

const avatarText = computed(() => (props.player.name || '?').slice(0, 2).toUpperCase())

const checkinWeek = computed(() => {
  if (!props.checkin.week?.length) return []
  const todayIdx = (new Date().getDay() + 6) % 7
  return props.checkin.week.map((d, i) => ({ ...d, today: i === todayIdx && !d.done }))
})

function onCheckin() { emit('checkin') }

function fmt(n) { return (n || 0).toLocaleString() }

const assetStats = computed(() => [
  {
    key: 'tom',
    icon: 'monetization_on',
    label: 'Tôm',
    value: fmt(props.wallet.tom),
    unit: 'số dư khả dụng',
    dot: '#ffd54f',
    tint: 'rgba(255,213,79,.12)',
  },
  {
    key: 'kc',
    icon: 'construction',
    label: 'Block KC',
    value: fmt(props.wallet.diamond_blocks),
    unit: 'khối đã đào',
    dot: '#2ec4b6',
    tint: 'rgba(46,196,182,.12)',
  },
  {
    key: 'points',
    icon: 'stars',
    label: 'Point',
    value: fmt(props.wallet.points),
    unit: 'điểm tích lũy',
    dot: '#60a5fa',
    tint: 'rgba(96,165,250,.12)',
  },
  {
    key: 'rs',
    icon: 'autorenew',
    label: 'Trùng Sinh',
    value: props.player.rs,
    unit: 'lần reset',
    dot: '#a78bfa',
    tint: 'rgba(167,139,250,.12)',
  },
])

const TYPE_MAP = {
  event:       { icon: 'celebration',   color: '#c9a94e', tint: 'rgba(201,169,78,.12)',  label: 'SỰ KIỆN' },
  maintenance: { icon: 'build',         color: '#f87171', tint: 'rgba(248,113,113,.12)', label: 'BẢO TRÌ' },
  update:      { icon: 'update',        color: '#60a5fa', tint: 'rgba(96,165,250,.12)',  label: 'CẬP NHẬT' },
  giftcode:    { icon: 'redeem',        color: '#34d399', tint: 'rgba(52,211,153,.12)',  label: 'GIFTCODE' },
}

const newsItems = computed(() =>
  state.changelog.slice(0, 3).map(e => {
    const t = TYPE_MAP[e.type] || { icon: 'notifications', color: '#8c877b', tint: 'rgba(140,135,123,.12)', label: 'TIN TỨC' }
    const raw = e.date || e.created_at || ''
    const dateStr = raw ? new Date(raw).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }) : ''
    return { ...e, ...t, typeLabel: t.label, dateStr }
  })
)
</script>

<style scoped>
/* ── Layout ── */
.ov-pane {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

@media (min-width: 768px) {
  .ov-pane {
    display: grid;
    grid-template-columns: 240px 1fr;
    grid-template-areas: "left mid";
    gap: 14px;
    padding: 18px;
  }
  .ov-col--left  { grid-area: left; }
  .ov-col--mid   { grid-area: mid; }
  .ov-col--right { display: none; }
}

@media (min-width: 1280px) {
  .ov-pane {
    grid-template-columns: 256px 1fr 280px;
    grid-template-areas: "left mid right";
    gap: 16px;
  }
  .ov-col--right { display: flex; }
}

.ov-col {
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-width: 0;
}

/* ── Card ── */
.ov-card {
  background: #10101a;
  border: 1px solid rgba(201,169,78,.14);
  border-radius: 16px;
  padding: 18px;
}

.ov-card-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.ov-lbl {
  font-family: 'Outfit', sans-serif;
  font-size: 13px;
  font-weight: 700;
  color: #c9a94e;
  letter-spacing: .03em;
}

.ov-text-btn {
  display: flex;
  align-items: center;
  gap: 2px;
  background: none;
  border: none;
  cursor: pointer;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11.5px;
  font-weight: 600;
  color: #8c877b;
  padding: 0;
  transition: color .15s;
}
.ov-text-btn:hover { color: #c9a94e; }

/* ── Player card ── */
.ov-player {
  display: flex;
  align-items: center;
  gap: 13px;
}

.ov-avatar {
  width: 54px;
  height: 54px;
  border-radius: 14px;
  background: linear-gradient(150deg, #2a2a38, #15151f);
  border: 1px solid rgba(201,169,78,.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Outfit', sans-serif;
  font-size: 20px;
  font-weight: 700;
  color: #c9a94e;
  flex-shrink: 0;
}

.ov-player-info { flex: 1; min-width: 0; }

.ov-player-name {
  font-family: 'Outfit', sans-serif;
  font-size: 17px;
  font-weight: 700;
  color: #f4f1e9;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ov-player-badges {
  display: flex;
  gap: 6px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.ov-badge {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 6px;
}
.ov-badge--lv  { color: #dfe4ee; background: rgba(91,141,239,.16); border: 1px solid rgba(91,141,239,.35); }
.ov-badge--vip { color: #07070a; background: #c9a94e; }

.ov-icon-btn {
  background: none;
  border: none;
  color: #8c877b;
  cursor: pointer;
  padding: 4px;
  line-height: 1;
  border-radius: 6px;
  transition: color .15s, background .15s;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  font-size: 18px;
}
.ov-icon-btn:hover { color: #c9a94e; background: rgba(201,169,78,.08); }
.ov-icon-btn:disabled { opacity: .45; cursor: not-allowed; }
.ov-icon-btn--spin { animation: ov-spin .8s linear infinite; }
@keyframes ov-spin { to { transform: rotate(360deg); } }

/* ── Wallet ── */
.ov-wallet-rows {
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.ov-wr {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.ov-wdot {
  width: 8px;
  height: 8px;
  border-radius: 3px;
  flex-shrink: 0;
  margin-right: 8px;
}

.ov-wlabel {
  flex: 1;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  color: #b8b2a4;
}

.ov-wval {
  font-family: 'Outfit', sans-serif;
  font-size: 15px;
  font-weight: 700;
  color: #f4f1e9;
  font-variant-numeric: tabular-nums;
}

.ov-wallet-extra {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(255,255,255,.06);
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* ── Streak ── */
.ov-streak {
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11px;
  font-weight: 600;
  color: #e8d49a;
  background: rgba(201,169,78,.14);
  border: 1px solid rgba(201,169,78,.3);
  padding: 2px 9px;
  border-radius: 999px;
}

/* override CheckinCard card-wrapper khi nhúng trong ov-card */
:deep(.ccsdk-checkin) {
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 0;
  margin-bottom: 0;
}

/* ── TÀI SẢN grid ── */
.ov-stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 11px;
}

.ov-stat-card {
  background: #0c0c14;
  border: 1px solid rgba(255,255,255,.05);
  border-radius: 12px;
  padding: 14px;
}

.ov-stat-top {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 9px;
}

.ov-stat-icon {
  width: 24px;
  height: 24px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.ov-stat-label {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 12px;
  font-weight: 500;
  color: #b8b2a4;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ov-stat-val {
  font-family: 'Outfit', sans-serif;
  font-size: 24px;
  font-weight: 700;
  color: #f4f1e9;
  letter-spacing: -.01em;
  font-variant-numeric: tabular-nums;
  line-height: 1.15;
}

.ov-stat-unit {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11px;
  font-weight: 500;
  margin-top: 2px;
}

/* ── News (right col) ── */
.ov-news-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.ov-news-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px 0;
  font-size: 12px;
  color: #5a5a7a;
}
.ov-news-empty .mat-icon { font-size: 28px; opacity: .5; }

.ov-news-item {
  display: flex;
  gap: 11px;
  align-items: flex-start;
}

.ov-news-icon {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ov-news-body { min-width: 0; }

.ov-news-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 3px;
}

.ov-news-type {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 9.5px;
  font-weight: 600;
  padding: 1px 7px;
  border-radius: 5px;
  text-transform: uppercase;
  letter-spacing: .04em;
}

.ov-news-date {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 10.5px;
  font-weight: 500;
  color: #6f6b61;
}

.ov-news-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 12.5px;
  font-weight: 600;
  color: #e7e3d9;
  line-height: 1.35;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* ── Panel slide transition ── */
.ov-slide-enter-active { animation: ov-slide-in .2s ease; }
.ov-slide-leave-active { animation: ov-slide-out .15s ease; }
@keyframes ov-slide-in  { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
@keyframes ov-slide-out { from { opacity:1; } to { opacity:0; } }
</style>
