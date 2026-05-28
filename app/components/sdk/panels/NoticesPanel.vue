<script setup lang="ts">
import { computed } from 'vue'
import { sdkReadMessage } from '~/utils/sdkReadMessage'
import type { Notice, NoticesReadResult } from '~~/types/sdk'

const { data, pending, error } = useFetch<{ data: NoticesReadResult }>('/api/notices', {
  key: 'sdk-notices',
  lazy: true,
})

const noticesResult = computed<NoticesReadResult | null>(() => data.value?.data ?? null)
const notices = computed<Notice[]>(() => noticesResult.value?.items ?? [])

const emptyMessage = computed(() =>
  sdkReadMessage(noticesResult.value?.reason, 'Chưa có thông báo từ legacy', {
    db_error: 'Tạm thời không đọc được thông báo từ legacy.',
  }),
)

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
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Thông báo hệ thống
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
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
        class="size-6 animate-spin text-dimmed"
      />
    </div>

    <UCard
      v-else-if="error || noticesResult?.sealed || notices.length === 0"
      variant="subtle"
      class="border border-muted bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2 py-8 text-center">
        <UIcon
          name="i-heroicons-megaphone"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          {{ emptyMessage }}
        </p>
      </div>
    </UCard>

    <UCard
      v-for="notice in notices"
      v-else
      :key="notice.id"
      variant="subtle"
      class="border border-muted bg-elevated"
      :ui="{ body: 'flex flex-col gap-2 p-3' }"
    >
      <div class="flex items-start justify-between gap-2">
        <div class="flex min-w-0 items-center gap-2">
          <UIcon
            v-if="notice.icon"
            :name="notice.icon"
            class="size-4 shrink-0 text-primary"
          />
          <h4 class="text-sm font-semibold text-highlighted truncate">
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

      <p class="text-xs text-muted leading-relaxed">
        {{ notice.body }}
      </p>

      <div
        v-if="notice.publishedAt || notice.link"
        class="flex items-center justify-between gap-2 text-[11px] text-dimmed"
      >
        <span>{{ formatDate(notice.publishedAt) }}</span>
        <UButton
          v-if="notice.link"
          :to="notice.link"
          target="_blank"
          color="primary"
          variant="link"
          size="xs"
          trailing-icon="i-heroicons-arrow-top-right-on-square"
          label="Xem thêm"
        />
      </div>
    </UCard>
  </div>
</template>
