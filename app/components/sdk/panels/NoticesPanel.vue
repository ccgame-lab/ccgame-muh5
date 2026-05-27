<script setup lang="ts">
import { computed } from 'vue'
import type { Notice } from '~~/types/sdk'

const { data, pending, error } = useFetch<{ data: Notice[] }>('/api/notices', {
  key: 'sdk-notices',
  lazy: true,
})

const notices = computed<Notice[]>(() => data.value?.data ?? [])

const formatDate = (iso?: string): string => {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  }
  catch {
    return iso.slice(0, 10)
  }
}

const typeBadgeColor = (type: Notice['type']): 'info' | 'success' | 'warning' => {
  if (type === 'success') return 'success'
  if (type === 'warning') return 'warning'
  return 'info'
}
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold text-gray-200">
        Thông báo hệ thống
      </h3>
      <UBadge
        color="neutral"
        variant="solid"
        size="xs"
      >
        Tin tức S1
      </UBadge>
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
      v-else-if="error || notices.length === 0"
      class="text-center py-8 text-sm text-gray-500 bg-gray-900 rounded-lg border border-gray-800"
    >
      <UIcon
        name="i-heroicons-megaphone"
        class="w-8 h-8 text-gray-600 mb-2 mx-auto"
      />
      <p>Chưa có thông báo</p>
    </div>

    <div
      v-for="notice in notices"
      v-else
      :key="notice.id"
      class="p-3 bg-gray-900 border border-gray-800 rounded-lg flex flex-col gap-2"
    >
      <div class="flex items-start justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0">
          <UIcon
            v-if="notice.icon"
            :name="notice.icon"
            class="w-4 h-4 text-primary-400 shrink-0"
          />
          <h4 class="text-sm font-bold text-white truncate">
            {{ notice.title }}
          </h4>
        </div>
        <UBadge
          :color="typeBadgeColor(notice.type)"
          variant="subtle"
          size="xs"
          class="shrink-0"
        >
          {{ notice.type }}
        </UBadge>
      </div>

      <p class="text-xs text-gray-400 leading-relaxed">
        {{ notice.body }}
      </p>

      <div
        v-if="notice.publishedAt || notice.link"
        class="flex items-center justify-between text-[11px] text-gray-500"
      >
        <span>{{ formatDate(notice.publishedAt) }}</span>
        <a
          v-if="notice.link"
          :href="notice.link"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary-400 hover:underline inline-flex items-center gap-1"
        >
          Xem thêm
          <UIcon
            name="i-heroicons-arrow-top-right-on-square"
            class="w-3 h-3"
          />
        </a>
      </div>
    </div>
  </div>
</template>
