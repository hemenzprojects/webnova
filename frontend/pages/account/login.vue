<template>
  <AccountAuthCard title="Member login" subtitle="Sign in to the member portal.">
    <form novalidate @submit.prevent="submit">
      <AccountField v-model="email" label="Email" type="email" autocomplete="email" required />
      <AccountField v-model="password" label="Password" type="password" autocomplete="current-password" required />
      <div class="flex items-center justify-between mb-6 text-sm">
        <label class="inline-flex items-center gap-2 text-gray-700">
          <input v-model="remember" type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary/40">
          Keep me signed in
        </label>
        <NuxtLink to="/account/forgot-password" class="text-primary hover:underline">Forgot your password?</NuxtLink>
      </div>
      <p v-if="error" class="mb-4 text-sm text-red-600" role="alert">{{ error }}</p>
      <AccountSubmitButton label="Log in" :busy="busy" />
    </form>
    <template #footer>
      <p>Not a member yet? <NuxtLink to="/membership" class="text-primary font-medium hover:underline">Join us</NuxtLink></p>
    </template>
  </AccountAuthCard>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member-guest' })
useHead({ title: 'Member login' })

const route = useRoute()
const { login } = useMember()
const email = ref('')
const password = ref('')
const remember = ref(false)
const busy = ref(false)
const error = ref('')

const submit = async () => {
  error.value = ''
  if (!email.value || !password.value) {
    error.value = 'Enter your email and password.'
    return
  }
  busy.value = true
  try {
    await login(email.value, password.value, remember.value)
    await navigateTo(safeRedirect(route.query.redirect))
  } catch (e) {
    error.value = apiErrorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>
