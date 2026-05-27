<script setup lang="ts">
import { ref } from 'vue'
import { sdkConfig } from '~~/config/sdk.config'
import OverviewPanel from './panels/OverviewPanel.vue'
import NoticesPanel from './panels/NoticesPanel.vue'
import GiftcodePanel from './panels/GiftcodePanel.vue'
import WalletPanel from './panels/WalletPanel.vue'
import HistoryPanel from './panels/HistoryPanel.vue'
import LeaderboardPanel from './panels/LeaderboardPanel.vue'
import MiningPanel from './panels/MiningPanel.vue'

const isOpen = defineModel<boolean>('isOpen')
const activeTab = ref(sdkConfig.defaultTab)

const closePanel = () => {
  isOpen.value = false
}
</script>

<template>
  <div
    class="fixed z-90 flex flex-col bg-gray-950 shadow-2xl transition-all duration-300 pointer-events-auto overflow-hidden"
    :class="[
      isOpen ? 'opacity-100 visible scale-100' : 'opacity-0 invisible scale-95 pointer-events-none translate-y-8 sm:translate-y-0 sm:translate-x-8',
      'max-sm:bottom-0 max-sm:left-0 max-sm:right-0 max-sm:w-full max-sm:max-h-[80vh] max-sm:border-t max-sm:border-gray-800 max-sm:rounded-t-[20px]',
      'sm:top-1/2 sm:-translate-y-1/2 sm:right-6 sm:w-[380px] sm:max-h-[72vh] sm:border sm:border-gray-800 sm:rounded-[20px]',
    ]"
  >
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800 shrink-0">
      <div class="flex flex-col">
        <h2 class="text-lg font-bold text-white">
          {{ sdkConfig.app.name }} SDK
        </h2>
        <p class="text-xs text-gray-500">
          Player Info (Mock)
        </p>
      </div>
      <UButton
        icon="i-heroicons-x-mark"
        color="neutral"
        variant="ghost"
        @click="closePanel"
      />
    </div>

    <!-- Tabs -->
    <SdkTabs
      v-model:active-tab="activeTab"
      :tabs="sdkConfig.tabs"
    />

    <!-- Panel Content -->
    <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
      <OverviewPanel v-if="activeTab === 'overview'" />
      <NoticesPanel v-if="activeTab === 'notices'" />
      <GiftcodePanel v-if="activeTab === 'giftcode'" />
      <WalletPanel v-if="activeTab === 'wallet'" />
      <HistoryPanel v-if="activeTab === 'history'" />
      <LeaderboardPanel v-if="activeTab === 'leaderboard'" />
      <MiningPanel v-if="activeTab === 'mining'" />
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
  background-color: #374151; /* gray-700 */
  border-radius: 10px;
}
</style>
