<script setup lang="ts">
defineProps<{
  tabs: Array<{ id: string, label: string, icon: string }>
  activeTab: string
}>()

const emit = defineEmits<{
  (e: 'update:activeTab', id: string): void
}>()
</script>

<template>
  <div class="flex overflow-x-auto no-scrollbar border-b border-gray-800 shrink-0 px-2 pt-2">
    <button
      v-for="tab in tabs"
      :key="tab.id"
      class="flex items-center gap-1 px-4 py-2 text-sm font-medium transition-colors border-b-2 whitespace-nowrap"
      :class="activeTab === tab.id ? 'border-primary-500 text-primary-400' : 'border-transparent text-gray-400 hover:text-gray-200'"
      @click="emit('update:activeTab', tab.id)"
    >
      <UIcon
        :name="tab.icon"
        class="w-4 h-4"
      />
      <span>{{ tab.label }}</span>
    </button>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
