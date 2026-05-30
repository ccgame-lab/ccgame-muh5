<script setup lang="ts">
definePageMeta({
  layout: 'admin',
  middleware: 'admin-auth',
})
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar title="Dashboard">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UCard title="Status">
          <p class="text-success font-medium">Online</p>
        </UCard>

        <UCard title="Server">
          <p class="font-medium">MUH5 S1</p>
        </UCard>

        <UCard title="Build">
          <p class="text-muted font-medium">v0.1.0</p>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>
</template>
