<script setup lang="ts">
import type { ComponentPublicInstance } from 'vue'

const isOpen = defineModel<boolean>('isOpen', { default: false })

const sdkPos = ref({ x: 0, y: 0 })
const isSdkDragging = ref(false)

function initInteract(el: HTMLElement | ComponentPublicInstance | Element | null) {
  if (!import.meta.client) return
  import('interactjs').then((m) => {
    const interact = m.default || m
    const node = (el && '$el' in el) ? (el as ComponentPublicInstance).$el : el
    if (node) {
      interact(node).unset()
      interact(node).draggable({
        inertia: true,
        modifiers: [
          interact.modifiers.restrictRect({
            restriction: 'body',
            endOnly: false,
          }),
        ],
        listeners: {
          start() {
            isSdkDragging.value = false
          },
          move(event: { dx: number, dy: number }) {
            isSdkDragging.value = true
            sdkPos.value.x += event.dx
            sdkPos.value.y += event.dy
          },
        },
      })
    }
  })
}

function onSdkClick(e: Event) {
  if (isSdkDragging.value) {
    e.preventDefault()
    e.stopPropagation()
    setTimeout(() => {
      isSdkDragging.value = false
    }, 50)
    return
  }
  isOpen.value = !isOpen.value
}
</script>

<template>
  <div
    :ref="initInteract"
    class="pointer-events-auto fixed bottom-4 right-4 z-[110] touch-none select-none"
    :style="{ transform: `translate(${sdkPos.x}px, ${sdkPos.y}px)` }"
  >
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
        class="size-12 rounded-full p-0 shadow-sm ring-1 ring-muted"
        :ui="{ base: 'justify-center font-black text-lg italic' }"
        label="CC"
        @click="onSdkClick"
      />
    </UChip>
  </div>
</template>
