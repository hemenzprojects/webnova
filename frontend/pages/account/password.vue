<template>
  <AccountShell title="Change password">
    <section class="bg-white rounded-xl border border-gray-200 p-6 max-w-lg">
      <div v-if="done" class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm p-4" role="status">{{ done }}</div>
      <form novalidate @submit.prevent="submit">
        <AccountField v-model="current" label="Current password" type="password" autocomplete="current-password" required />
        <AccountField v-model="password" label="New password" type="password" autocomplete="new-password" hint="At least 8 characters." required />
        <AccountField v-model="confirmation" label="Repeat the new password" type="password" autocomplete="new-password" required />
        <p v-if="error" class="mb-4 text-sm text-red-600" role="alert">{{ error }}</p>
        <AccountSubmitButton label="Change password" :busy="busy" />
      </form>
    </section>
  </AccountShell>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: 'Change password' })

const { changePassword } = useMember()
const current = ref('')
const password = ref('')
const confirmation = ref('')
const busy = ref(false)
const error = ref('')
const done = ref('')

const submit = async () => {
  error.value = ''
  done.value = ''
  if (password.value.length < 8) {
    error.value = 'The new password must be at least 8 characters.'
    return
  }
  if (password.value !== confirmation.value) {
    error.value = 'The two new passwords are not the same.'
    return
  }
  busy.value = true
  try {
    done.value = await changePassword({ current_password: current.value, password: password.value, password_confirmation: confirmation.value })
    current.value = password.value = confirmation.value = ''
  } catch (e) {
    error.value = apiErrorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>
