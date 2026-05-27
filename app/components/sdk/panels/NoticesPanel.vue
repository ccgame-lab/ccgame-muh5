<script setup lang="ts">
// Using hardcoded mock data for notices to save time since it's just static
const notices = [
  { id: 1, title: 'Welcome to MUH5', date: '2026-05-27', isNew: true },
  { id: 2, title: 'Server Maintenance Scheduled', date: '2026-05-26', isNew: false },
  { id: 3, title: 'Patch Notes v1.0.1', date: '2026-05-25', isNew: false },
]
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="notice in notices"
      :key="notice.id"
      class="p-3 bg-gray-900 border border-gray-800 rounded-lg flex items-center justify-between cursor-pointer hover:bg-gray-800 transition-colors"
    >
      <div class="flex flex-col">
        <div class="flex items-center gap-2">
          <h4 class="text-sm font-bold text-white">
            {{ notice.title }}
          </h4>
          <span
            v-if="notice.isNew"
            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-500/20 text-red-500"
          >NEW</span>
        </div>
        <p class="text-[11px] text-gray-500 mt-1">
          {{ notice.date }}
        </p>
      </div>
      <UIcon
        name="i-heroicons-chevron-right"
        class="w-4 h-4 text-gray-600"
      />
    </div>
  </div>
</template>
