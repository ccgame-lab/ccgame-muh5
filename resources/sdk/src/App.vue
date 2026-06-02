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
        @click="activeTab = tab.key"
      >{{ tab.label }}</button>
    </div>

    <div class="ccgame-sdk-body" v-if="state.loaded">
      <OverviewPane v-if="activeTab==='overview'" :player="state.player" :wallet="state.wallet" :features="state.features" />
      <RankingPane v-if="activeTab==='ranking'" :types="state.rankingTypes" :items="state.rankingItems" :active="state.rankingActive" @load="loadRanking" />
      <ChangelogPane v-if="activeTab==='changelog'" :entries="state.changelog" />
    </div>

    <div class="ccgame-sdk-body" v-else-if="state.error">
      <div class="ccgame-sdk-error-msg">{{ state.error }}</div>
    </div>

    <div class="ccgame-sdk-body" v-else>
      <div class="ccgame-sdk-loading-overlay">
        <div class="ccgame-sdk-spinner"></div>
        <div class="ccgame-sdk-loading-text">Đang tải...</div>
      </div>
    </div>
  </div>
  <div class="ccgame-sdk-fab" :class="{ 'ccgame-sdk-fab--open': open }" @click="open=!open">CC</div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useSdkState } from './composables/useSdkState.js'
import OverviewPane from './components/OverviewPane.vue'
import RankingPane from './components/RankingPane.vue'
import ChangelogPane from './components/ChangelogPane.vue'

const { state, loadBootstrap, loadRanking } = useSdkState()
const open = ref(false)
const activeTab = ref('overview')
const booted = ref(false)

watch(open, async (v) => {
  if (v && !booted.value) {
    booted.value = true
    await loadBootstrap()
    if (state.tabs.length) activeTab.value = state.tabs[0].key || 'overview'
  }
})
</script>
