<template>
  <div class="cug-wrap">
    <div class="cug-grid">
      <button
        v-for="tile in tiles"
        :key="tile.key"
        class="cug-tile"
        :class="`cug-tile--${tile.state}`"
        :disabled="tile.state === 'maintenance'"
        @click="onTile(tile)"
      >
        <span class="cug-tile-icon mat-icon">{{ tile.icon }}</span>
        <span class="cug-tile-label">{{ tile.label }}</span>
        <span v-if="tile.stat" class="cug-tile-stat">{{ tile.stat }}</span>
        <span v-if="tile.state === 'maintenance'" class="cug-tile-badge">BT</span>
        <span v-else-if="tile.state === 'soon'" class="cug-tile-badge cug-tile-badge--soon">SỚM</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useSdkState } from '../composables/useSdkState.js'

const emit = defineEmits(['navigate'])

const { state } = useSdkState()

function fmt(n) {
  return (n || 0).toLocaleString()
}

// Tile = lối tắt sang tab tương ứng (đồng bộ với tab bar Material icon, không còn panel inline).
const tiles = computed(() => {
  const spinRemaining = state.spinStatus?.spins_remaining ?? 0

  return [
    {
      key: 'topup',
      icon: 'payments',
      label: 'Nạp Tôm',
      stat: null,
      state: 'active',
      action: 'navigate',
    },
    {
      key: 'shop',
      icon: 'storefront',
      label: 'Cửa hàng',
      stat: state.wallet.tom != null ? `${fmt(state.wallet.tom)} Tôm` : null,
      state: 'active',
      action: 'navigate',
    },
    {
      key: 'spin',
      icon: 'cyclone',
      label: 'Vòng quay',
      stat: spinRemaining > 0 ? `${spinRemaining} lượt` : null,
      state: 'active',
      action: 'navigate',
    },
    {
      key: 'mining',
      icon: 'construction',
      label: 'Đào KC',
      stat: null,
      state: 'active',
      action: 'navigate',
    },
    {
      key: 'giftcode',
      icon: 'redeem',
      label: 'Giftcode',
      stat: null,
      state: 'active',
      action: 'navigate',
    },
    {
      key: 'support',
      icon: 'chat',
      label: 'Hỗ trợ',
      stat: null,
      state: 'active',
      action: 'link',
    },
  ]
})

function onTile(tile) {
  if (tile.state === 'maintenance' || tile.state === 'soon') return
  if (tile.action === 'link') {
    window.open('https://fb.com/muonhan5.online', '_blank', 'noopener')
    return
  }
  emit('navigate', tile.key)
}
</script>

<style scoped>
.cug-wrap {
  margin-bottom: 12px;
}

.cug-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
}

.cug-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  padding: 8px 4px;
  background: #12121d;
  border: 1px solid #1e1e32;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
  position: relative;
  min-height: 62px;
}

.cug-tile:hover:not(:disabled) {
  background: #181828;
  border-color: rgba(201,169,78,0.35);
  transform: translateY(-1px);
}

.cug-tile:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.cug-tile-icon {
  font-size: 21px;
  line-height: 1;
  color: #c9a94e;
}

.cug-tile-label {
  font-size: 9px;
  font-weight: 700;
  color: #8888aa;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.cug-tile-stat {
  font-size: 9px;
  color: #c9a94e;
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}

.cug-tile-badge {
  position: absolute;
  top: 3px;
  right: 4px;
  font-size: 7px;
  font-weight: 800;
  padding: 1px 3px;
  border-radius: 2px;
  background: #3a3a3a;
  color: #888;
  letter-spacing: 0.04em;
}

.cug-tile-badge--soon {
  background: rgba(240,192,96,0.15);
  color: #c9a94e;
}
</style>
