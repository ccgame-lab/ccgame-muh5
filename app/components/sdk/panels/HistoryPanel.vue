<script setup lang="ts">
import type { WalletBalance, Transaction } from '~~/types/sdk'

const { data: walletData, pending } = useFetch<{ data: { balance: WalletBalance, history: Transaction[] } }>('/api/wallet', {
  key: 'wallet-data',
  lazy: true,
})
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Lịch sử giao dịch ví
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        Ví tài khoản
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
      v-else-if="!walletData?.data.history || walletData.data.history.length === 0"
      variant="subtle"
      class="border border-muted bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2 py-8 text-center">
        <UIcon
          name="i-heroicons-lock-closed"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          Lịch sử giao dịch trống hoặc đang đồng bộ
        </p>
      </div>
    </UCard>

    <div
      v-else
      class="space-y-2"
    >
      <UCard
        v-for="tx in walletData.data.history"
        :key="tx.id"
        variant="subtle"
        class="border border-muted bg-elevated"
        :ui="{ body: 'flex items-center justify-between gap-3 p-3' }"
      >
        <div class="min-w-0">
          <p class="text-sm text-default truncate">
            {{ tx.description }}
          </p>
          <p class="text-xs text-dimmed">
            {{ new Date(tx.createdAt).toLocaleDateString() }}
          </p>
        </div>
        <UBadge
          :color="tx.type === 'deposit' ? 'success' : 'neutral'"
          variant="subtle"
          class="shrink-0 font-semibold tabular-nums"
        >
          {{ tx.type === 'deposit' ? '+' : '-' }}{{ tx.amount }}
        </UBadge>
      </UCard>
    </div>
  </div>
</template>
