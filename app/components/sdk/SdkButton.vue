<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'

const isOpen = defineModel<boolean>('isOpen')

const buttonRef = ref<HTMLElement | null>(null)
const position = ref({ x: 0, y: 0 })
const isMounted = ref(false)
const isDraggingUi = ref(false)
let isDragging = false
let startPos = { x: 0, y: 0 }
let startMousePos = { x: 0, y: 0 }
let hasMoved = false
let dragRafId: number | null = null
let pendingDragPos = { x: 0, y: 0 }

const clampDragPosition = (x: number, y: number) => {
  if (typeof window === 'undefined') {
    return { x, y }
  }
  return {
    x: Math.max(24, Math.min(x, window.innerWidth - 72)),
    y: Math.max(24, Math.min(y, window.innerHeight - 72)),
  }
}

const cancelDragRaf = () => {
  if (dragRafId !== null) {
    cancelAnimationFrame(dragRafId)
    dragRafId = null
  }
}

const scheduleDragPosition = (x: number, y: number) => {
  pendingDragPos = clampDragPosition(x, y)
  if (dragRafId !== null) {
    return
  }
  dragRafId = requestAnimationFrame(() => {
    position.value = pendingDragPos
    dragRafId = null
  })
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    // Default position: bottom-right
    position.value = {
      x: window.innerWidth - 72, // 48px width + 24px margin
      y: window.innerHeight - 72,
    }
    isMounted.value = true
  }
})

onBeforeUnmount(cancelDragRaf)

const handlePointerDown = (e: PointerEvent) => {
  if (!buttonRef.value) return
  isDragging = true
  isDraggingUi.value = true
  hasMoved = false
  startMousePos = { x: e.clientX, y: e.clientY }
  startPos = { x: position.value.x, y: position.value.y }
  buttonRef.value.setPointerCapture(e.pointerId)
}

const handlePointerMove = (e: PointerEvent) => {
  if (!isDragging) return
  const dx = e.clientX - startMousePos.x
  const dy = e.clientY - startMousePos.y

  if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
    hasMoved = true
  }

  scheduleDragPosition(startPos.x + dx, startPos.y + dy)
}

const handlePointerUp = (e: PointerEvent) => {
  if (!isDragging) return
  isDragging = false
  isDraggingUi.value = false
  cancelDragRaf()
  if (buttonRef.value) {
    buttonRef.value.releasePointerCapture(e.pointerId)
  }

  if (typeof window !== 'undefined') {
    // Snap to edges (single write on drag end)
    if (position.value.x < window.innerWidth / 2) {
      position.value.x = 24
    }
    else {
      position.value.x = window.innerWidth - 72
    }
    position.value.y = Math.max(24, Math.min(position.value.y, window.innerHeight - 72))
  }

  if (!hasMoved) {
    isOpen.value = !isOpen.value
  }
}
</script>

<template>
  <div
    ref="buttonRef"
    class="fixed left-0 top-0 z-100 w-12 h-12 bg-gray-900 border border-gray-700 rounded-full shadow-lg flex items-center justify-center cursor-pointer touch-none pointer-events-auto select-none will-change-transform"
    :class="[
      isMounted ? 'opacity-100' : 'opacity-0 pointer-events-none',
      isDraggingUi ? '' : 'transition-transform hover:scale-105 active:scale-95',
    ]"
    :style="{ transform: `translate3d(${position.x}px, ${position.y}px, 0)` }"
    @pointerdown="handlePointerDown"
    @pointermove="handlePointerMove"
    @pointerup="handlePointerUp"
    @pointercancel="handlePointerUp"
  >
    <div class="font-black text-xl text-primary-500 italic">
      CC
    </div>
    <span
      v-if="!isOpen"
      class="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full border-2 border-gray-900"
    />
  </div>
</template>
