<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { LeaderboardEntry, LeaderboardTab } from '~~/types/sdk'

const JOB_LABEL: Record<number, string> = {
  1: 'Chiến Binh',
  2: 'Ma Đấu Sĩ',
  3: 'Tiên Nữ',
}

const tab = ref<LeaderboardTab>('power')

const tabItems = [
  { label: 'Lực chiến', value: 'power' as const },
  { label: 'Cấp độ', value: 'level' as const },
]

const { data, pending, error, refresh } = useFetch<{ data: { tab: LeaderboardTab, entries: LeaderboardEntry[] } }>(
  '/api/leaderboard',
  {
    key: 'sdk-leaderboard',
    lazy: true,
    query: computed(() => ({ tab: tab.value })),
  },
)

watch(tab, () => {
  refresh()
})

const entries = computed<LeaderboardEntry[]>(() => data.value?.data?.entries ?? [])

const tabLabel = computed(() => (tab.value === 'level' ? 'Cấp độ' : 'Lực chiến'))

const formatScore = (value: number): string => {
  if (!Number.isFinite(value)) return '-'
  return value.toLocaleString('vi-VN')
}

const rankBadgeColor = (rank: number): 'warning' | 'neutral' | 'primary' => {
  if (rank === 1) return 'warning'
  if (rank <= 3) return 'primary'
  return 'neutral'
}

const rankMedal = (rank: number): string | null => {
  if (rank === 1) return '👑'
  if (rank === 2) return '🥈'
  if (rank === 3) return '🥉'
  return null
}
</script>

<template>
  <div class="space-y-3 sdk-pop">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Bảng xếp hạng
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        Cao thủ S1
      </UBadge>
    </div>

    <UTabs
      v-model="tab"
      :items="tabItems"
      color="primary"
      variant="pill"
      size="xs"
      :content="false"
      class="w-full"
      :ui="{ list: 'w-full bg-muted/60 p-1' }"
    />

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
      v-else-if="error || entries.length === 0"
      variant="subtle"
      class="border border-muted bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2 py-8 text-center">
        <UIcon
          name="i-heroicons-trophy"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          Chưa có dữ liệu bảng xếp hạng từ legacy
        </p>
        <p class="text-[11px] text-dimmed">
          Kiểm tra cấu hình DB game hoặc thử lại sau.
        </p>
      </div>
    </UCard>

    <div
      v-else
      class="space-y-2"
    >
      <UCard
        v-for="(entry, idx) in entries"
        :key="`${entry.rank}-${entry.accountname || entry.username}`"
        variant="subtle"
        class="border bg-elevated sdk-pop sdk-press"
        :class="entry.rank === 1
          ? 'border-warning/50 sdk-shimmer sdk-glow sdk-glow-gold'
          : entry.rank <= 3 ? 'border-primary/30 sdk-shimmer' : 'border-muted'"
        :style="{ '--sdk-i': idx }"
        :ui="{ body: 'flex items-center gap-3 p-2.5' }"
      >
        <UBadge
          :color="rankBadgeColor(entry.rank)"
          variant="subtle"
          class="size-8 shrink-0 justify-center font-bold"
        >
          <span v-if="rankMedal(entry.rank)">{{ rankMedal(entry.rank) }}</span>
          <span v-else>{{ entry.rank }}</span>
        </UBadge>
        <div class="min-w-0 flex-1">
          <p
            class="text-sm font-semibold truncate"
            :class="entry.rank === 1 ? 'text-highlighted' : 'text-highlighted'"
          >
            {{ entry.username }}
          </p>
          <p class="text-[11px] text-dimmed truncate">
            <template v-if="entry.level">
              Lv {{ entry.level }}
            </template>
            <template v-if="entry.job">
              · {{ JOB_LABEL[entry.job] || 'Khác' }}
            </template>
          </p>
        </div>
        <div class="shrink-0 text-right">
          <p
            class="text-xs font-bold tabular-nums"
            :class="entry.rank <= 3 ? 'sdk-shine-text' : 'text-primary'"
          >
            {{ formatScore(entry.score) }}
          </p>
          <p class="text-[10px] uppercase tracking-wide text-dimmed">
            {{ tabLabel }}
          </p>
        </div>
      </UCard>
    </div>
  </div>
</template>
