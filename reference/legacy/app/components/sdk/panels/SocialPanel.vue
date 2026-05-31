<script setup lang="ts">
import { computed } from 'vue'
import { sdkReadMessage } from '~/utils/sdkReadMessage'
import type { SocialEvent, SocialReadResult } from '~~/types/sdk'

const { data, pending, error } = useFetch<{ data: SocialReadResult }>('/api/social', {
  key: 'sdk-social',
  lazy: true,
})

const result = computed<SocialReadResult | null>(() => data.value?.data ?? null)
const items = computed<SocialEvent[]>(() => result.value?.items ?? [])

const emptyMessage = computed(() =>
  sdkReadMessage(result.value?.reason, 'Chưa có hoạt động nào từ legacy', {
    db_error: 'Tạm thời không đọc được bảng tin hoạt động từ legacy.',
  }),
)

const EVENT_LABEL: Record<string, string> = {
  recharge: 'Nạp tài khoản',
  purchase_item: 'Mua vật phẩm',
  milestone: 'Cột mốc',
}

const eventLabel = (type: string): string => EVENT_LABEL[type] || type

const eventIcon = (type: string): string => {
  if (type === 'recharge') return 'i-heroicons-banknotes'
  if (type === 'purchase_item') return 'i-heroicons-shopping-bag'
  if (type === 'milestone') return 'i-heroicons-flag'
  return 'i-heroicons-bolt'
}

const formatTime = (iso: string): string => {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
  }
  catch {
    return iso.slice(0, 16)
  }
}
</script>

<template>
  <div class="space-y-3 sdk-pop">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Hoạt động
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        <span class="mr-1 inline-block size-1.5 rounded-full bg-error sdk-live-dot" />
        Bảng tin S1
      </UBadge>
    </div>

    <div
      v-if="pending"
      class="flex justify-center py-8"
    >
      <UIcon
        name="i-heroicons-arrow-path"
        class="size-6 animate-spin text-dimmed"
      />
    </div>

    <UCard
      v-else-if="error || result?.sealed || items.length === 0"
      variant="subtle"
      class="border border-muted bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2 py-8 text-center">
        <UIcon
          name="i-heroicons-bolt"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          {{ emptyMessage }}
        </p>
      </div>
    </UCard>

    <div
      v-else
      class="space-y-2"
    >
      <UCard
        v-for="(event, idx) in items"
        :key="event.id"
        variant="subtle"
        class="border border-muted bg-elevated sdk-pop"
        :style="{ '--sdk-i': idx }"
        :ui="{ body: 'flex items-center gap-3 p-3' }"
      >
        <UIcon
          :name="eventIcon(event.eventType)"
          class="size-5 shrink-0 text-primary"
        />
        <div class="min-w-0 flex-1">
          <p class="text-sm text-default truncate">
            <span class="font-semibold text-highlighted">{{ event.username || 'Người chơi ẩn danh' }}</span>
            · {{ eventLabel(event.eventType) }}
          </p>
          <p class="text-[11px] text-dimmed">
            {{ formatTime(event.createdAt) }}
          </p>
        </div>
      </UCard>
    </div>
  </div>
</template>
