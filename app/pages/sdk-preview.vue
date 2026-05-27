<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({
  layout: false,
})

if (!import.meta.dev) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Not Found',
  })
}

const isSdkOpen = ref(true)

useHead({
  title: 'MUH5 SDK Preview',
})
</script>

<template>
  <div class="dark relative min-h-screen bg-default text-default">
    <header
      class="flex flex-wrap items-center gap-2 border-b border-muted bg-elevated px-3 py-2 sm:px-4"
    >
      <h1 class="text-sm font-semibold text-highlighted">
        MUH5 SDK Preview
      </h1>
      <UBadge
        color="warning"
        variant="subtle"
        size="xs"
      >
        dev only
      </UBadge>
      <UBadge
        color="neutral"
        variant="subtle"
        size="xs"
      >
        no game iframe
      </UBadge>
      <div class="flex-1" />
      <UButton
        to="/play"
        color="neutral"
        variant="outline"
        size="xs"
        label="Về /play"
      />
    </header>

    <div
      class="relative min-h-[calc(100dvh-2.75rem)] overflow-hidden"
      aria-label="SDK preview canvas"
    >
      <p class="pointer-events-none absolute inset-x-0 top-3 z-0 px-4 text-center text-xs text-dimmed">
        Chỉnh SDK tại đây — không tải Egret / muh5-client.
      </p>

      <SdkButton v-model:is-open="isSdkOpen" />
      <SdkPanel v-model:is-open="isSdkOpen" />
    </div>
  </div>
</template>
