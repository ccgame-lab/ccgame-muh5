<script setup lang="ts">
import { ref, computed, onBeforeUnmount, watch } from 'vue'

definePageMeta({
  layout: false,
})

useHead({
  link: [
    { rel: 'preconnect', href: 'https://cdn.ccgame.org' },
    { rel: 'dns-prefetch', href: 'https://cdn.ccgame.org' },
    { rel: 'preconnect', href: 'https://muh5-ws.ccgame.org' },
    { rel: 'dns-prefetch', href: 'https://muh5-ws.ccgame.org' },
  ],
})

const isSdkOpen = ref(false)

const route = useRoute()
const frameLoaded = ref(false)
const frameFailed = ref(false)
const frameSlow = ref(false)
const frameKey = ref(0)
let frameSlowTimer: number | undefined

const { data: bootstrap, pending } = useFetch<{
  data: {
    session: {
      authMode: string
      source: string
      trusted: boolean
      playAllowed: boolean
    }
    player: {
      id: string
      username?: string
      spverify?: string
      displayName: string
      suggestedCharacterName?: string
    } | null
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
  if (frameFailed.value) {
    return 'Khung game không tải được. Kiểm tra mạng rồi thử tải lại.'
  }

  const source = bootstrap.value?.data?.session?.source
  if (source === 'invalid_launch') {
    return 'Phiên launch không hợp lệ hoặc đã hết hạn. Vào lại từ CCGame để tiếp tục.'
  }
  if (source === 'unsigned_legacy') {
    return 'Chế độ tương thích unsigned (không tin cậy). Chỉ dùng cho thử nghiệm.'
  }
  return 'Thiếu launch token hợp lệ từ CCGame. Vào game qua ccgame.org/play/muh5.'
})

const frameStatus = computed(() => {
  if (frameSlow.value) {
    return 'Đang tải tài nguyên game. Lần đầu có thể chậm hơn, lần sau sẽ nhanh hơn.'
  }
  if (gameUrl.value && !frameLoaded.value) {
    return 'Đang tải game...'
  }
  return ''
})

const clearFrameSlowTimer = () => {
  if (frameSlowTimer !== undefined) {
    window.clearTimeout(frameSlowTimer)
    frameSlowTimer = undefined
  }
}

const startFrameSlowTimer = () => {
  if (!import.meta.client || !gameUrl.value) return
  clearFrameSlowTimer()
  frameSlowTimer = window.setTimeout(() => {
    if (!frameLoaded.value && !frameFailed.value) {
      frameSlow.value = true
    }
  }, 12000)
}

const handleFrameLoad = () => {
  frameLoaded.value = true
  frameSlow.value = false
  clearFrameSlowTimer()
}

const handleFrameError = () => {
  frameFailed.value = true
  clearFrameSlowTimer()
}

const retryFrame = () => {
  frameLoaded.value = false
  frameFailed.value = false
  frameSlow.value = false
  frameKey.value++
  startFrameSlowTimer()
}

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

/** Mirror ccgame-web deriveGuestSuggestedCharacterName (max 6 UTF-8). */
const deriveGuestCharacterNick = (playerId: string): string => {
  const hex = playerId.replace(/[^a-f0-9]/gi, '').slice(-5)
  return `g${(hex || '00000').padStart(5, '0').slice(0, 5)}`
}

const gameUrl = computed(() => {
  if (!playAllowed.value || !bootstrap.value?.data) {
    return ''
  }

  const player = bootstrap.value.data.player
  const server = bootstrap.value.data.server

  const username = player?.username
  const spverify = player?.spverify
  if (!username || !spverify) {
    return ''
  }
  const userId = player?.id || username

  const srvid = server?.id || 1
  const srvaddr = normalizeSrvAddr(server?.srvaddr || 'muh5-ws.ccgame.org')
  const srvport = server?.srvport || '443'

  const params = new URLSearchParams({
    user: username,
    userId,
    spverify,
    srvid: String(srvid),
    srvaddr,
    srvport,
  })

  // Guest: unique short nick only. Do NOT set roleCount=0 — it skips preload/initGame and
  // crashes doEnterGame when the account already has a character (server role list > 0).
  if (bootstrap.value?.data?.session?.authMode === 'guest') {
    const nick = player?.suggestedCharacterName?.trim()
      || deriveGuestCharacterNick(player?.id || username)
    params.set('nickName', nick)
  }

  return `/muh5-client/index.html?${params.toString()}`
})

watch(gameUrl, () => {
  frameLoaded.value = false
  frameFailed.value = false
  frameSlow.value = false
  startFrameSlowTimer()
}, { immediate: true })

onBeforeUnmount(clearFrameSlowTimer)
</script>

<template>
  <div class="relative w-full h-screen overflow-hidden bg-black text-white">
    <div
      v-if="pending"
      class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 bg-black px-6 text-center"
    >
      <UIcon
        name="i-heroicons-arrow-path"
        class="w-8 h-8 animate-spin text-primary-400"
      />
      <p class="max-w-xs text-sm text-gray-300 leading-relaxed">
        Đang mở phiên chơi...
      </p>
    </div>

    <div
      v-else-if="!playAllowed || frameFailed"
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
        <UButton
          v-if="frameFailed"
          color="primary"
          @click="retryFrame"
        >
          Thử tải lại
        </UButton>
      </div>
    </div>

    <div
      v-else-if="!gameUrl"
      class="absolute inset-0 z-20 flex items-center justify-center p-6 bg-black"
    >
      <div class="w-full max-w-md space-y-4 text-center">
        <UIcon
          name="i-heroicons-exclamation-triangle"
          class="w-12 h-12 text-amber-500 mx-auto"
        />
        <h1 class="text-lg font-bold text-white">
          Thiếu tên nhân vật game
        </h1>
        <p class="text-sm text-gray-400 leading-relaxed">
          Launch token hợp lệ nhưng không có username game. Vào lại từ CCGame để tiếp tục.
        </p>
      </div>
    </div>

    <GameFrame
      v-else
      :key="frameKey"
      :src="gameUrl"
      @load="handleFrameLoad"
      @error="handleFrameError"
    />

    <div
      v-if="playAllowed && gameUrl && frameStatus && !frameFailed"
      class="pointer-events-none absolute left-3 right-3 bottom-4 z-40 flex justify-center"
    >
      <div class="pointer-events-auto flex max-w-sm items-center gap-2 rounded-lg border border-gray-800 bg-black/75 px-3 py-2 text-xs text-gray-300 shadow-lg">
        <UIcon
          name="i-heroicons-arrow-path"
          class="h-4 w-4 shrink-0 animate-spin text-primary-400"
        />
        <span class="min-w-0 flex-1 leading-relaxed">{{ frameStatus }}</span>
        <UButton
          v-if="frameSlow"
          size="xs"
          color="neutral"
          variant="soft"
          @click="retryFrame"
        >
          Tải lại
        </UButton>
      </div>
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
