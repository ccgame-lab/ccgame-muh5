<template>
  <div v-if="activeFeatures.length" class="ccgame-sdk-feature-grid">
    <a
      v-for="f in activeFeatures"
      :key="f.key"
      :href="f.href || '#'"
      :target="f.href && f.href.startsWith('http') ? '_blank' : '_top'"
      class="ccgame-sdk-btn"
      :class="{ 'ccgame-sdk-btn--gold': f.key === 'topup' || f.key === 'wallet' }"
    >
      <span class="ccgame-sdk-btn-label">{{ f.label }}</span>
      <span v-if="f.note" class="ccgame-sdk-btn-note">{{ f.note }}</span>
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ features: { type: Array, default: () => [] } })
const activeFeatures = computed(() => props.features.filter(f => f.active))
</script>
