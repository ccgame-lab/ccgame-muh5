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

      <div v-if="activeSub && players.length" class="ccgame-sdk-ranking">
        <RankCard
          v-for="(p, i) in players"
          :key="p.name"
          :rank="p.rank || i + 1"
          :name="p.name"
          :primary="primaryVal(p)"
          :primaryLabel="activeSub?.label"
          :secondary="secondaryVal(p)"
          :secondaryLabel="secondaryLabel"
        />
      </div>

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
</style>
