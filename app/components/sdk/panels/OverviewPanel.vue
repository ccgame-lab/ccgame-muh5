<script setup lang="ts">
const route = useRoute()
const runtimeConfig = useRuntimeConfig()

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

const authLabel = computed(() => {
  if (isGreenJade.value) {
    return bootstrap.value?.data?.player?.displayName || 'GreenJade'
  }
  return 'Khách'
})

const greenJadeLoginUrl = computed(() => {
  const base = String(runtimeConfig.public.ccgamePortalUrl || 'https://ccgame.org').replace(/\/+$/, '')
  return `${base}/api/auth/greenjade/start?returnTo=${encodeURIComponent('/play/muh5')}`
})

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
      class="w-8 h-8 animate-spin text-gray-500"
    />
  </div>
  <div
    v-else
    class="space-y-4"
  >
    <div class="flex flex-col items-center justify-center p-6 bg-gray-900 rounded-lg border border-gray-800">
      <UAvatar
        src="https://avatars.githubusercontent.com/u/739984?v=4"
        size="xl"
        class="mb-3 border-2 border-primary-500"
      />
      <h3 class="text-lg font-bold text-white">
        {{ bootstrap?.data?.player?.displayName || bootstrap?.data?.player?.username || 'Khách' }}
      </h3>
      <p class="text-xs text-gray-400">
        ID: {{ bootstrap?.data?.player?.id || 'guest' }}
      </p>
      <UBadge
        class="mt-2"
        :color="isGreenJade ? 'success' : 'warning'"
        variant="subtle"
      >
        {{ authLabel }}
      </UBadge>
    </div>

    <div
      v-if="!isGreenJade"
      class="rounded-lg border border-amber-900/40 bg-amber-950/20 p-3 space-y-2"
    >
      <p class="text-xs text-amber-200/90 leading-relaxed">
        Đang chơi bằng Khách. Đăng nhập GreenJade để giữ tiến trình tốt hơn.
      </p>
      <a
        :href="greenJadeLoginUrl"
        target="_parent"
        rel="noopener noreferrer"
        class="inline-flex items-center justify-center w-full rounded-md bg-emerald-700/80 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-600 transition-colors"
      >
        Đăng nhập GreenJade
      </a>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="p-3 bg-gray-900 rounded-lg border border-gray-800 flex items-center gap-3">
        <UIcon
          name="i-heroicons-server"
          class="w-5 h-5 text-gray-400"
        />
        <div>
          <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">
            Server
          </p>
          <p class="text-sm font-semibold text-gray-200">
            {{ serverLabel }}
          </p>
        </div>
      </div>
      <div class="p-3 bg-gray-900 rounded-lg border border-gray-800 flex items-center gap-3">
        <UIcon
          name="i-heroicons-star"
          class="w-5 h-5 text-yellow-500"
        />
        <div>
          <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">
            VIP Level
          </p>
          <p class="text-sm font-semibold text-gray-200">
            VIP 0
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
