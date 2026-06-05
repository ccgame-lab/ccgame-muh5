<template>
  <div class="cug-wrap">
    <div class="cug-grid">
      <button
        v-for="tile in tiles"
        :key="tile.key"
        class="cug-tile"
        :class="[
          `cug-tile--${tile.state}`,
          { 'cug-tile--active': activePanel === tile.key }
        ]"
        :disabled="tile.state === 'maintenance'"
        @click="onTile(tile)"
      >
        <span class="cug-tile-icon">{{ tile.icon }}</span>
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

const props = defineProps({
  activePanel: { type: String, default: null },
})

const emit = defineEmits(['toggle-panel'])

const { state } = useSdkState()

function fmt(n) {
  return (n || 0).toLocaleString()
}

const tiles = computed(() => {
  const spinRemaining = state.spinStatus?.spins_remaining ?? 0
  const effPct = state.spinStatus ? null : null // unused
  const miningEff = 0 // could read from quote if stored in state

  return [
    {
      key: 'wallet',
      icon: '💰',
      label: 'Ví',
      stat: `${fmt(state.wallet.points)} PT`,
      state: 'active',
      action: 'panel',
    },
    {
      key: 'giftcode',
      icon: '🎁',
      label: 'Giftcode',
      stat: null,
      state: 'active',
      action: 'panel',
    },
    {
      key: 'shop',
      icon: '🛒',
      label: 'Cửa hàng',
      stat: state.wallet.tom != null ? `${fmt(state.wallet.tom)} 🦐` : null,
      state: 'active',
      action: 'panel',
    },
    {
      key: 'spin',
      icon: '🎰',
      label: 'Vòng quay',
      stat: spinRemaining > 0 ? `${spinRemaining} lượt` : null,
      state: 'active',
      action: 'panel',
    },
    {
      key: 'mining',
      icon: '⛏️',
      label: 'Đào KC',
      stat: null,
      state: 'active',
      action: 'panel',
    },
    {
      key: 'support',
      icon: '💬',
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
  emit('toggle-panel', tile.key)
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
  border-color: rgba(120,100,255,0.3);
}

.cug-tile--active {
  background: #1a1a30;
  border-color: rgba(120,100,255,0.55);
  box-shadow: 0 0 0 1px rgba(120,100,255,0.2);
}

.cug-tile:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.cug-tile-icon {
  font-size: 18px;
  line-height: 1;
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
