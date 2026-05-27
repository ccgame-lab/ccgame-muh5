<script setup lang="ts">
defineProps<{
  src: string
}>()
</script>

<template>
  <div class="fixed inset-0 w-full h-full bg-black z-0">
    <!-- A simple mock overlay to simulate game view since we don't have real game files here -->
    <div
      v-if="src === 'about:blank'"
      class="absolute inset-0 flex items-center justify-center pointer-events-none"
    >
      <div class="text-center">
        <UIcon
          name="i-heroicons-play-circle"
          class="w-16 h-16 text-gray-700 mb-4 mx-auto"
        />
        <h2 class="text-2xl font-bold text-gray-500">
          Game View Placeholder
        </h2>
      </div>
    </div>
    <iframe
      :src="src"
      class="w-full h-full border-none"
      allowfullscreen
      allow="autoplay; fullscreen"
      scrolling="no"
    />
  </div>
</template>
