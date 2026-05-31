import { ref, watch, onUnmounted } from 'vue'
import type { Ref } from 'vue'

/**
 * Animated number roll-up for SDK "juice" (count from 0 → target).
 * Client-only (SSR renders the final value), honors prefers-reduced-motion,
 * and cancels cleanly on unmount or target change.
 */
export function useCountUp(
  target: Ref<number | null | undefined>,
  options: { duration?: number, startOnMount?: boolean } = {},
): Ref<number> {
  const duration = options.duration ?? 900
  const display = ref<number>(target.value ?? 0)

  let frame = 0
  let startTs = 0
  let from = 0

  const prefersReduced = (): boolean => {
    if (!import.meta.client || typeof window.matchMedia !== 'function') return false
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches
  }

  const stop = () => {
    if (frame) {
      cancelAnimationFrame(frame)
      frame = 0
    }
  }

  const run = (to: number) => {
    if (!import.meta.client || prefersReduced()) {
      display.value = to
      return
    }
    stop()
    from = display.value
    startTs = 0

    const tick = (ts: number) => {
      if (!startTs) startTs = ts
      const progress = Math.min((ts - startTs) / duration, 1)
      // easeOutExpo for a punchy, decelerating roll
      const eased = progress === 1 ? 1 : 1 - 2 ** (-10 * progress)
      display.value = Math.round(from + (to - from) * eased)
      if (progress < 1) {
        frame = requestAnimationFrame(tick)
      }
      else {
        display.value = to
        frame = 0
      }
    }

    frame = requestAnimationFrame(tick)
  }

  watch(
    target,
    (value) => {
      const next = value ?? 0
      run(next)
    },
    { immediate: options.startOnMount ?? true },
  )

  onUnmounted(stop)

  return display
}
