<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  tabs: Array<{ id: string, label: string, icon: string }>
}>()

const activeTab = defineModel<string>('activeTab', { required: true })

const items = computed(() =>
  props.tabs.map(tab => ({
    label: tab.label,
    icon: tab.icon,
    value: tab.id,
  })),
)
</script>

<template>
  <UTabs
    v-model="activeTab"
    :items="items"
    variant="link"
    :content="false"
    class="w-full shrink-0 border-b border-muted"
  />
</template>
