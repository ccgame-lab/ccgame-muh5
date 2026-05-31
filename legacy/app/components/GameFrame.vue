<script setup lang="ts">
defineProps<{
  src: string
}>()

const emit = defineEmits<{
  (e: 'load' | 'error'): void
}>()
</script>

<template>
  <div class="fixed inset-0 w-full h-full bg-black z-0">
    <iframe
      v-if="src"
      :src="src"
      class="w-full h-full border-none"
      allow="autoplay; fullscreen"
      referrerpolicy="no-referrer"
      scrolling="no"
      loading="eager"
      @load="emit('load')"
      @error="emit('error')"
    />
  </div>
</template>
