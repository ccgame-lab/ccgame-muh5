<script setup lang="ts">
const route = useRoute()

const emit = defineEmits<{ close: [] }>()

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
const player = computed(() => bootstrap.value?.data?.player ?? null)
const server = computed(() => bootstrap.value?.data?.server ?? null)

const authMode = computed(() => session.value?.authMode || 'guest')
const isGreenJade = computed(() => authMode.value === 'greenjade')

const displayName = computed(() =>
  player.value?.displayName
  || player.value?.username
  || 'Khách',
)

const shortPlayerId = computed(() => {
  const id = player.value?.id || 'guest'
  if (id.length <= 18) {
    return id
  }
  return `${id.slice(0, 10)}...${id.slice(-5)}`
})

const greenJadeLoginUrl = useGreenJadeLoginUrl()

const serverName = computed(() => {
  if (session.value?.playAllowed && server.value?.name) {
    return server.value.name
  }
  return 'S1'
})

const sessionLabel = computed(() => {
  const s = session.value
  if (!s) return 'Chưa xác định'
  if (s.authMode === 'greenjade' && s.trusted) return 'Đã xác thực'
  if (s.trusted && s.authMode === 'guest') return 'Khách'
  if (s.trusted) return 'Launch hợp lệ'
  return 'Chưa xác định'
})

// Character name/level/power is not part of bootstrap state; never fabricate it.
const characterLabel = computed(() => 'Đang đồng bộ')

const gameReady = computed(() => session.value?.playAllowed === true)
const gameStatusLabel = computed(() => gameReady.value ? 'Sẵn sàng vào game' : 'Đang chờ phiên')

const statusRows = computed(() => [
  { key: 'account', label: 'Tài khoản', value: displayName.value, icon: 'i-heroicons-user' },
  { key: 'server', label: 'Máy chủ', value: serverName.value, icon: 'i-heroicons-server' },
  { key: 'session', label: 'Phiên', value: sessionLabel.value, icon: 'i-heroicons-shield-check' },
  { key: 'character', label: 'Nhân vật', value: characterLabel.value, icon: 'i-heroicons-sparkles' },
])

function handleContinue() {
  emit('close')
}
</script>

<template>
  <div
    v-if="pending"
    class="flex justify-center py-8"
  >
    <UIcon
      name="i-heroicons-arrow-path"
      class="size-8 animate-spin text-dimmed"
    />
  </div>

  <div
    v-else
    class="space-y-3 sdk-pop"
  >
    <div class="flex items-center gap-3 rounded-xl border border-primary/30 bg-elevated px-3 py-2.5">
      <UAvatar
        :alt="displayName"
        icon="i-heroicons-user"
        size="md"
        class="ring-2 ring-primary/60 sdk-glow shrink-0"
      />
      <div class="min-w-0 flex-1">
        <h3 class="text-sm font-semibold text-highlighted truncate">
          {{ displayName }}
        </h3>
        <p class="font-mono text-[11px] text-dimmed truncate">
          {{ shortPlayerId }}
        </p>
      </div>
      <UBadge
        :color="isGreenJade ? 'success' : 'warning'"
        variant="subtle"
        size="sm"
        class="shrink-0"
      >
        {{ isGreenJade ? 'GreenJade' : 'Khách' }}
      </UBadge>
    </div>

    <div class="overflow-hidden rounded-xl border border-muted bg-elevated divide-y divide-muted/60">
      <div
        v-for="row in statusRows"
        :key="row.key"
        class="flex items-center gap-2.5 px-3 py-2.5"
      >
        <UIcon
          :name="row.icon"
          class="size-4 shrink-0 text-dimmed"
        />
        <span class="text-[11px] font-medium uppercase tracking-wide text-dimmed">
          {{ row.label }}
        </span>
        <span class="ml-auto text-sm font-semibold text-default truncate text-right">
          {{ row.value }}
        </span>
      </div>

      <div class="flex items-center gap-2.5 px-3 py-2.5">
        <UIcon
          :name="gameReady ? 'i-heroicons-play-circle' : 'i-heroicons-clock'"
          class="size-4 shrink-0"
          :class="gameReady ? 'text-primary' : 'text-dimmed'"
        />
        <span class="text-[11px] font-medium uppercase tracking-wide text-dimmed">
          Trạng thái game
        </span>
        <span
          class="ml-auto text-sm font-semibold truncate text-right"
          :class="gameReady ? 'text-primary' : 'text-default'"
        >
          {{ gameStatusLabel }}
        </span>
      </div>
    </div>

    <UAlert
      v-if="!isGreenJade"
      color="warning"
      variant="subtle"
      icon="i-heroicons-user-circle"
      title="Đang chơi bằng Khách"
      description="Đăng nhập GreenJade để giữ tiến trình tốt hơn."
    >
      <template #actions>
        <UButton
          :href="greenJadeLoginUrl"
          target="_parent"
          rel="noopener noreferrer"
          color="primary"
          size="sm"
          block
          label="Đăng nhập GreenJade"
        />
      </template>
    </UAlert>

    <UButton
      :icon="gameReady ? 'i-heroicons-play' : 'i-heroicons-arrow-path'"
      color="primary"
      size="md"
      block
      class="font-semibold"
      :label="gameReady ? 'Vào game' : 'Tiếp tục'"
      @click="handleContinue"
    />
  </div>
</template>
