<script setup lang="ts">
import { computed } from 'vue'
import { sdkReadMessage } from '~/utils/sdkReadMessage'
import type { WalletReadResult } from '~~/types/sdk'

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

const sealedMessage = computed(() =>
  sdkReadMessage(wallet.value?.reason, 'Ví đang niêm phong.', {
    db_not_configured: 'Ví đang niêm phong. Hệ thống đồng bộ số dư chưa sẵn sàng.',
    session_untrusted: 'Phiên launch không hợp lệ. Vào lại từ CCGame để xem số dư.',
    account_not_found: 'Chưa có hồ sơ ví cho tài khoản này.',
    db_error: 'Tạm thời không đọc được ví. Thử lại sau.',
  }),
)
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Tổng quan số dư
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

    <div
      v-else
      class="grid grid-cols-2 gap-2"
    >
      <UCard
        variant="subtle"
        class="border border-muted bg-elevated text-center"
        :ui="{ body: 'py-4 px-2' }"
      >
        <p class="text-xs text-muted mb-1">
          WCoin
        </p>
        <p
          class="text-xl font-bold"
          :class="wallet?.sealed ? 'text-dimmed' : 'text-highlighted'"
        >
          {{ formatBalance(wallet?.balance.wcoin) }}
        </p>
      </UCard>
      <UCard
        variant="subtle"
        class="border border-muted bg-elevated text-center"
        :ui="{ body: 'py-4 px-2' }"
      >
        <p class="text-xs text-muted mb-1">
          WPoint
        </p>
        <p
          class="text-xl font-bold"
          :class="wallet?.sealed ? 'text-dimmed' : 'text-highlighted'"
        >
          {{ formatBalance(wallet?.balance.wpoint) }}
        </p>
      </UCard>
    </div>

    <FeatureLocked
      v-if="!wallet || wallet.sealed"
      title="Sắp mở"
      :description="sealedMessage"
      icon="i-heroicons-wallet"
    />

    <UAlert
      v-else
      color="info"
      variant="subtle"
      icon="i-heroicons-lock-closed"
      title="Chỉ hiển thị số dư"
      description="Tính năng nạp, đổi, lịch sử giao dịch tạm đóng cho đợt cộng đồng. Mọi thay đổi số dư do hệ thống xử lý."
    />
  </div>
</template>
