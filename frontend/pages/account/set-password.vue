<template>
  <!-- Opened from the welcome email ("set your password") and the reset email -->
  <AccountAuthCard title="Choose your password" :subtitle="email ? `For ${email}` : undefined">
    <div v-if="!token || !email" class="text-sm text-gray-700">
      This link is incomplete. Please open it again from your email, or
      <NuxtLink to="/account/forgot-password" class="text-primary hover:underline">ask for a new one</NuxtLink>.
    </div>
    <form v-else novalidate @submit.prevent="submit">
      <AccountField v-model="password" label="New password" type="password" autocomplete="new-password" hint="At least 8 characters." required />
      <AccountField v-model="confirmation" label="Repeat the password" type="password" autocomplete="new-password" required />
      <p v-if="error" class="mb-4 text-sm text-red-600" role="alert">
        {{ error }}
        <NuxtLink v-if="expired" to="/account/forgot-password" class="underline">Get a new link</NuxtLink>
      </p>
      <AccountSubmitButton label="Save and sign in" :busy="busy" />
    </form>
  </AccountAuthCard>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member-guest' })
useHead({ title: 'Choose your password' })

const route = useRoute()
const token = computed(() => String(route.query.token || ''))
const email = computed(() => String(route.query.email || ''))
const { setPassword } = useMember()
const password = ref('')
const confirmation = ref('')
const busy = ref(false)
const error = ref('')
const expired = ref(false)

const submit = async () => {
  error.value = ''
  expired.value = false
  if (password.value.length < 8) {
    error.value = 'The password must be at least 8 characters.'
    return
  }
  if (password.value !== confirmation.value) {
    error.value = 'The two passwords are not the same.'
    return
  }
  busy.value = true
  try {
    await setPassword({ token: token.value, email: email.value, password: password.value, password_confirmation: confirmation.value })
    await navigateTo('/account')
  } catch (e: any) {
    error.value = apiErrorMessage(e)
    expired.value = /expired/.test(error.value)
  } finally {
    busy.value = false
  }
}
</script>
