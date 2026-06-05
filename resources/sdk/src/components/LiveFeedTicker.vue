<template>
  <div v-if="events.length" class="ccsdk-feed-wrap">
    <div class="ccsdk-feed-badge">LIVE</div>
    <div class="ccsdk-feed-track">
      <div class="ccsdk-feed-content" :style="{ animationDuration: duration + 's' }">
        <span v-for="(e, i) in doubled" :key="i" class="ccsdk-feed-item">
          {{ e.message }}<span class="ccsdk-feed-sep"> &bull; </span>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useSdkState } from '../composables/useSdkState.js'

const { state, loadFeed } = useSdkState()

const events = computed(() => state.feedEvents)
const doubled = computed(() => [...events.value, ...events.value])
const duration = computed(() => Math.max(25, events.value.length * 4))

let timer = null

onMounted(() => {
  loadFeed()
  timer = setInterval(loadFeed, 30000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<style scoped>
.ccsdk-feed-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  overflow: hidden;
  margin-bottom: 12px;
  height: 22px;
}

.ccsdk-feed-badge {
  flex-shrink: 0;
  font-size: 8px;
  font-weight: 800;
  color: #fff;
  background: #e74c3c;
  padding: 2px 5px;
  border-radius: 3px;
  letter-spacing: 0.08em;
  animation: ccsdk-live-blink 1.4s ease-in-out infinite;
}

@keyframes ccsdk-live-blink {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.45; }
}

.ccsdk-feed-track {
  flex: 1;
  overflow: hidden;
  min-width: 0;
}

.ccsdk-feed-content {
  display: inline-flex;
  white-space: nowrap;
  animation: ccsdk-ticker linear infinite;
  will-change: transform;
}

.ccsdk-feed-item {
  font-size: 10px;
  color: #9999bb;
}

.ccsdk-feed-sep {
  color: #44445a;
}

@keyframes ccsdk-ticker {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
</style>
