<script setup lang="ts">
import { computed } from 'vue'
import { sdkReadMessage } from '~/utils/sdkReadMessage'
import type { GiftcodeReadResult } from '~~/types/sdk'

const route = useRoute()

const { data, pending, error } = useFetch<{ data: GiftcodeReadResult }>('/api/giftcode', {
  key: 'sdk-giftcode',
  lazy: true,
  query: computed(() => ({ launch: route.query.launch })),
})

const giftcode = computed<GiftcodeReadResult | null>(() => data.value?.data ?? null)
const items = computed(() => giftcode.value?.items ?? [])

const sealedMessage = (reason?: GiftcodeReadResult['reason']) =>
  sdkReadMessage(reason, 'Chưa có dữ liệu giftcode từ legacy', {
    session_untrusted: 'Phiên launch không hợp lệ. Vào lại từ CCGame để xem trạng thái giftcode cá nhân.',
    db_error: 'Tạm thời không đọc được giftcode từ legacy.',
  })

const usageLabel = (item: { usedCount: number, limitUsage: number }): string => {
  if (item.limitUsage <= 0) {
    return `${item.usedCount} lượt đã dùng`
  }
  return `${item.usedCount}/${item.limitUsage} lượt`
}

const usageRatio = (item: { usedCount: number, limitUsage: number }): number => {
  if (item.limitUsage <= 0) return 0
  return Math.min(item.usedCount / item.limitUsage, 1)
}

const isScarce = (item: { usedCount: number, limitUsage: number }): boolean => {
  return item.limitUsage > 0 && usageRatio(item) >= 0.7 && usageRatio(item) < 1
}

const isSoldOut = (item: { usedCount: number, limitUsage: number }): boolean => {
  return item.limitUsage > 0 && item.usedCount >= item.limitUsage
}
</script>

<template>
  <div class="space-y-3 sdk-pop">
    <div class="flex flex-wrap items-center gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Giftcode
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        Chỉ đọc
      </UBadge>
    </div>

    <UAlert
      color="warning"
      variant="subtle"
      icon="i-heroicons-lock-closed"
      title="Nhận thưởng chưa mở"
      description="Danh sách đọc từ legacy portal DB. Redeem/write chưa kích hoạt trên SDK Nuxt."
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
      v-else-if="error || giftcode?.reason === 'db_error'"
      variant="subtle"
      class="border border-muted bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2 py-8 text-center">
        <UIcon
          name="i-heroicons-exclamation-circle"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          {{ sealedMessage(giftcode?.reason) }}
        </p>
      </div>
    </UCard>

    <UCard
      v-else-if="items.length === 0"
      variant="subtle"
      class="border border-muted bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2 py-8 text-center">
        <UIcon
          name="i-heroicons-gift"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          Chưa có dữ liệu giftcode từ legacy
        </p>
      </div>
    </UCard>

    <div
      v-else
      class="space-y-2"
    >
      <UAlert
        v-if="giftcode?.sealed"
        color="info"
        variant="subtle"
        icon="i-heroicons-information-circle"
        :title="sealedMessage(giftcode.reason)"
      />

      <UCard
        v-for="(item, idx) in items"
        :key="item.id"
        variant="subtle"
        class="border bg-elevated sdk-pop sdk-press"
        :class="item.redeemed
          ? 'border-success/40'
          : isSoldOut(item) ? 'border-muted opacity-70' : isScarce(item) ? 'border-error/40' : 'border-muted'"
        :style="{ '--sdk-i': idx }"
        :ui="{ body: 'space-y-2 p-3' }"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="font-mono text-sm font-semibold text-highlighted truncate">
              {{ item.code }}
            </p>
          </div>
          <UBadge
            v-if="item.redeemed"
            color="success"
            variant="subtle"
            size="xs"
            class="shrink-0"
            icon="i-heroicons-check-badge"
          >
            Đã nhận
          </UBadge>
          <UBadge
            v-else-if="isSoldOut(item)"
            color="neutral"
            variant="subtle"
            size="xs"
            class="shrink-0"
          >
            Hết lượt
          </UBadge>
          <UBadge
            v-else-if="isScarce(item)"
            color="error"
            variant="subtle"
            size="xs"
            class="shrink-0"
          >
            Sắp hết
          </UBadge>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-[11px] text-dimmed">
          <span>{{ usageLabel(item) }}</span>
          <span>·</span>
          <span>{{ item.rewardType }}</span>
        </div>

        <div
          v-if="item.limitUsage > 0"
          class="h-1 w-full overflow-hidden rounded-full bg-muted/60"
        >
          <div
            class="h-full rounded-full sdk-bar-fill"
            :class="isSoldOut(item) ? 'bg-dimmed' : isScarce(item) ? 'bg-error' : 'bg-primary'"
            :style="{ '--sdk-bar': usageRatio(item) }"
          />
        </div>
      </UCard>
    </div>
  </div>
</template>
