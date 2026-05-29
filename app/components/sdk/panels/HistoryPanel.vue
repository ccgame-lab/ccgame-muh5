<script setup lang="ts">
import { computed } from 'vue'
import { sdkReadMessage } from '~/utils/sdkReadMessage'
import type { HistoryReadResult, Transaction } from '~~/types/sdk'

const route = useRoute()

const { data, pending, error } = useFetch<{ data: HistoryReadResult }>('/api/history', {
  key: 'sdk-history',
  lazy: true,
  query: computed(() => ({ launch: route.query.launch })),
})

const history = computed<HistoryReadResult | null>(() => data.value?.data ?? null)
const items = computed<Transaction[]>(() => history.value?.items ?? [])

const sealedMessage = (reason?: HistoryReadResult['reason']) =>
  sdkReadMessage(reason, 'Chưa có dữ liệu lịch sử từ legacy', {
    session_untrusted: 'Phiên launch không hợp lệ. Vào lại từ CCGame để xem lịch sử ví.',
    db_error: 'Tạm thời không đọc được lịch sử giao dịch từ legacy.',
  })

const formatDate = (iso: string): string => {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
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
        Lịch sử giao dịch ví
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        Legacy portal
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
      v-else-if="error || history?.sealed"
      variant="subtle"
      class="border border-muted bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2 py-8 text-center">
        <UIcon
          name="i-heroicons-lock-closed"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          {{ sealedMessage(history?.reason) }}
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
          name="i-heroicons-clock"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          Chưa có giao dịch Wcoin/Wpoint từ legacy
        </p>
      </div>
    </UCard>

    <div
      v-else
      class="space-y-2"
    >
      <UCard
        v-for="(tx, idx) in items"
        :key="tx.id"
        variant="subtle"
        class="border border-muted bg-elevated sdk-pop"
        :style="{ '--sdk-i': idx }"
        :ui="{ body: 'flex items-center justify-between gap-3 p-3' }"
      >
        <div class="min-w-0">
          <p class="text-sm text-default truncate">
            {{ tx.description }}
          </p>
          <p class="text-xs text-dimmed">
            {{ formatDate(tx.createdAt) }} · {{ tx.currency.toUpperCase() }} · {{ tx.type }}
          </p>
        </div>
        <UBadge
          :color="tx.amount >= 0 ? 'success' : 'neutral'"
          variant="subtle"
          class="shrink-0 font-semibold tabular-nums"
        >
          {{ tx.amount >= 0 ? '+' : '' }}{{ tx.amount.toLocaleString('vi-VN') }}
        </UBadge>
      </UCard>
    </div>
  </div>
</template>
