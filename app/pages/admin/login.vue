<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-950">
    <div class="w-72">
      <h1 class="text-sm font-medium text-gray-400 mb-3 text-center">MUH5 Admin</h1>

      <UForm :state="state" @submit="onSubmit">
        <div class="flex gap-1.5">
          <UInput
            v-model="state.password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Password"
            autofocus
            size="sm"
            class="flex-1"
          >
            <template #trailing>
              <UButton
                variant="ghost"
                color="neutral"
                size="xs"
                :icon="showPassword ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                @click="showPassword = !showPassword"
              />
            </template>
          </UInput>
          <UButton type="submit" size="sm" :loading="loading">
            Go
          </UButton>
        </div>
        <p v-if="error" class="text-red-400 text-xs mt-2 text-center">
          {{ error }}
        </p>
      </UForm>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
})

const state = reactive({ password: '' })
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

async function onSubmit() {
  loading.value = true
  error.value = ''

  try {
    await $fetch('/api/admin/login', {
      method: 'POST',
      body: { password: state.password },
    })
    await navigateTo('/admin')
  }
  catch (e: any) {
    error.value = e?.data?.statusMessage || 'Login failed'
  }
  finally {
    loading.value = false
  }
}
</script>
