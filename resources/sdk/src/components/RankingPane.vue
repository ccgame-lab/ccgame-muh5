<template>
  <div class="ccgame-sdk-pane">
    <div v-if="types.length" class="ccgame-sdk-subtabs">
      <button
        v-for="t in types" :key="t.key"
        class="ccgame-sdk-subtab"
        :class="{ 'ccgame-sdk-subtab--active': active === t.key }"
        @click="$emit('update:active', t.key)"
      >{{ t.label }}</button>
    </div>

    <div v-if="activeSub && players.length" class="ccgame-sdk-ranking">
      <RankCard
        v-for="(p, i) in players"
        :key="p.name"
        :rank="i+1"
        :name="p.name"
        :primary="primaryVal(p)"
        :primaryLabel="activeSub?.label"
        :secondary="secondaryVal(p)"
        :secondaryLabel="secondaryLabel"
      />
    </div>

    <div v-else class="ccgame-sdk-empty">Chưa có dữ liệu</div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import RankCard from './RankCard.vue'

const props = defineProps({
  types: { type: Array, default: () => [] },
  items: { type: Object, default: () => ({}) },
  active: { type: String, default: '' },
})
const emit = defineEmits(['load', 'update:active'])

const activeSub = computed(() => props.types.find(t => t.key === props.active))
const players = computed(() => {
  if (!activeSub.value) return []
  return (props.items[activeSub.value.key] || []).slice(0, 5)
})
const secondaryLabel = computed(() => activeSub.value?.secondary_label || '')

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

onMounted(() => emit('load'))
</script>
