<template>
  <div class="min-h-screen bg-gray-950 text-gray-200 text-sm">
    <header class="border-b border-gray-800 px-4 py-2 flex items-center justify-between">
      <span class="font-medium text-gray-400">MUH5 Admin</span>
      <UButton variant="ghost" color="neutral" size="xs" @click="logout">
        Logout
      </UButton>
    </header>

    <main class="p-4">
      <div class="grid grid-cols-3 gap-3">
        <div class="bg-gray-900 rounded px-3 py-2">
          <span class="text-xs text-gray-500">Status</span>
          <p class="text-emerald-400 font-medium">Online</p>
        </div>
        <div class="bg-gray-900 rounded px-3 py-2">
          <span class="text-xs text-gray-500">Server</span>
          <p class="font-medium">MUH5 S1</p>
        </div>
        <div class="bg-gray-900 rounded px-3 py-2">
          <span class="text-xs text-gray-500">Build</span>
          <p class="font-medium text-gray-400">v0.1.0</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
  middleware: 'admin-auth',
})

async function logout() {
  await $fetch('/api/admin/logout', { method: 'POST' })
  await navigateTo('/admin/login')
}
</script>
