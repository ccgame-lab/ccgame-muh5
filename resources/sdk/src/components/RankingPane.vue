<template>
  <div class="ccgame-sdk-pane">
    <!-- Loading spinner -->
    <div v-if="loading" class="ccsdk-ranking-status">
      <div class="ccsdk-ranking-spinner"></div>
      <span>Đang tải bảng xếp hạng...</span>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="ccsdk-ranking-status ccsdk-ranking-status--error">
      <span>{{ error }}</span>
    </div>

    <!-- Data loaded -->
    <template v-else-if="types.length">
      <div class="ccgame-sdk-subtabs">
        <button
          v-for="t in types" :key="t.key"
          class="ccgame-sdk-subtab"
          :class="{ 'ccgame-sdk-subtab--active': active === t.key }"
          @click="$emit('update:active', t.key)"
        >{{ t.label }}</button>
      </div>

      <template v-if="activeSub && players.length">
        <!-- Podium top-3 -->
        <div v-if="podium.length" class="ccsdk-podium">
          <div
            v-for="slot in podium"
            :key="slot.player.rank"
            class="ccsdk-podium-slot"
            :class="`ccsdk-podium-slot--${slot.pos}`"
          >
            <span v-if="slot.pos === 1" class="mat-icon ccsdk-podium-crown">emoji_events</span>
            <div class="ccsdk-podium-avatar" :class="`ccsdk-podium-avatar--${slot.pos}`">
              {{ initials(slot.player.name) }}
              <span class="ccsdk-podium-rank">{{ slot.player.rank }}</span>
            </div>
            <div class="ccsdk-podium-name">{{ slot.player.name }}</div>
            <div class="ccsdk-podium-value">{{ primaryVal(slot.player) }}</div>
            <div v-if="secondaryLabel" class="ccsdk-podium-meta">
              {{ secondaryLabel }} {{ secondaryVal(slot.player) }}
            </div>
          </div>
        </div>

        <!-- Rank 4+ flat list -->
        <div v-if="rest.length" class="ccgame-sdk-ranking ccsdk-rest">
          <RankCard
            v-for="p in rest"
            :key="p.rank"
            :rank="p.rank"
            :name="p.name"
            :primary="primaryVal(p)"
            :primaryLabel="activeSub?.label"
            :secondary="secondaryVal(p)"
            :secondaryLabel="secondaryLabel"
          />
        </div>
      </template>

      <div v-else class="ccgame-sdk-empty">Chưa có dữ liệu</div>
    </template>

    <!-- No ranking types configured -->
    <div v-else class="ccgame-sdk-empty">Chưa có dữ liệu</div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import RankCard from './RankCard.vue'

const props = defineProps({
  types: { type: Array, default: () => [] },
  items: { type: Object, default: () => ({}) },
  active: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})
const emit = defineEmits(['update:active'])

const activeSub = computed(() => props.types.find(t => t.key === props.active))
const players = computed(() => {
  if (!activeSub.value) return []
  return props.items[activeSub.value.key] || []
})

// Podium = top-3, sắp lại thứ tự hiển thị #2 - #1 - #3 (center cao). Chỉ render slot có thật
// để server thưa (0/1/2 player) không vỡ.
const podium = computed(() => {
  const top = players.value.slice(0, 3)
  const order = [
    { idx: 1, pos: 2 }, // trái
    { idx: 0, pos: 1 }, // giữa, cao nhất
    { idx: 2, pos: 3 }, // phải
  ]
  return order
    .filter(o => top[o.idx])
    .map(o => ({ player: top[o.idx], pos: o.pos }))
})
const rest = computed(() => players.value.slice(3))
const secondaryLabel = computed(() => activeSub.value?.secondary_label || '')

function initials(name) {
  const chars = Array.from((name || '').trim())
  if (!chars.length) return '?'
  return chars.slice(0, 2).join('').toUpperCase()
}

function primaryVal(p) {
  const m = activeSub.value?.metric
  if (!m) return ''
  const v = p[m]
  if (m === 'power' && typeof v === 'number') {
    return v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : v.toLocaleString()
  }
  return v ?? ''
}

function secondaryVal(p) {
  const m = activeSub.value?.secondary_metric
  if (!m) return ''
  const v = p[m]
  return v ?? ''
}

watch(() => props.types, (types) => {
  if (types.length && !props.active) {
    emit('update:active', types[0].key)
  }
}, { immediate: true })
</script>

<style scoped>
.ccsdk-ranking-status {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 32px 16px;
  font-size: 12px;
  color: #8888aa;
}

.ccsdk-ranking-status--error {
  color: #f44336;
}

.ccsdk-ranking-spinner {
  width: 24px;
  height: 24px;
  border: 2px solid rgba(201,169,78,0.2);
  border-top-color: #c9a94e;
  border-radius: 50%;
  animation: ccsdk-rank-spin 0.7s linear infinite;
}

@keyframes ccsdk-rank-spin {
  to { transform: rotate(360deg); }
}

/* Podium top-3 */
.ccsdk-podium {
  display: flex;
  justify-content: center;
  align-items: flex-end;
  gap: 6px;
  padding: 12px 2px 14px;
  margin-bottom: 6px;
  border-bottom: 1px solid #1a1a2a;
}
.ccsdk-podium-slot {
  flex: 1 1 0;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
}
.ccsdk-podium-slot--1 { padding-bottom: 6px; }
.ccsdk-podium-slot--2,
.ccsdk-podium-slot--3 { padding-bottom: 0; opacity: 0.95; }

.ccsdk-podium-crown {
  font-size: 18px;
  color: #c9a94e;
  line-height: 1;
  margin-bottom: 2px;
  filter: drop-shadow(0 0 4px rgba(201,169,78,0.5));
}

.ccsdk-podium-avatar {
  position: relative;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  color: #0d0d14;
  background: #2a2a3d;
  border: 2px solid #2a2a3d;
}
.ccsdk-podium-avatar--1 {
  width: 50px;
  height: 50px;
  font-size: 16px;
  background: linear-gradient(135deg, #e6c766 0%, #a3812d 100%);
  border-color: #c9a94e;
  box-shadow: 0 0 12px rgba(201,169,78,0.35);
}
.ccsdk-podium-avatar--2 {
  background: linear-gradient(135deg, #9a9ab4 0%, #6e6e8a 100%);
  border-color: #6e6e8a;
  color: #0d0d14;
}
.ccsdk-podium-avatar--3 {
  background: linear-gradient(135deg, #a8723e 0%, #5c3a1e 100%);
  border-color: #5c3a1e;
  color: #f0d8b8;
}

.ccsdk-podium-rank {
  position: absolute;
  bottom: -4px;
  right: -4px;
  min-width: 16px;
  height: 16px;
  padding: 0 3px;
  border-radius: 8px;
  background: #10101a;
  border: 1px solid #2a2a3d;
  color: #c2c2e0;
  font-size: 9px;
  font-weight: 700;
  line-height: 14px;
  text-align: center;
}
.ccsdk-podium-slot--1 .ccsdk-podium-rank {
  background: #c9a94e;
  border-color: #c9a94e;
  color: #0d0d14;
}

.ccsdk-podium-name {
  margin-top: 8px;
  max-width: 100%;
  font-size: 10px;
  font-weight: 600;
  color: #c2c2e0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ccsdk-podium-value {
  margin-top: 2px;
  font-size: 12px;
  font-weight: 700;
  color: #c9a94e;
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
}
.ccsdk-podium-meta {
  margin-top: 1px;
  font-size: 8px;
  color: #4a4a6a;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
</style>
