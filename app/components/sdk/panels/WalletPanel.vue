<script setup lang="ts">
import { computed } from 'vue'
import type { WalletReadResult, WalletSealedReason } from '~~/types/sdk'

const route = useRoute()

const { data, pending } = useFetch<{ data: WalletReadResult }>('/api/wallet', {
  key: 'sdk-wallet',
  lazy: true,
  query: computed(() => ({ launch: route.query.launch })),
})

const wallet = computed<WalletReadResult | null>(() => data.value?.data ?? null)

const formatBalance = (value: number | null | undefined): string => {
  if (value == null) return '—'
  return value.toLocaleString('vi-VN')
}

const sealedMessage = computed<string>(() => {
  const reason: WalletSealedReason | undefined = wallet.value?.reason
  switch (reason) {
    case 'db_not_configured':
      return 'Ví đang niêm phong. Hệ thống đồng bộ số dư chưa sẵn sàng.'
    case 'session_untrusted':
      return 'Phiên launch không hợp lệ. Vào lại từ CCGame để xem số dư.'
    case 'username_missing':
      return 'Phiên launch thiếu tên tài khoản game.'
    case 'account_not_found':
      return 'Chưa có hồ sơ ví cho tài khoản này.'
    case 'db_error':
      return 'Tạm thời không đọc được ví. Thử lại sau.'
    default:
      return 'Ví đang niêm phong.'
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-200">
        Tổng quan số dư
      </h3>
      <UBadge
        color="neutral"
        variant="solid"
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
        class="w-6 h-6 animate-spin text-gray-500"
      />
    </div>

    <div
      v-else
      class="grid grid-cols-2 gap-4"
    >
      <UCard class="bg-gray-900 border-gray-800 p-0 text-center py-4">
        <p class="text-xs text-gray-400 mb-1">
          WCoin
        </p>
        <p
          class="text-xl font-bold"
          :class="wallet?.sealed ? 'text-gray-500' : 'text-yellow-500'"
        >
          {{ formatBalance(wallet?.balance.wcoin) }}
        </p>
      </UCard>
      <UCard class="bg-gray-900 border-gray-800 p-0 text-center py-4">
        <p class="text-xs text-gray-400 mb-1">
          WPoint
        </p>
        <p
          class="text-xl font-bold"
          :class="wallet?.sealed ? 'text-gray-500' : 'text-blue-500'"
        >
          {{ formatBalance(wallet?.balance.wpoint) }}
        </p>
      </UCard>
    </div>

    <FeatureLocked
      v-if="!wallet || wallet.sealed"
      title="Ví đang niêm phong"
      :description="sealedMessage"
      icon="i-heroicons-wallet"
    />

    <FeatureLocked
      v-else
      title="Chỉ hiển thị số dư"
      description="Tính năng nạp, đổi, lịch sử giao dịch tạm đóng cho đợt cộng đồng. Mọi thay đổi số dư do hệ thống xử lý."
      icon="i-heroicons-lock-closed"
    />
  </div>
</template>
