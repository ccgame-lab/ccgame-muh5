<template>
  <div class="ccgame-sdk-rank-card">
    <div class="ccgame-sdk-rank-badge" :class="badgeClass">{{ rank }}</div>
    <div class="ccgame-sdk-rank-body">
      <div class="ccgame-sdk-rank-name">{{ name }}</div>
      <div v-if="secondary || typeof secondary === 'number'" class="ccgame-sdk-rank-meta">
        <span>{{ secondaryLabel }} {{ secondary }}</span>
      </div>
    </div>
    <div class="ccgame-sdk-rank-metric">
      <span class="ccgame-sdk-rank-value">{{ primary }}</span>
      <span class="ccgame-sdk-rank-label">{{ primaryLabel }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  rank: { type: Number, default: 1 },
  name: { type: String, default: '' },
  primary: { type: [String, Number], default: '' },
  primaryLabel: { type: String, default: '' },
  secondary: { type: [String, Number], default: '' },
  secondaryLabel: { type: String, default: '' },
})

const badgeClass = computed(() => {
  if (props.rank === 1) return 'ccgame-sdk-rank-gold'
  if (props.rank === 2) return 'ccgame-sdk-rank-silver'
  if (props.rank === 3) return 'ccgame-sdk-rank-bronze'
  return ''
})
</script>
