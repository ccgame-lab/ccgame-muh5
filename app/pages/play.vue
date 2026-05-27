<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({
  layout: false,
})

const isSdkOpen = ref(false)
</script>

<template>
  <div class="relative w-full h-screen overflow-hidden bg-black text-white">
    <!-- Game Frame (z-0) -->
    <GameFrame src="about:blank" />

    <!-- Top Bar (Minimal Back button) (z-10) -->
    <div class="absolute top-2 left-2 z-10 pointer-events-auto">
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
