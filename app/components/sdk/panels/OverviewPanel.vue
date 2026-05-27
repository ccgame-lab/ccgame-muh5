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

const authMode = computed(() => bootstrap.value?.data?.session?.authMode || 'guest')
const isGreenJade = computed(() => authMode.value === 'greenjade')

const displayName = computed(() =>
  bootstrap.value?.data?.player?.displayName
  || bootstrap.value?.data?.player?.username
  || 'Khách',
)

const shortPlayerId = computed(() => {
  const id = bootstrap.value?.data?.player?.id || 'guest'
  if (id.length <= 18) {
    return id
  }
  return `${id.slice(0, 10)}...${id.slice(-5)}`
})

const greenJadeLoginUrl = useGreenJadeLoginUrl()

const serverLabel = computed(() => {
  if (!bootstrap.value?.data?.session?.playAllowed) {
    return 'Launch niêm phong'
  }
  return bootstrap.value?.data?.server?.name || 'S1'
})
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
    class="space-y-3"
  >
    <UCard
      variant="subtle"
      class="border border-muted bg-elevated"
    >
      <div class="flex flex-col items-center gap-2 px-2 py-4 text-center">
        <UAvatar
          src="https://avatars.githubusercontent.com/u/739984?v=4"
          size="lg"
          class="ring-2 ring-primary"
        />
        <div class="min-w-0 space-y-0.5">
          <h3 class="text-base font-semibold text-highlighted truncate max-w-full">
            {{ displayName }}
          </h3>
          <p class="font-mono text-[11px] text-dimmed truncate max-w-full">
            {{ shortPlayerId }}
          </p>
        </div>
        <UBadge
          :color="isGreenJade ? 'success' : 'warning'"
          variant="subtle"
          size="sm"
        >
          {{ isGreenJade ? 'GreenJade' : 'Khách' }}
        </UBadge>
      </div>
    </UCard>

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

    <div class="grid grid-cols-2 gap-2">
      <UCard
        variant="subtle"
        class="border border-muted bg-elevated"
        :ui="{ body: 'flex items-center gap-2.5 p-3' }"
      >
        <UIcon
          name="i-heroicons-server"
          class="size-5 shrink-0 text-dimmed"
        />
        <div class="min-w-0">
          <p class="text-[10px] font-medium uppercase tracking-wide text-dimmed">
            Máy chủ
          </p>
          <p class="text-sm font-semibold text-default truncate">
            {{ serverLabel }}
          </p>
        </div>
      </UCard>

      <UCard
        variant="subtle"
        class="border border-muted bg-elevated"
        :ui="{ body: 'flex items-center gap-2.5 p-3' }"
      >
        <UIcon
          name="i-heroicons-star"
          class="size-5 shrink-0 text-warning"
        />
        <div class="min-w-0">
          <p class="text-[10px] font-medium uppercase tracking-wide text-dimmed">
            VIP
          </p>
          <p class="text-sm font-semibold text-default">
            VIP 0
          </p>
        </div>
      </UCard>
    </div>
  </div>
</template>
