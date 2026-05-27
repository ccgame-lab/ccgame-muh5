<script setup lang="ts">
import { ref, computed } from 'vue'

definePageMeta({
  layout: false,
})

const isSdkOpen = ref(false)
const isDev = import.meta.dev

const route = useRoute()

const { data: bootstrap, pending } = useFetch<{
  data: {
    session: {
      authMode: string
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

const playAllowed = computed(() => bootstrap.value?.data?.session?.playAllowed === true)

const launchBlockedMessage = computed(() => {
  const source = bootstrap.value?.data?.session?.source
  if (source === 'invalid_launch') {
    return 'Phiên launch không hợp lệ hoặc đã hết hạn. Vào lại từ CCGame để tiếp tục.'
  }
  if (source === 'unsigned_legacy') {
    return 'Chế độ tương thích unsigned (không tin cậy). Chỉ dùng cho thử nghiệm.'
  }
  return 'Thiếu launch token hợp lệ từ CCGame. Vào game qua ccgame.org/play/muh5.'
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

  host = host.replace(/^(wss?|https?):\/\//i, '')

  const slashIdx = host.indexOf('/')
  if (slashIdx !== -1) {
    host = host.substring(0, slashIdx)
  }

  const colonIdx = host.indexOf(':')
  if (colonIdx !== -1) {
    host = host.substring(0, colonIdx)
  }

  return host.trim()
}

const gameUrl = computed(() => {
  if (!playAllowed.value || !bootstrap.value?.data) {
    return ''
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
    <div
      v-if="pending"
      class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-black"
    >
      <UIcon
        name="i-heroicons-arrow-path"
        class="w-8 h-8 animate-spin text-gray-500"
      />
      <p class="text-sm text-gray-400">
        Đang xác thực phiên launch...
      </p>
    </div>

    <div
      v-else-if="!playAllowed"
      class="absolute inset-0 z-20 flex items-center justify-center p-6 bg-black"
    >
      <div class="w-full max-w-md space-y-4 text-center">
        <UIcon
          name="i-heroicons-lock-closed"
          class="w-12 h-12 text-amber-500 mx-auto"
        />
        <h1 class="text-lg font-bold text-white">
          Launch bị niêm phong
        </h1>
        <p class="text-sm text-gray-400 leading-relaxed">
          {{ launchBlockedMessage }}
        </p>
        <UBadge
          color="warning"
          variant="subtle"
          class="mx-auto"
        >
          {{ bootstrap?.data?.session?.source || 'sealed' }} · trusted: {{ bootstrap?.data?.session?.trusted ? 'yes' : 'no' }}
        </UBadge>
      </div>
    </div>

    <GameFrame
      v-else
      :src="gameUrl"
    />

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

    <div
      v-if="playAllowed"
      class="pointer-events-none absolute inset-0 z-50 overflow-hidden"
    >
      <SdkButton v-model:is-open="isSdkOpen" />
      <SdkPanel v-model:is-open="isSdkOpen" />
    </div>
  </div>
</template>
