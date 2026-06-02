<template>
  <div class="ccgame-sdk-pane">
    <div class="ccgame-sdk-player-header">
      <div class="ccgame-sdk-player-name">{{ player.name }}</div>
    </div>

    <div class="ccgame-sdk-row" v-if="player.vip">
      <span class="ccgame-sdk-label">Hạng VIP</span>
      <span class="ccgame-sdk-value ccgame-sdk-value--gold">VIP {{ player.vip }}</span>
    </div>
    <div class="ccgame-sdk-row">
      <span class="ccgame-sdk-label">Cấp độ</span>
      <span class="ccgame-sdk-value ccgame-sdk-value--gold">Lv.{{ player.level }}</span>
    </div>
    <div class="ccgame-sdk-row">
      <span class="ccgame-sdk-label">Tôm</span>
      <span class="ccgame-sdk-value ccgame-sdk-value--gold">{{ fmt(wallet.tom) }}</span>
    </div>
    <div class="ccgame-sdk-row">
      <span class="ccgame-sdk-label">WCoin</span>
      <span class="ccgame-sdk-value ccgame-sdk-value--gold">{{ fmt(wallet.wcoin) }}</span>
    </div>
    <div class="ccgame-sdk-row">
      <span class="ccgame-sdk-label">WPoint</span>
      <span class="ccgame-sdk-value ccgame-sdk-value--gold">{{ fmt(wallet.wpoint) }}</span>
    </div>

    <FeatureGrid :features="features" />
  </div>
</template>

<script setup>
import FeatureGrid from './FeatureGrid.vue'

defineProps({
  player: { type: Object, default: () => ({ id:0, name:'', level:0, vip:0 }) },
  wallet: { type: Object, default: () => ({ tom:0, wcoin:0, wpoint:0 }) },
  features: { type: Array, default: () => [] },
})

function fmt(n) {
  return (n || 0).toLocaleString()
}
</script>
