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
  <div class="w-full border-b border-muted bg-muted/60 px-1 py-1.5">
    <div class="flex w-full items-center gap-0.5">
      <button
        v-for="tab in items"
        :key="tab.value"
        type="button"
        class="flex h-9 min-w-0 flex-1 items-center justify-center rounded-lg transition-colors"
        :class="activeTab === tab.value
          ? 'bg-primary text-inverted shadow-sm'
          : 'text-muted hover:bg-elevated hover:text-default'"
        :title="tab.label"
        :aria-label="tab.label"
        :aria-pressed="activeTab === tab.value"
        @click="activeTab = tab.value"
      >
        <UIcon
          :name="tab.icon"
          class="size-4 shrink-0"
        />
      </button>
    </div>
  </div>
</template>
