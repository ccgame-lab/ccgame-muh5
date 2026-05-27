<script setup lang="ts">
import type { LeaderboardEntry } from '~~/types/sdk'

const { data: boardData, pending } = useFetch<{ data: LeaderboardEntry[] }>('/api/leaderboard', {
  key: 'leaderboard-data',
  lazy: true,
})
</script>

<template>
  <div
    v-if="pending"
    class="flex justify-center py-8"
  >
    <UIcon
      name="i-heroicons-arrow-path"
      class="w-8 h-8 animate-spin text-gray-500"
    />
  </div>
  <div
    v-else
    class="space-y-2"
  >
    <div class="flex items-center justify-between mb-4">
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

    <div
      v-if="!boardData?.data || boardData.data.length === 0"
      class="text-center py-8 text-sm text-gray-500 bg-gray-900 rounded-lg border border-gray-800"
    >
      <UIcon
        name="i-heroicons-trophy"
        class="w-8 h-8 text-gray-600 mb-2 mx-auto"
      />
      <p>Bảng xếp hạng đang niêm phong chuẩn bị S1</p>
    </div>

    <div
      v-for="entry in boardData?.data"
      v-else
      :key="entry.rank"
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
      <div class="flex-1 overflow-hidden">
        <p class="text-sm font-bold text-white truncate">
          {{ entry.username }}
        </p>
      </div>
      <div class="text-right shrink-0">
        <p class="text-xs text-primary-400 font-bold">
          {{ entry.score.toLocaleString() }}
        </p>
      </div>
    </div>
  </div>
</template>
