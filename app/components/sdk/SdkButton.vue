<script setup lang="ts">
import { ref, onMounted } from 'vue'

const isOpen = defineModel<boolean>('isOpen')

const buttonRef = ref<HTMLElement | null>(null)
const position = ref({ x: 0, y: 0 })
const isMounted = ref(false)
let isDragging = false
let startPos = { x: 0, y: 0 }
let startMousePos = { x: 0, y: 0 }
let hasMoved = false

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

const handlePointerDown = (e: PointerEvent) => {
  if (!buttonRef.value) return
  isDragging = true
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

  position.value = {
    x: startPos.x + dx,
    y: startPos.y + dy,
  }
}

const handlePointerUp = (e: PointerEvent) => {
  if (!isDragging) return
  isDragging = false
  if (buttonRef.value) {
    buttonRef.value.releasePointerCapture(e.pointerId)
  }

  if (typeof window !== 'undefined') {
    // Snap to edges
    if (position.value.x < window.innerWidth / 2) {
      position.value.x = 24
    }
    else {
      position.value.x = window.innerWidth - 72
    }
    // Keep within vertical bounds
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
    class="fixed z-100 w-12 h-12 bg-gray-900 border border-gray-700 rounded-full shadow-xl flex items-center justify-center cursor-pointer touch-none pointer-events-auto transition-transform hover:scale-105 active:scale-95 select-none"
    :class="isMounted ? 'opacity-100' : 'opacity-0 pointer-events-none'"
    :style="{ left: `${position.x}px`, top: `${position.y}px` }"
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
