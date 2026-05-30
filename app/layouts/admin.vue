<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const items = computed<NavigationMenuItem[]>(() => [
  {
    label: 'Dashboard',
    icon: 'i-lucide-house',
    to: '/admin',
  },
  {
    label: 'Players',
    icon: 'i-lucide-users',
    to: '/admin/players',
  },
  {
    label: 'Hall of Fame',
    icon: 'i-lucide-trophy',
    to: '/admin/hall-of-fame',
  },
  {
    label: 'Accounts',
    icon: 'i-lucide-shield',
    to: '/admin/accounts',
  },
])

async function logout() {
  await $fetch('/api/admin/logout', { method: 'POST' })
  await navigateTo('/admin/login')
}
</script>

<template>
  <UDashboardGroup>
    <UDashboardSidebar collapsible resizable>
      <template #header="{ collapsed }">
        <span v-if="!collapsed" class="font-semibold text-sm">MUH5 Admin</span>
        <UIcon v-else name="i-lucide-gamepad-2" class="size-5 mx-auto" />
      </template>

      <template #default="{ collapsed }">
        <UNavigationMenu
          :collapsed="collapsed"
          :items="items"
          orientation="vertical"
        />
      </template>

      <template #footer="{ collapsed }">
        <UButton
          :icon="collapsed ? 'i-lucide-log-out' : undefined"
          :label="collapsed ? undefined : 'Logout'"
          color="neutral"
          variant="ghost"
          block
          @click="logout"
        />
      </template>
    </UDashboardSidebar>

    <slot />
  </UDashboardGroup>
</template>
