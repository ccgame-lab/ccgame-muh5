<script setup lang="ts">
definePageMeta({
  layout: 'admin',
  middleware: 'admin-auth',
})

const toast = useToast()
const newName = ref('')
const newPassword = ref('')
const adding = ref(false)

const { data, refresh } = await useFetch('/api/admin/accounts')

// Check if current user is owner
const { data: session } = await useFetch('/api/admin/session')
const isOwner = computed(() => session.value?.role === 'owner')

async function addAccount() {
  if (!newName.value || !newPassword.value) return
  adding.value = true
  try {
    await $fetch('/api/admin/accounts', {
      method: 'POST',
      body: { name: newName.value, password: newPassword.value },
    })
    toast.add({ title: 'Account created', color: 'success' })
    newName.value = ''
    newPassword.value = ''
    await refresh()
  }
  catch (e: any) {
    toast.add({ title: e?.data?.statusMessage || 'Failed', color: 'error' })
  }
  finally {
    adding.value = false
  }
}

async function removeAccount(name: string) {
  try {
    await $fetch('/api/admin/accounts', {
      method: 'DELETE',
      body: { name },
    })
    toast.add({ title: `Removed ${name}`, color: 'success' })
    await refresh()
  }
  catch (e: any) {
    toast.add({ title: e?.data?.statusMessage || 'Failed', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar title="Accounts">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <template v-if="!isOwner">
        <UAlert title="Owner only" description="Only Owner can manage accounts." color="warning" icon="i-lucide-shield-alert" />
      </template>

      <template v-else>
        <!-- Add new admin -->
        <UCard title="Add Admin Account" class="mb-6">
          <div class="flex gap-2 items-end">
            <UFormField label="Name">
              <UInput v-model="newName" placeholder="e.g. GM01" />
            </UFormField>
            <UFormField label="Password">
              <UInput v-model="newPassword" type="password" placeholder="Password" />
            </UFormField>
            <UButton :loading="adding" @click="addAccount">
              Add
            </UButton>
          </div>
        </UCard>

        <!-- List -->
        <UCard title="Admin Accounts">
          <div v-if="!data?.accounts?.length" class="text-muted text-sm">
            No admin accounts yet.
          </div>
          <ul v-else class="divide-y divide-default">
            <li v-for="account in data.accounts" :key="account.name" class="flex items-center justify-between py-2">
              <span>{{ account.name }}</span>
              <UButton
                icon="i-lucide-trash-2"
                color="error"
                variant="ghost"
                size="xs"
                @click="removeAccount(account.name)"
              />
            </li>
          </ul>
        </UCard>
      </template>
    </template>
  </UDashboardPanel>
</template>
