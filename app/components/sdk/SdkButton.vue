<script setup lang="ts">
const isOpen = defineModel<boolean>('isOpen', { default: false })

function toggleSdk() {
  isOpen.value = !isOpen.value
}
</script>

<template>
  <div class="pointer-events-auto fixed bottom-4 right-4 z-[110]">
    <UChip
      color="error"
      :show="!isOpen"
      inset
      size="sm"
    >
      <UButton
        aria-label="Mở CCGame SDK"
        color="neutral"
        variant="solid"
        size="lg"
        class="size-12 rounded-full p-0 shadow-lg ring-1 ring-muted"
        :ui="{ base: 'justify-center font-black text-lg italic' }"
        label="CC"
        @click="toggleSdk"
      />
    </UChip>
  </div>
</template>
