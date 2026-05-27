<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
  icon?: string
}>()
</script>

<template>
  <div class="flex flex-col items-center justify-center p-8 text-center bg-gray-900 rounded-lg border border-gray-800">
    <UIcon
      :name="icon || 'i-heroicons-lock-closed'"
      class="w-12 h-12 text-gray-500 mb-4"
    />
    <h3 class="text-lg font-semibold text-gray-200 mb-2">
      {{ title || 'Sắp mở' }}
    </h3>
    <p class="text-sm text-gray-400 max-w-sm">
      {{ description || 'Tính năng đang khóa để bảo toàn dữ liệu. Theo dõi thông báo trong game.' }}
    </p>
  </div>
</template>
