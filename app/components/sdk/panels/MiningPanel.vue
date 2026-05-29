<script setup lang="ts">
import { computed } from 'vue'
import { sdkReadMessage } from '~/utils/sdkReadMessage'
import type { MiningReadResult } from '~~/types/sdk'

const route = useRoute()

const { data, pending, error } = useFetch<{ data: MiningReadResult }>('/api/mining', {
  key: 'sdk-mining',
  lazy: true,
  query: computed(() => ({ launch: route.query.launch })),
})

const mining = computed<MiningReadResult | null>(() => data.value?.data ?? null)
const machines = computed(() => mining.value?.machines ?? [])

const balanceTarget = computed(() => mining.value?.balance ?? null)
const balanceDisplay = useCountUp(balanceTarget)
const hasBalance = computed(() => !mining.value?.sealed && mining.value?.balance != null)

const sealedMessage = (reason?: MiningReadResult['reason']) =>
  sdkReadMessage(reason, 'Chưa có dữ liệu mining từ legacy', {
    session_untrusted: 'Phiên launch không hợp lệ. Vào lại từ CCGame để xem máy đào.',
    db_error: 'Tạm thời không đọc được dữ liệu mining từ legacy.',
    no_legacy_data: 'Chưa có dữ liệu diamond generator từ legacy cho tài khoản này.',
  })

const formatBalance = (value: number | null | undefined): string => {
  if (value == null) return '—'
  return value.toLocaleString('vi-VN')
}
</script>

<template>
  <div class="space-y-3 sdk-pop">
    <div class="flex items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Máy đào / Monument
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        Chỉ đọc
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
      v-else-if="error || mining?.sealed"
      variant="subtle"
      class="border border-muted bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2 py-8 text-center">
        <UIcon
          name="i-heroicons-cpu-chip"
          class="size-8 text-dimmed"
        />
        <p class="text-sm text-muted">
          {{ sealedMessage(mining?.reason) }}
        </p>
      </div>
    </UCard>

    <template v-else>
      <UCard
        variant="subtle"
        class="border border-info/30 bg-elevated text-center sdk-shimmer"
        :class="{ 'sdk-glow': hasBalance }"
        :style="{ '--sdk-glow-color': 'rgb(56 189 248 / 0.5)' }"
        :ui="{ body: 'py-4 px-2' }"
      >
        <p class="text-xs text-muted mb-1 flex items-center justify-center gap-1">
          <UIcon
            name="i-heroicons-cube-transparent"
            class="size-3.5 text-info"
          />
          Kim cương đào (diamond_wallets)
        </p>
        <p class="text-2xl font-bold tabular-nums text-info">
          {{ hasBalance ? balanceDisplay.toLocaleString('vi-VN') : formatBalance(mining?.balance) }}
        </p>
      </UCard>

      <UCard
        v-if="machines.length === 0"
        variant="subtle"
        class="border border-muted bg-muted/30"
      >
        <div class="flex flex-col items-center gap-2 py-6 text-center">
          <p class="text-sm text-muted">
            Chưa có máy đào từ legacy
          </p>
        </div>
      </UCard>

      <div
        v-else
        class="space-y-2"
      >
        <UCard
          v-for="(machine, idx) in machines"
          :key="machine.machineIndex"
          variant="subtle"
          class="border border-muted bg-elevated sdk-pop sdk-press"
          :style="{ '--sdk-i': idx }"
          :ui="{ body: 'space-y-1 p-3' }"
        >
          <p class="text-sm font-semibold text-highlighted">
            Máy #{{ machine.machineIndex }}
          </p>
          <p class="text-xs text-muted">
            Lv {{ machine.level }} · Speed {{ machine.speedLevel }} · Storage {{ machine.storageLevel }} · Eff {{ machine.efficiencyLevel }}
          </p>
          <p class="text-[11px] text-dimmed">
            Rate {{ machine.baseRate }} · Capacity {{ machine.capacity }}
          </p>
        </UCard>
      </div>
    </template>
  </div>
</template>
