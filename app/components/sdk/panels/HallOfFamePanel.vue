<script setup lang="ts">
import { computed } from 'vue'
import { sdkReadMessage } from '~/utils/sdkReadMessage'
import type { HallOfFameEntry, HallOfFameReadResult } from '~~/types/sdk'

const { data, pending, error } = useFetch<{ data: HallOfFameReadResult }>('/api/hall-of-fame', {
  key: 'sdk-hall-of-fame',
  lazy: true,
})

const result = computed<HallOfFameReadResult | null>(() => data.value?.data ?? null)
const items = computed<HallOfFameEntry[]>(() => result.value?.items ?? [])

const emptyMessage = computed(() =>
  sdkReadMessage(result.value?.reason, 'Chưa có dữ liệu vinh danh từ legacy', {
    db_error: 'Tạm thời không đọc được bảng vinh danh từ legacy.',
  }),
)

const formatScore = (value: number | null): string => {
  if (value == null) return '—'
  return value.toLocaleString('vi-VN')
}

const categoryColor = (category: HallOfFameEntry['category']): 'warning' | 'primary' =>
  category === 'donate' ? 'warning' : 'primary'
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Vinh danh
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        Chỉ đọc
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
          name="i-heroicons-sparkles"
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
        v-for="entry in items"
        :key="entry.id"
        variant="subtle"
        class="border border-muted bg-elevated"
        :ui="{ body: 'space-y-2 p-3' }"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-highlighted truncate">
              {{ entry.categoryLabel || (entry.category === 'donate' ? 'Vua Nạp' : 'Vua Lực Chiến') }}
            </p>
            <p class="text-[11px] text-dimmed truncate">
              {{ entry.serverName }}
            </p>
          </div>
          <UBadge
            :color="categoryColor(entry.category)"
            variant="subtle"
            size="xs"
            class="shrink-0"
          >
            {{ entry.serverStatus === 'ongoing' ? 'Đang diễn ra' : 'Đã kết thúc' }}
          </UBadge>
        </div>

        <div class="flex items-center justify-between gap-2">
          <p class="text-sm text-default truncate">
            {{ entry.playerName || 'Đang ẩn danh' }}
          </p>
          <div class="shrink-0 text-right">
            <p class="text-xs font-bold text-primary">
              {{ formatScore(entry.scoreValue) }}
            </p>
            <p class="text-[10px] uppercase tracking-wide text-dimmed">
              {{ entry.scoreLabel }}
            </p>
          </div>
        </div>

        <div
          v-if="entry.rewards.length"
          class="flex flex-wrap gap-1"
        >
          <UBadge
            v-for="(reward, idx) in entry.rewards"
            :key="idx"
            color="neutral"
            variant="subtle"
            size="xs"
          >
            {{ reward }}
          </UBadge>
        </div>
      </UCard>
    </div>
  </div>
</template>
