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
      <span class="ccgame-sdk-label">TÔM</span>
      <div class="ccgame-sdk-tom-group">
        <span class="ccgame-sdk-value ccgame-sdk-value--gold">{{ fmt(wallet.tom) }}</span>
        <a class="ccgame-sdk-tom-plus" :href="suppliesUrl" target="_blank" rel="noopener" title="Tiếp tế qua GreenJade ID">+</a>
      </div>
    </div>
    <div class="ccgame-sdk-row">
      <span class="ccgame-sdk-label">WCoin</span>
      <span class="ccgame-sdk-value ccgame-sdk-value--gold">{{ fmt(wallet.wcoin) }}</span>
    </div>
    <div class="ccgame-sdk-row">
      <span class="ccgame-sdk-label">WPoint</span>
      <span class="ccgame-sdk-value ccgame-sdk-value--gold">{{ fmt(wallet.wpoint) }}</span>
    </div>

    <MiningCard />

    <div v-if="quickActions.length" class="ccgame-sdk-feature-grid">
      <a
        v-for="a in quickActions"
        :key="a.key"
        :href="a.href || '#'"
        :target="a.href && a.href.startsWith('http') ? '_blank' : '_top'"
        class="ccgame-sdk-btn"
      >
        <span class="ccgame-sdk-btn-label">{{ a.label }}</span>
        <span v-if="a.note" class="ccgame-sdk-btn-note">{{ a.note }}</span>
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import MiningCard from './MiningCard.vue'

const props = defineProps({
  player: { type: Object, default: () => ({ id:0, name:'', level:0, vip:0 }) },
  wallet: { type: Object, default: () => ({ tom:0, wcoin:0, wpoint:0 }) },
  features: { type: Array, default: () => [] },
})

const quickActions = computed(() => {
  return props.features
    .filter(f => f.active && f.key !== 'topup' && f.key !== 'wallet')
})

const suppliesUrl = 'https://id.greenjade.net/supplies?game=muh5&server=s1&return=' + encodeURIComponent(location.href)

function fmt(n) {
  return (n || 0).toLocaleString()
}
</script>
