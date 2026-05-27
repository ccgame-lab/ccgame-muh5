<script setup lang="ts">
const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  dismiss: []
}>()

const greenJadeLoginUrl = useGreenJadeLoginUrl()
</script>

<template>
  <div
    v-if="props.visible"
    class="pointer-events-auto absolute top-3 right-3 z-40 flex max-w-[min(100%,14rem)] items-center gap-1.5 rounded-full border border-amber-900/50 bg-neutral-950/90 py-1 pl-2.5 pr-1 text-[10px] text-amber-100/90 shadow-md sm:top-4 sm:right-4 sm:text-[11px]"
  >
    <a
      :href="greenJadeLoginUrl"
      target="_parent"
      rel="noopener noreferrer"
      class="min-w-0 truncate font-semibold text-emerald-300 hover:text-emerald-200"
    >
      Đăng nhập
    </a>
    <button
      type="button"
      class="shrink-0 rounded-full p-1 text-neutral-500 hover:bg-neutral-800 hover:text-neutral-300"
      aria-label="Ẩn gợi ý đăng nhập"
      @click="emit('dismiss')"
    >
      <UIcon
        name="i-heroicons-x-mark"
        class="size-3.5"
      />
    </button>
  </div>
</template>
