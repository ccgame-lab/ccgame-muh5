<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-950">
    <UCard class="w-full max-w-sm">
      <template #header>
        <div class="text-center">
          <h1 class="text-xl font-bold text-white">MUH5 Admin</h1>
          <p class="text-sm text-gray-400 mt-1">Enter password to continue</p>
        </div>
      </template>

      <UForm :state="state" @submit="onSubmit">
        <UFormField label="Password" name="password">
          <UInput
            v-model="state.password"
            type="password"
            placeholder="Admin password"
            autofocus
            class="w-full"
          />
        </UFormField>

        <UButton
          type="submit"
          block
          :loading="loading"
          class="mt-4"
        >
          Sign in
        </UButton>

        <p v-if="error" class="text-red-400 text-sm mt-3 text-center">
          {{ error }}
        </p>
      </UForm>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
})

const state = reactive({ password: '' })
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
