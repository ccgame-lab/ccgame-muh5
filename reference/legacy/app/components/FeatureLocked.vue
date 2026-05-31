<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
  icon?: string
}>()
</script>

<template>
  <UCard
    variant="subtle"
    class="border border-muted bg-muted/30"
  >
    <div class="flex flex-col items-center gap-3 px-4 py-6 text-center">
      <UIcon
        :name="icon || 'i-heroicons-lock-closed'"
        class="size-10 text-dimmed"
      />
      <div class="space-y-1">
        <h3 class="text-sm font-semibold text-highlighted">
          {{ title || 'Sắp mở' }}
        </h3>
        <p class="text-xs text-muted leading-relaxed max-w-xs">
          {{ description || 'Đang khóa để bảo toàn dữ liệu. Theo dõi thông báo trong game.' }}
        </p>
      </div>
    </div>
  </UCard>
</template>
