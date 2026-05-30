<template>
  <div class="min-h-screen bg-gray-950 text-white">
    <header class="border-b border-gray-800 px-6 py-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold">MUH5 Admin</h1>
      <UButton variant="ghost" color="neutral" @click="logout">
        Logout
      </UButton>
    </header>

    <main class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <UCard>
          <template #header>
            <span class="text-sm text-gray-400">Status</span>
          </template>
          <p class="text-2xl font-bold text-emerald-400">Online</p>
        </UCard>

        <UCard>
          <template #header>
            <span class="text-sm text-gray-400">Server</span>
          </template>
          <p class="text-2xl font-bold">MUH5 S1</p>
        </UCard>

        <UCard>
          <template #header>
            <span class="text-sm text-gray-400">Build</span>
          </template>
          <p class="text-2xl font-bold text-gray-300">v0.1.0</p>
        </UCard>
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
