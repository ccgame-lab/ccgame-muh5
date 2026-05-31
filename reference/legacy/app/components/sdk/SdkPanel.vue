<script setup lang="ts">
import { ref } from 'vue'
import { sdkConfig } from '~~/config/sdk.config'
import SdkTabs from './SdkTabs.vue'
import OverviewPanel from './panels/OverviewPanel.vue'
import DailyPanel from './panels/DailyPanel.vue'
import NoticesPanel from './panels/NoticesPanel.vue'
import GiftcodePanel from './panels/GiftcodePanel.vue'
import WalletPanel from './panels/WalletPanel.vue'
import HistoryPanel from './panels/HistoryPanel.vue'
import LeaderboardPanel from './panels/LeaderboardPanel.vue'
import MiningPanel from './panels/MiningPanel.vue'
import HallOfFamePanel from './panels/HallOfFamePanel.vue'
import SocialPanel from './panels/SocialPanel.vue'

const isOpen = defineModel<boolean>('isOpen', { default: false })

const activeTab = ref(sdkConfig.defaultTab)

function closePanel() {
  isOpen.value = false
}
</script>

<template>
  <div
    v-if="isOpen"
    class="dark fixed z-[100] flex flex-col bg-default ring-1 ring-primary/30 pointer-events-auto overflow-hidden contain-layout shadow-2xl shadow-primary/10 sdk-pop"
    :class="[
      'max-sm:bottom-0 max-sm:left-0 max-sm:right-0 max-sm:w-full max-sm:max-h-[80vh] max-sm:border-t max-sm:border-muted max-sm:rounded-t-2xl',
      'sm:top-1/2 sm:-translate-y-1/2 sm:right-4 sm:w-[min(380px,calc(100vw-2rem))] sm:max-h-[72vh] sm:border sm:border-muted sm:rounded-2xl',
    ]"
  >
    <div class="flex items-center gap-2 border-b border-muted bg-elevated px-3 py-2.5 shrink-0">
      <div class="min-w-0 flex-1">
        <h2 class="text-sm font-semibold text-highlighted leading-tight">
          MUH5 SDK
        </h2>
        <p class="text-[11px] text-muted leading-snug">
          Thông tin nhân vật
        </p>
      </div>
      <UButton
        icon="i-heroicons-x-mark"
        color="neutral"
        variant="ghost"
        size="sm"
        aria-label="Đóng SDK"
        @click="closePanel"
      />
    </div>

    <SdkTabs
      v-model:active-tab="activeTab"
      :tabs="sdkConfig.tabs"
    />

    <div class="flex-1 min-h-0 overflow-y-auto bg-default p-3 sm:p-4">
      <OverviewPanel
        v-if="activeTab === 'overview'"
        @close="closePanel"
      />
      <DailyPanel v-else-if="activeTab === 'daily'" />
      <NoticesPanel v-else-if="activeTab === 'notices'" />
      <GiftcodePanel v-else-if="activeTab === 'giftcode'" />
      <WalletPanel v-else-if="activeTab === 'wallet'" />
      <HistoryPanel v-else-if="activeTab === 'history'" />
      <LeaderboardPanel v-else-if="activeTab === 'leaderboard'" />
      <MiningPanel v-else-if="activeTab === 'mining'" />
      <HallOfFamePanel v-else-if="activeTab === 'halloffame'" />
      <SocialPanel v-else-if="activeTab === 'social'" />
    </div>
  </div>
</template>
