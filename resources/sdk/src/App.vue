<template>
  <div class="ccgame-sdk-panel" :class="{ 'ccgame-sdk-panel--open': open }">
    <div class="ccgame-sdk-header">
      <div class="ccgame-sdk-header-left">
        <div class="ccgame-sdk-header-title">{{ state.server.name || 'CCGame' }}</div>
        <div class="ccgame-sdk-header-user" v-if="state.loaded || state.error">
          <span v-if="state.loaded" class="ccgame-sdk-header-user-name">{{ state.player.name }}</span>
          <span v-if="state.error" class="ccgame-sdk-header-user-name" style="color:#f44336">{{ state.error }}</span>
        </div>
      </div>
      <button class="ccgame-sdk-close" @click="open=false">&times;</button>
    </div>

    <div class="ccgame-sdk-tabs">
      <button
        v-for="tab in state.tabs"
        :key="tab.key"
        class="ccgame-sdk-tab"
        :class="{ 'ccgame-sdk-tab--active': activeTab === tab.key }"
        @click="switchTab(tab.key)"
      >{{ tab.label }}</button>
    </div>

    <div class="ccgame-sdk-body" v-show="state.loaded">
      <OverviewPane v-show="activeTab==='overview'" :player="state.player" :wallet="state.wallet" :features="state.features" :checkin="state.checkin" :refreshing="state.refreshing" @checkin="doCheckin" @refresh="refreshWallet" />
      <DonatePane v-show="activeTab==='donate'" :features="state.features" :loaded="state.loaded" :items="state.pshopItems" :items-loading="state.pshopLoading" :items-error="state.pshopError" :buy="buyWithTom" />
      <RankingPane v-show="activeTab==='ranking'" :types="state.rankingTypes" :items="state.rankingItems" :active="state.rankingActive" :loading="state.rankingLoading" :error="state.rankingError" @update:active="setRankingActive" />
      <ChangelogPane v-show="activeTab==='changelog'" :entries="state.changelog" />
    </div>

    <div class="ccgame-sdk-body" v-show="!state.loaded && !state.error">
      <div class="ccgame-sdk-loading-overlay">
        <div class="ccgame-sdk-spinner"></div>
        <div class="ccgame-sdk-loading-text">Đang tải...</div>
      </div>
    </div>

    <div class="ccgame-sdk-body" v-show="!state.loaded && state.error">
      <div class="ccgame-sdk-error-msg">{{ state.error }}</div>
    </div>
  </div>
  <div
    class="ccgame-sdk-fab"
    ref="fabRef"
    :class="{ 'ccgame-sdk-fab--open': open }"
      @mousedown.prevent="onDragStart"
      @touchstart="onDragStart"
    @click="onFabClick"
  >CC</div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useSdkState } from './composables/useSdkState.js'
import OverviewPane from './components/OverviewPane.vue'
import RankingPane from './components/RankingPane.vue'
import ChangelogPane from './components/ChangelogPane.vue'
import DonatePane from './components/DonatePane.vue'

const { state, loadBootstrap, loadRanking, setRankingActive, doCheckin, refreshWallet, loadPshopItems, buyWithTom } = useSdkState()
const open = ref(false)
const activeTab = ref('overview')
const booted = ref(false)
const fabRef = ref(null)

// Dragging state
let dragging = false
let didDrag = false
let startX = 0
let startY = 0
let origX = 0
let origY = 0

function switchTab(key) {
  activeTab.value = key
  if (key === 'ranking' && !state.rankingLoaded) {
    loadRanking()
  }
  if (key === 'donate' && !state.pshopLoaded) {
    loadPshopItems()
  }
}

watch(open, async (v) => {
  if (v && !booted.value) {
    booted.value = true
    await loadBootstrap()
    if (state.tabs.length) activeTab.value = state.tabs[0].key || 'overview'
  }
})

function onDragStart(e) {
  const el = fabRef.value
  if (!el) return
  const touch = e.touches ? e.touches[0] : e
  startX = touch.clientX
  startY = touch.clientY
  origX = el.getBoundingClientRect().left
  origY = el.getBoundingClientRect().top
  dragging = false
  didDrag = false
  document.addEventListener('mousemove', onDragMove)
  document.addEventListener('mouseup', onDragEnd)
  document.addEventListener('touchmove', onDragMove, { passive: false })
  document.addEventListener('touchend', onDragEnd)
}

function onDragMove(e) {
  const el = fabRef.value
  if (!el) return
  const touch = e.touches ? e.touches[0] : e
  const dx = touch.clientX - startX
  const dy = touch.clientY - startY
  if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
    dragging = true
  }
  if (!dragging) return
  e.preventDefault()
  const newX = origX + dx
  const newY = origY + dy
  el.style.left = newX + 'px'
  el.style.top = newY + 'px'
  el.style.right = 'auto'
  el.style.bottom = 'auto'
}

function onDragEnd() {
  didDrag = dragging
  document.removeEventListener('mousemove', onDragMove)
  document.removeEventListener('mouseup', onDragEnd)
  document.removeEventListener('touchmove', onDragMove)
  document.removeEventListener('touchend', onDragEnd)
  dragging = false
}

function onFabClick() {
  if (didDrag) { didDrag = false; return }
  open.value = !open.value
}
</script>
