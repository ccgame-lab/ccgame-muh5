<script setup lang="ts">
const route = useRoute()

const { data: bootstrap, pending } = useFetch<{
  data: {
    session: { source: string, trusted: boolean, playAllowed: boolean }
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
