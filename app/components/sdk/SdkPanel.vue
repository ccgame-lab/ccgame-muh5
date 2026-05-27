<script setup lang="ts">
import { ref } from 'vue'
import { sdkConfig } from '~~/config/sdk.config'
import SdkTabs from './SdkTabs.vue'
import OverviewPanel from './panels/OverviewPanel.vue'
import NoticesPanel from './panels/NoticesPanel.vue'
import GiftcodePanel from './panels/GiftcodePanel.vue'
import WalletPanel from './panels/WalletPanel.vue'
import HistoryPanel from './panels/HistoryPanel.vue'
import LeaderboardPanel from './panels/LeaderboardPanel.vue'
import MiningPanel from './panels/MiningPanel.vue'

const isOpen = defineModel<boolean>('isOpen', { default: false })

const activeTab = ref(sdkConfig.defaultTab)

function closePanel() {
  isOpen.value = false
}
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed z-[100] flex flex-col bg-elevated shadow-2xl pointer-events-auto overflow-hidden contain-layout"
    :class="[
      'max-sm:bottom-0 max-sm:left-0 max-sm:right-0 max-sm:w-full max-sm:max-h-[80vh] max-sm:border-t max-sm:border-muted max-sm:rounded-t-[20px]',
      'sm:top-1/2 sm:-translate-y-1/2 sm:right-6 sm:w-[380px] sm:max-h-[72vh] sm:border sm:border-muted sm:rounded-[20px]',
    ]"
  >
    <div class="flex items-center justify-between px-4 py-3 border-b border-muted shrink-0">
      <div class="flex flex-col">
        <h2 class="text-lg font-bold text-highlighted">
          {{ sdkConfig.app.name }} SDK
        </h2>
        <p class="text-xs text-muted">
          Thông tin nhân vật
        </p>
      </div>
      <UButton
        icon="i-heroicons-x-mark"
        color="neutral"
        variant="ghost"
        aria-label="Đóng SDK"
        @click="closePanel"
      />
    </div>

    <SdkTabs
      v-model:active-tab="activeTab"
      :tabs="sdkConfig.tabs"
    />

    <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
      <OverviewPanel v-if="activeTab === 'overview'" />
      <NoticesPanel v-else-if="activeTab === 'notices'" />
      <GiftcodePanel v-else-if="activeTab === 'giftcode'" />
      <WalletPanel v-else-if="activeTab === 'wallet'" />
      <HistoryPanel v-else-if="activeTab === 'history'" />
      <LeaderboardPanel v-else-if="activeTab === 'leaderboard'" />
      <MiningPanel v-else-if="activeTab === 'mining'" />
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #374151;
  border-radius: 10px;
}
</style>
