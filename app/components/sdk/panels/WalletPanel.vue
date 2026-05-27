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
    class="space-y-6"
  >
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-200">
        Balance overview
      </h3>
      <UBadge
        color="neutral"
        variant="solid"
        size="xs"
      >
        Read-Only
      </UBadge>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <UCard class="bg-gray-900 border-gray-800 p-0 text-center py-4">
        <p class="text-xs text-gray-400 mb-1">
          Coin
        </p>
        <p class="text-xl font-bold text-yellow-500">
          {{ walletData?.data.balance.coin.toLocaleString() }}
        </p>
      </UCard>
      <UCard class="bg-gray-900 border-gray-800 p-0 text-center py-4">
        <p class="text-xs text-gray-400 mb-1">
          Diamond
        </p>
        <p class="text-xl font-bold text-blue-500">
          {{ walletData?.data.balance.diamond.toLocaleString() }}
        </p>
      </UCard>
    </div>
  </div>
</template>
