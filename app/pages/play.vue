<script setup lang="ts">
import { ref, computed, watch } from 'vue'

definePageMeta({
  layout: false,
})

const isSdkOpen = ref(false)

const route = useRoute()
const runtimeConfig = useRuntimeConfig()

const frameFailed = ref(false)
const frameLoaded = ref(false)
const frameKey = ref(0)

const ccgamePortalUrl = computed(() =>
  String(runtimeConfig.public.ccgamePortalUrl || 'https://ccgame.org').replace(/\/+$/, ''),
)
const ccgamePlayUrl = computed(() => `${ccgamePortalUrl.value}/play/muh5`)

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

const sessionSource = computed(() => bootstrap.value?.data?.session?.source || '')
const serverName = computed(() => bootstrap.value?.data?.server?.name || 'S1')

const isExpiredOrInvalid = computed(() =>
  sessionSource.value === 'invalid_launch' || frameFailed.value,
)

const accessTitle = computed(() => {
  if (frameFailed.value) {
    return 'Không tải được game'
  }
  if (sessionSource.value === 'invalid_launch') {
    return 'Phiên đã hết hạn'
  }
  return 'Trạng thái truy cập'
})

const accessMessage = computed(() => {
  if (frameFailed.value) {
    return 'Khung game chưa tải được. Kiểm tra mạng rồi tạo phiên mới.'
  }
  if (sessionSource.value === 'invalid_launch') {
    return 'Phiên chơi đã hết hạn hoặc không còn hợp lệ. Mở lại từ CCGame để tạo phiên mới.'
  }
  return 'Cần mở game từ CCGame để tạo phiên chơi.'
})

const handleFrameError = () => {
  frameFailed.value = true
}

const handleFrameLoaded = () => {
  frameLoaded.value = true
}

const retryFrame = () => {
  frameFailed.value = false
  frameLoaded.value = false
  frameKey.value++
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
  frameFailed.value = false
  frameLoaded.value = false
}, { immediate: true })
</script>

<template>
  <div class="relative w-full h-screen overflow-hidden bg-black text-white">
    <div
      v-if="pending"
      class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 bg-black px-6 text-center"
    >
      <UIcon
        name="i-heroicons-arrow-path"
        class="w-7 h-7 animate-spin text-primary-400"
      />
      <p class="text-sm text-gray-300 leading-relaxed">
        Đang kiểm tra lối vào...
      </p>
    </div>

    <div
      v-else-if="!playAllowed || frameFailed"
      class="absolute inset-0 z-20 flex items-center justify-center p-6 bg-black"
    >
      <div class="w-full max-w-sm space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-center">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-primary-500/10 ring-1 ring-primary-500/30">
          <UIcon
            :name="isExpiredOrInvalid ? 'i-heroicons-clock' : 'i-heroicons-shield-check'"
            class="size-6 text-primary-400"
          />
        </div>
        <div class="space-y-1.5">
          <h1 class="text-base font-semibold text-white">
            {{ accessTitle }}
          </h1>
          <p class="text-sm text-gray-400 leading-relaxed">
            {{ accessMessage }}
          </p>
        </div>
        <div class="flex flex-col gap-2">
          <UButton
            v-if="frameFailed"
            color="primary"
            block
            label="Tạo phiên mới"
            @click="retryFrame"
          />
          <UButton
            :to="ccgamePlayUrl"
            target="_parent"
            external
            :color="frameFailed ? 'neutral' : 'primary'"
            :variant="frameFailed ? 'soft' : 'solid'"
            block
            label="Về CCGame"
          />
        </div>
      </div>
    </div>

    <div
      v-else-if="!gameUrl"
      class="absolute inset-0 z-20 flex items-center justify-center p-6 bg-black"
    >
      <div class="w-full max-w-sm space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-center">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-primary-500/10 ring-1 ring-primary-500/30">
          <UIcon
            name="i-heroicons-arrow-path"
            class="size-6 text-primary-400"
          />
        </div>
        <div class="space-y-1.5">
          <h1 class="text-base font-semibold text-white">
            Đang chuẩn bị nhân vật
          </h1>
          <p class="text-sm text-gray-400 leading-relaxed">
            Phiên chơi hợp lệ nhưng chưa có thông tin nhân vật. Mở lại từ CCGame để tạo phiên mới.
          </p>
        </div>
        <UButton
          :to="ccgamePlayUrl"
          target="_parent"
          external
          color="primary"
          block
          label="Về CCGame"
        />
      </div>
    </div>

    <template v-else>
      <Transition name="sdk-fade">
        <div
          v-if="!frameLoaded"
          class="pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-black px-6 text-center"
        >
          <UIcon
            name="i-heroicons-arrow-path"
            class="w-7 h-7 animate-spin text-primary-400"
          />
          <p class="text-sm text-gray-300 leading-relaxed">
            Đang mở phiên chơi...
          </p>
          <p class="text-[11px] font-mono uppercase tracking-wide text-gray-500">
            {{ serverName }} · Phiên chơi đã sẵn sàng
          </p>
        </div>
      </Transition>

      <GameFrame
        :key="frameKey"
        :src="gameUrl"
        @load="handleFrameLoaded"
        @error="handleFrameError"
      />

      <SdkButton v-model:is-open="isSdkOpen" />
      <SdkPanel v-model:is-open="isSdkOpen" />
    </template>
  </div>
</template>

<style scoped>
.sdk-fade-leave-active {
  transition: opacity 0.3s ease;
}
.sdk-fade-leave-to {
  opacity: 0;
}
</style>
