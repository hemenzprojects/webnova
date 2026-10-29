<template>
  <AccountAuthCard title="Forgot your password?" subtitle="Enter your email or Member ID and we will email you a link to choose a new password.">
    <div v-if="sent" class="rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm p-4">
      {{ sent }}
    </div>
    <form v-else novalidate @submit.prevent="submit">
      <AccountField v-model="email" label="Email or Member ID" type="text" autocomplete="username" required />
      <p v-if="error" class="mb-4 text-sm text-red-600" role="alert">{{ error }}</p>
      <AccountSubmitButton label="Send me a link" :busy="busy" />
    </form>
    <template #footer>
      <p><NuxtLink to="/account/login" class="text-primary font-medium hover:underline">Back to login</NuxtLink></p>
    </template>
  </AccountAuthCard>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member-guest' })
useHead({ title: 'Forgot your password?' })

const { forgotPassword } = useMember()
const email = ref('')
const busy = ref(false)
const error = ref('')
const sent = ref('')

const submit = async () => {
  error.value = ''
  if (!email.value) {
    error.value = 'Enter your email or Member ID.'
    return
  }
  busy.value = true
  try {
    sent.value = await forgotPassword(email.value)
  } catch (e) {
    error.value = apiErrorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>
