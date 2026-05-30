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

<template>
  <UApp>
    <div class="min-h-screen flex items-center justify-center">
      <UCard class="w-full max-w-xs">
        <template #header>
          <p class="text-center text-sm text-muted">MUH5 Admin</p>
        </template>

        <UForm :state="state" @submit="onSubmit">
          <UFormField label="Password" name="password">
            <UInput
              v-model="state.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Password"
              autofocus
              class="w-full"
            >
              <template #trailing>
                <UButton
                  variant="ghost"
                  color="neutral"
                  size="xs"
                  :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                  @click="showPassword = !showPassword"
                />
              </template>
            </UInput>
          </UFormField>

          <UButton type="submit" block :loading="loading" class="mt-4">
            Sign in
          </UButton>

          <p v-if="error" class="text-error text-xs mt-2 text-center">
            {{ error }}
          </p>
        </UForm>
      </UCard>
    </div>
  </UApp>
</template>
