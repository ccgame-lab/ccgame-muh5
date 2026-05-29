<script setup lang="ts">
const route = useRoute()

const { data: bootstrap, pending } = useFetch<{
  data: {
    session: {
      authMode: 'guest' | 'greenjade'
      source: string
      trusted: boolean
      playAllowed: boolean
    }
    player: { id: string, username?: string, displayName: string } | null
    server: { id: number, key: string, name: string, srvaddr: string, srvport: string } | null
  }
}>('/api/bootstrap', {
  key: 'bootstrap-data',
  query: computed(() => ({
    launch: route.query.launch,
    user: route.query.user,
    userId: route.query.userId,
  })),
  lazy: true,
})

const session = computed(() => bootstrap.value?.data?.session ?? null)

// 'done' and 'pending' only when safely inferable from existing session state.
// Everything else stays 'unknown' -> "Chưa xác định". Read-only status, no mutation.
type ItemState = 'done' | 'pending' | 'unknown'

const trusted = computed(() => session.value?.trusted === true)
const playAllowed = computed(() => session.value?.playAllowed === true)
const onServer = computed(() => playAllowed.value && session.value?.source === 'signed_launch')

const items = computed<Array<{ key: string, label: string, state: ItemState }>>(() => [
  {
    key: 'login',
    label: 'Đăng nhập hôm nay',
    state: trusted.value ? 'done' : 'unknown',
  },
  {
    key: 'server',
    label: 'Vào máy chủ S1',
    state: onServer.value ? 'done' : (trusted.value ? 'pending' : 'unknown'),
  },
  {
    key: 'character',
    label: 'Kiểm tra nhân vật',
    state: 'unknown',
  },
  {
    key: 'activity',
    label: 'Tham gia hoạt động trong game',
    state: 'unknown',
  },
  {
    key: 'comeback',
    label: 'Quay lại xem cập nhật',
    state: 'unknown',
  },
])

const STATE_META: Record<ItemState, { icon: string, color: string, label: string }> = {
  done: { icon: 'i-heroicons-check-circle', color: 'text-primary', label: 'Đã xong' },
  pending: { icon: 'i-heroicons-clock', color: 'text-warning', label: 'Đang chờ' },
  unknown: { icon: 'i-heroicons-minus-circle', color: 'text-dimmed', label: 'Chưa xác định' },
}
</script>

<template>
  <div class="space-y-3 sdk-pop">
    <div class="flex flex-wrap items-center gap-2">
      <h3 class="text-sm font-semibold text-highlighted">
        Hôm nay
      </h3>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        Chỉ đọc
      </UBadge>
    </div>

    <p class="text-[11px] leading-snug text-muted">
      Danh sách theo dõi phiên chơi. Trạng thái đọc từ phiên hiện tại, chỉ dùng để theo dõi.
    </p>

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
      class="overflow-hidden rounded-xl border border-muted bg-elevated divide-y divide-muted/60"
    >
      <div
        v-for="(item, idx) in items"
        :key="item.key"
        class="flex items-center gap-2.5 px-3 py-2.5 sdk-pop"
        :style="{ '--sdk-i': idx }"
      >
        <UIcon
          :name="STATE_META[item.state].icon"
          class="size-4 shrink-0"
          :class="STATE_META[item.state].color"
        />
        <span
          class="text-sm truncate"
          :class="item.state === 'done' ? 'text-default' : 'text-muted'"
        >
          {{ item.label }}
        </span>
        <UBadge
          :color="item.state === 'done' ? 'primary' : (item.state === 'pending' ? 'warning' : 'neutral')"
          variant="subtle"
          size="xs"
          class="ml-auto shrink-0"
        >
          {{ STATE_META[item.state].label }}
        </UBadge>
      </div>
    </div>
  </div>
</template>
