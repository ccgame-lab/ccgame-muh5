<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { LeaderboardEntry, LeaderboardTab } from '~~/types/sdk'

const JOB_LABEL: Record<number, string> = {
  1: 'Chiến Binh',
  2: 'Ma Đấu Sĩ',
  3: 'Tiên Nữ',
}

const tab = ref<LeaderboardTab>('power')

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
  if (!Number.isFinite(value)) return '—'
  return value.toLocaleString('vi-VN')
}
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-sm font-semibold text-gray-200">
        Bảng xếp hạng
      </h3>
      <UBadge
        color="neutral"
        variant="solid"
        size="xs"
      >
        Cao thủ S1
      </UBadge>
    </div>

    <div class="flex gap-1 p-1 bg-gray-900 rounded-lg border border-gray-800">
      <button
        type="button"
        class="flex-1 text-xs font-semibold py-1.5 rounded-md transition-colors"
        :class="tab === 'power' ? 'bg-primary-500/20 text-primary-300' : 'text-gray-400 hover:text-gray-200'"
        @click="tab = 'power'"
      >
        Lực chiến
      </button>
      <button
        type="button"
        class="flex-1 text-xs font-semibold py-1.5 rounded-md transition-colors"
        :class="tab === 'level' ? 'bg-primary-500/20 text-primary-300' : 'text-gray-400 hover:text-gray-200'"
        @click="tab = 'level'"
      >
        Cấp độ
      </button>
    </div>

    <div
      v-if="pending"
      class="flex justify-center py-8"
    >
      <UIcon
        name="i-heroicons-arrow-path"
        class="w-6 h-6 animate-spin text-gray-500"
      />
    </div>

    <div
      v-else-if="error || entries.length === 0"
      class="text-center py-8 text-sm text-gray-500 bg-gray-900 rounded-lg border border-gray-800"
    >
      <UIcon
        name="i-heroicons-trophy"
        class="w-8 h-8 text-gray-600 mb-2 mx-auto"
      />
      <p>Bảng xếp hạng {{ tabLabel.toLowerCase() }} đang niêm phong</p>
      <p class="text-[11px] text-gray-600 mt-1">
        Dữ liệu sẽ hiện khi máy chủ S1 mở hoặc khi cấu hình DB sẵn sàng.
      </p>
    </div>

    <div
      v-else
      class="space-y-2"
    >
      <div
        v-for="entry in entries"
        :key="`${entry.rank}-${entry.accountname || entry.username}`"
        class="p-2 bg-gray-900 border border-gray-800 rounded-lg flex items-center gap-3"
      >
        <div
          class="w-8 h-8 flex items-center justify-center rounded-full font-bold text-sm shrink-0"
          :class="{
            'bg-yellow-500/20 text-yellow-500': entry.rank === 1,
            'bg-gray-400/20 text-gray-400': entry.rank === 2,
            'bg-amber-700/20 text-amber-600': entry.rank === 3,
            'text-gray-500': entry.rank > 3,
          }"
        >
          {{ entry.rank }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-white truncate">
            {{ entry.username }}
          </p>
          <p class="text-[11px] text-gray-500 truncate">
            <template v-if="entry.level">
              Lv {{ entry.level }}
            </template>
            <template v-if="entry.job">
              · {{ JOB_LABEL[entry.job] || 'Khác' }}
            </template>
          </p>
        </div>
        <div class="text-right shrink-0">
          <p class="text-xs text-primary-400 font-bold">
            {{ formatScore(entry.score) }}
          </p>
          <p class="text-[10px] text-gray-500 uppercase tracking-wide">
            {{ tabLabel }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
