<script setup lang="ts">
import type { ComponentPublicInstance } from 'vue'

const isOpen = defineModel<boolean>('isOpen', { default: false })

// Store position in a plain object to avoid reactivity overhead
const sdkPos = { x: 0, y: 0 }
const isSdkDragging = ref(false)

// Reserve the top band for the ccgame-web control pill; SDK stays in the lower region.
const TOP_RESERVED = 96

function initInteract(el: HTMLElement | ComponentPublicInstance | Element | null) {
  if (!import.meta.client) return
  import('interactjs').then((m) => {
    const interact = m.default || m
    const node = (el && '$el' in el) ? (el as ComponentPublicInstance).$el : el
    if (node instanceof HTMLElement) {
      interact(node).unset()
      interact(node).draggable({
        inertia: true,
        modifiers: [
          interact.modifiers.restrictRect({
            restriction: () => ({
              top: TOP_RESERVED,
              left: 0,
              right: window.innerWidth,
              bottom: window.innerHeight,
            }),
            endOnly: false,
          }),
        ],
        listeners: {
          start() {
            isSdkDragging.value = false
          },
          move(event: { dx: number, dy: number }) {
            isSdkDragging.value = true
            sdkPos.x += event.dx
            sdkPos.y += event.dy
            // Direct DOM update for buttery smooth 60fps dragging
            node.style.transform = `translate(${sdkPos.x}px, ${sdkPos.y}px)`
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
    :class="{ 'sdk-float': !isOpen }"
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
        class="size-12 rounded-full p-0 shadow-lg ring-1 ring-primary/40 sdk-press"
        :class="{ 'sdk-glow': !isOpen }"
        :ui="{ base: 'justify-center font-black text-lg italic' }"
        label="CC"
        @click="onSdkClick"
      />
    </UChip>
  </div>
</template>
