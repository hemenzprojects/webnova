<template>
  <section class="py-12 md:py-16" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4 max-w-5xl">
      <header v-if="data.heading || data.text" class="mb-8">
        <h2 v-if="data.heading" class="text-3xl md:text-4xl font-bold mb-2" :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-text-muted)' }">
          {{ data.heading }}
        </h2>
        <p v-if="data.text" class="text-sm" :style="{ color: 'var(--color-text-muted)' }">{{ data.text }}</p>
      </header>

      <p v-if="pending" class="text-center py-12" :style="{ color: 'var(--color-text-muted)' }">Loading the form…</p>

      <p v-else-if="!form" class="text-center py-12" :style="{ color: 'var(--color-text-muted)' }">
        Donations are not open at the moment.
      </p>

      <!-- Done: shown when the donation was recorded without online payment -->
      <div
        v-else-if="done"
        class="text-center px-6 py-12"
        :style="{ backgroundColor: 'var(--color-surface-muted, #f3f4f6)', borderRadius: 'var(--radius-card, 12px)' }"
      >
        <ThemeIcon name="check" class="w-14 h-14 mx-auto mb-4 text-primary" />
        <p class="text-lg mb-3" :style="{ color: 'var(--color-text)' }">{{ done.message }}</p>
        <p class="text-sm" :style="{ color: 'var(--color-text-muted)' }">
          Your reference: <strong class="font-mono">{{ done.reference }}</strong>
        </p>
      </div>

      <form v-else novalidate @submit.prevent="submit">
        <!-- Currency and amount -->
        <div class="py-10 border-y-[6px]" :style="{ borderColor: 'var(--color-surface-muted, #eee)' }">
          <div class="flex flex-wrap items-stretch gap-y-2">
            <label :for="id('currency')" class="sr-only">Currency</label>
            <select
              v-if="form.currencies.length > 1"
              :id="id('currency')"
              v-model="values.currency"
              class="px-3 py-3 border border-gray-900 focus:outline-none focus:ring-2 focus:ring-primary/40"
              :style="inputStyle"
            >
              <option v-for="currency in form.currencies" :key="currency.code" :value="currency.code">{{ currency.label }}</option>
            </select>
            <span v-else class="inline-flex items-center px-3 py-3 border border-gray-900" :style="inputStyle">{{ form.currencies[0].label }}</span>

            <label :for="id('amount')" class="sr-only">Donation amount</label>
            <input
              :id="id('amount')"
              v-model="values.amount"
              type="number"
              inputmode="decimal"
              step="any"
              :min="form.minimum_amount"
              placeholder="Donation Amount"
              :class="['w-52 max-w-full px-3 py-3 border focus:outline-none focus:ring-2 focus:ring-primary/40', errors.amount ? 'border-red-500' : 'border-gray-500']"
              :style="inputStyle"
            />
          </div>
          <p v-if="errors.currency || errors.amount" class="mt-2 text-sm text-red-600">{{ errors.amount || errors.currency }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-8 pt-8 mb-10">
          <div v-if="form.titles.length" class="md:col-span-2">
            <div class="md:w-1/2 md:pr-3">
              <label :for="id('title')" :class="labelClass" :style="labelStyle">Title<span class="text-red-600">*</span></label>
              <select :id="id('title')" v-model="values.title" :class="inputClass('title')" :style="inputStyle">
                <option value="" disabled></option>
                <option v-for="title in form.titles" :key="title" :value="title">{{ title }}</option>
              </select>
              <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
            </div>
          </div>

          <div v-for="field in FIELDS" :key="field.key">
            <label :for="id(field.key)" :class="labelClass" :style="labelStyle">{{ field.label }}<span class="text-red-600">*</span></label>
            <input
              :id="id(field.key)"
              v-model="values[field.key]"
              :type="field.type"
              :autocomplete="field.autocomplete"
              :class="inputClass(field.key)"
              :style="inputStyle"
            />
            <p v-if="errors[field.key]" class="mt-1 text-sm text-red-600">{{ errors[field.key] }}</p>
          </div>

          <div class="md:col-span-2">
            <div class="md:w-2/3">
              <label :for="id('comments')" :class="labelClass" :style="labelStyle">Comments (Optional)</label>
              <textarea
                :id="id('comments')"
                v-model="values.comments"
                rows="3"
                placeholder="Type your comments here"
                :class="inputClass('comments')"
                :style="inputStyle"
              />
              <p v-if="errors.comments" class="mt-1 text-sm text-red-600">{{ errors.comments }}</p>
            </div>
          </div>
        </div>

        <p v-if="formError" class="mb-6 text-red-600">{{ formError }}</p>

        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="px-4 py-2 border transition hover:opacity-80"
            :style="{ borderColor: 'var(--color-surface-strong, #d1d5db)', color: 'var(--color-text-muted)', borderRadius: 'var(--radius-button, 0)' }"
            @click="reset"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-4 py-2 font-medium text-white bg-primary hover:opacity-90 transition disabled:opacity-60"
            :style="{ borderRadius: 'var(--radius-button, 0)' }"
          >
            {{ submitting ? 'Please wait…' : 'Proceed to make donation' }}
          </button>
        </div>
        <p v-if="form.online_payment" class="mt-4 text-xs" :style="{ color: 'var(--color-text-muted)' }">
          You will be taken to Paystack to pay securely by card or mobile money.
        </p>
      </form>
    </div>
  </section>
</template>

<script setup lang="ts">
// Public donation form for the Donations plugin. Currencies, titles and the
// smallest amount are set in the admin (Donations → Settings).
interface DonationFormData {
  currencies: { code: string; label: string }[]
  titles: string[]
  minimum_amount: number
  online_payment: boolean
}

const props = defineProps<{
  data: { heading?: string; text?: string }
  blockId: string
}>()

const FIELDS = [
  { key: 'first_name', label: 'First Name', type: 'text', autocomplete: 'given-name' },
  { key: 'last_name', label: 'Last Name', type: 'text', autocomplete: 'family-name' },
  { key: 'email', label: 'Email', type: 'email', autocomplete: 'email' },
  { key: 'phone', label: 'Phone Number', type: 'tel', autocomplete: 'tel' },
]

const { fetchDonationForm, submitDonation } = useApi()

const { data: formData, pending } = await useAsyncData(`donation-form-${props.blockId}`, async () => {
  try {
    return (await fetchDonationForm()) as DonationFormData
  } catch {
    // Plugin switched off or unreachable
    return null
  }
})

const form = computed(() => formData.value)
const values = reactive<Record<string, any>>({})
const errors = reactive<Record<string, string>>({})
const formError = ref('')
const submitting = ref(false)
const done = ref<{ message: string; reference: string } | null>(null)

const reset = () => {
  Object.assign(values, {
    currency: form.value?.currencies[0]?.code || '',
    amount: '',
    title: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    comments: '',
  })
  for (const key of Object.keys(errors)) delete errors[key]
  formError.value = ''
}

watch(form, reset, { immediate: true })

const id = (key: string) => `${props.blockId}-${key}`

const labelClass = 'block text-sm mb-2'
const labelStyle = { color: 'var(--color-text-muted)' }
const inputStyle = { borderRadius: 'var(--radius-button, 0)', color: 'var(--color-text)', backgroundColor: 'var(--color-surface, #fff)' }
const inputClass = (key: string) => [
  'w-full px-4 py-3 border focus:outline-none focus:ring-2 focus:ring-primary/40 transition',
  errors[key] ? 'border-red-500' : 'border-gray-900',
]

const submit = async () => {
  for (const key of Object.keys(errors)) delete errors[key]
  formError.value = ''
  submitting.value = true

  try {
    const result: any = await submitDonation({ ...values })
    if (result.payment_url) {
      window.location.href = result.payment_url
      return
    }
    done.value = { message: result.message, reference: result.reference }
  } catch (error: any) {
    const fieldErrors = error?.data?.errors
    if (error?.status === 422 && fieldErrors) {
      for (const [key, messages] of Object.entries(fieldErrors)) errors[key] = (messages as string[])[0]
      formError.value = 'Please check the highlighted fields.'
      // Bring the first problem into view
      const first = Object.keys(errors)[0]
      if (first) document.getElementById(id(first))?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    } else if (error?.status === 429) {
      formError.value = 'Too many attempts. Please wait a minute and try again.'
    } else {
      formError.value = error?.data?.message || 'Something went wrong. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}
</script>
