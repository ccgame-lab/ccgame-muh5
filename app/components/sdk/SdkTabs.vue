<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  tabs: Array<{ id: string, label: string, icon: string }>
}>()

const activeTab = defineModel<string>('activeTab', { required: true })

const SHORT_LABELS: Record<string, string> = {
  overview: 'Tổng quan',
  daily: 'Hôm nay',
  notices: 'TB',
  giftcode: 'Code',
  wallet: 'Ví',
  history: 'LS',
  leaderboard: 'BXH',
  mining: 'Đào',
  halloffame: 'Vinh',
  social: 'HĐ',
}

const items = computed(() =>
  props.tabs.map(tab => ({
    icon: tab.icon,
    value: tab.id,
    label: SHORT_LABELS[tab.id] ?? tab.label,
  })),
)
</script>

<template>
  <div class="w-full overflow-x-auto overscroll-x-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
    <UTabs
      v-model="activeTab"
      :items="items"
      color="primary"
      variant="pill"
      size="sm"
      :content="false"
      class="w-max min-w-full shrink-0"
      :ui="{
        list: 'w-max min-w-max flex-nowrap gap-0.5 rounded-none border-b border-muted bg-muted/60 px-1 py-1.5 whitespace-nowrap',
        trigger: 'shrink-0 whitespace-nowrap max-sm:px-2 sm:px-2.5',
        label: 'max-sm:sr-only sm:not-sr-only sm:text-[11px] sm:whitespace-nowrap',
        leadingIcon: 'size-4 shrink-0',
      }"
    />
  </div>
</template>
