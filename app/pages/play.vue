<script setup lang="ts">
import { ref, computed } from 'vue'

definePageMeta({
  layout: false,
})

const isSdkOpen = ref(false)
const isDev = import.meta.dev

const route = useRoute()

// Query bootstrap with the active launch token or fallback parameters
const { data: bootstrap } = useFetch<{
  data: {
    session: { authMode: string, source: string, trusted: boolean }
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

const normalizeSrvAddr = (addr: string): string => {
  if (!addr) return ''

  let host = addr
  try {
    host = decodeURIComponent(addr)
  }
  catch {
    // Fallback
  }

  // Strip protocols (e.g. wss://, https://)
  host = host.replace(/^(wss?|https?):\/\//i, '')

  // Strip trailing slashes and paths
  const slashIdx = host.indexOf('/')
  if (slashIdx !== -1) {
    host = host.substring(0, slashIdx)
  }

  // Strip port if appended (e.g. host:port)
  const colonIdx = host.indexOf(':')
  if (colonIdx !== -1) {
    host = host.substring(0, colonIdx)
  }

  return host.trim()
}

const gameUrl = computed(() => {
  if (!bootstrap.value?.data) {
    return '/muh5-client/index.html?user=guest&userId=guest&srvid=1&srvaddr=muh5-ws.ccgame.org&srvport=443'
  }

  const player = bootstrap.value.data.player
  const server = bootstrap.value.data.server

  const username = player?.username || player?.id || 'guest'
  const userId = player?.id || 'guest'

  const srvid = server?.id || 1
  const srvaddr = normalizeSrvAddr(server?.srvaddr || 'muh5-ws.ccgame.org')
  const srvport = server?.srvport || '443'

  return `/muh5-client/index.html?user=${encodeURIComponent(username)}&userId=${encodeURIComponent(userId)}&srvid=${encodeURIComponent(srvid)}&srvaddr=${encodeURIComponent(srvaddr)}&srvport=${encodeURIComponent(srvport)}`
})
</script>

<template>
  <div class="relative w-full h-screen overflow-hidden bg-black text-white">
    <!-- Game Frame (z-0) -->
    <GameFrame :src="gameUrl" />

    <!-- Top Bar (Minimal Back button) (z-10) -->
    <div
      v-if="isDev"
      class="absolute top-2 left-2 z-10 pointer-events-auto"
    >
      <UButton
        to="/"
        variant="ghost"
        color="neutral"
        icon="i-heroicons-arrow-left"
        size="xs"
        class="opacity-30 hover:opacity-100 transition-opacity bg-black/30 backdrop-blur-sm"
      >
        Dev: Home
      </UButton>
    </div>

    <!-- SDK Elements -->
    <div class="pointer-events-none absolute inset-0 z-50 overflow-hidden">
      <SdkButton v-model:is-open="isSdkOpen" />
      <SdkPanel v-model:is-open="isSdkOpen" />
    </div>
  </div>
</template>
