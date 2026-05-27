<script setup lang="ts">
import { ref, computed } from 'vue'
import type { UserProfile } from '~~/types/sdk'

definePageMeta({
  layout: false,
})

const isSdkOpen = ref(false)
const isDev = import.meta.dev

const { data: bootstrap } = useFetch<{ data: { user: UserProfile } }>('/api/bootstrap', {
  key: 'bootstrap-data',
  lazy: true,
})

const gameUrl = computed(() => {
  const username = bootstrap.value?.data.user.username || 'gamer_mock'
  const userId = bootstrap.value?.data.user.id || '10001'
  return `/muh5-client/index.html?user=${encodeURIComponent(username)}&userId=${encodeURIComponent(userId)}&srvid=1&srvaddr=muh5-ws.ccgame.org/s1/&srvport=443`
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
