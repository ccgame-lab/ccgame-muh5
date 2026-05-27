<script setup lang="ts">
import type { UserProfile } from '~~/types/sdk'

const { data: bootstrap, pending } = useFetch<{ data: { user: UserProfile } }>('/api/bootstrap', {
  key: 'bootstrap-data',
  lazy: true,
})
</script>

<template>
  <div
    v-if="pending"
    class="flex justify-center py-8"
  >
    <UIcon
      name="i-heroicons-arrow-path"
      class="w-8 h-8 animate-spin text-gray-500"
    />
  </div>
  <div
    v-else
    class="space-y-4"
  >
    <div class="flex flex-col items-center justify-center p-6 bg-gray-900 rounded-lg border border-gray-800">
      <UAvatar
        :src="bootstrap?.data.user.avatar"
        size="xl"
        class="mb-3 border-2 border-primary-500"
      />
      <h3 class="text-lg font-bold text-white">
        {{ bootstrap?.data.user.username }}
      </h3>
      <p class="text-xs text-gray-400">
        ID: {{ bootstrap?.data.user.id }}
      </p>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="p-3 bg-gray-900 rounded-lg border border-gray-800 flex items-center gap-3">
        <UIcon
          name="i-heroicons-server"
          class="w-5 h-5 text-gray-400"
        />
        <div>
          <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">
            Server
          </p>
          <p class="text-sm font-semibold text-gray-200">
            S1 - Dev Mock
          </p>
        </div>
      </div>
      <div class="p-3 bg-gray-900 rounded-lg border border-gray-800 flex items-center gap-3">
        <UIcon
          name="i-heroicons-star"
          class="w-5 h-5 text-yellow-500"
        />
        <div>
          <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">
            VIP Level
          </p>
          <p class="text-sm font-semibold text-gray-200">
            VIP 0
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
