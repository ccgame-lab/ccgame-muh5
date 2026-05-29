<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  tabs: Array<{ id: string, label: string, icon: string }>
}>()

const activeTab = defineModel<string>('activeTab', { required: true })

const SHORT_LABELS: Record<string, string> = {
  overview: 'Tổng quan',
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
  <UTabs
    v-model="activeTab"
    :items="items"
    color="primary"
    variant="pill"
    size="sm"
    :content="false"
    class="w-full shrink-0"
    :ui="{
      list: 'w-full gap-0.5 rounded-none border-b border-muted bg-muted/60 px-1 py-1.5',
      trigger: 'max-sm:min-w-9 max-sm:px-2 sm:px-2.5',
      label: 'max-sm:sr-only sm:not-sr-only sm:text-[11px]',
      leadingIcon: 'size-4 shrink-0',
    }"
  />
</template>
