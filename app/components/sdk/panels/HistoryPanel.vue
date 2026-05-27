<script setup lang="ts">
import type { WalletBalance, Transaction } from '~~/types/sdk'

const { data: walletData, pending } = useFetch<{ data: { balance: WalletBalance, history: Transaction[] } }>('/api/wallet', {
  key: 'wallet-data',
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
        Lịch sử giao dịch ví
      </h3>
      <UBadge
        color="neutral"
        variant="solid"
        size="xs"
      >
        Ví tài khoản
      </UBadge>
    </div>

    <div class="space-y-2">
      <div
        v-for="tx in walletData?.data.history"
        :key="tx.id"
        class="flex items-center justify-between p-3 bg-gray-900 rounded-lg border border-gray-800"
      >
        <div>
          <p class="text-sm text-gray-200">
            {{ tx.description }}
          </p>
          <p class="text-xs text-gray-500">
            {{ new Date(tx.createdAt).toLocaleDateString() }}
          </p>
        </div>
        <div
          :class="tx.type === 'deposit' ? 'text-green-500' : 'text-gray-300'"
          class="font-semibold text-sm"
        >
          {{ tx.type === 'deposit' ? '+' : '-' }}{{ tx.amount }}
        </div>
      </div>
    </div>
  </div>
</template>
