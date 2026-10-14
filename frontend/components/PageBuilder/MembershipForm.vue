<template>
  <section class="py-12 md:py-16" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4 max-w-5xl">
      <PageBuilderSectionIntro v-if="data.heading || data.text" :heading="data.heading" :text="data.text" class="mb-10" />

      <p v-if="pending" class="text-center py-12" :style="{ color: 'var(--color-text-muted)' }">Loading the form…</p>

      <p v-else-if="!form" class="text-center py-12" :style="{ color: 'var(--color-text-muted)' }">
        Membership registration is not open at the moment.
      </p>

      <!-- Done: shown when no online payment is needed -->
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
        <div v-for="(section, s) in form.sections" :key="s" class="mb-10">
          <header v-if="section.title || section.description" class="pb-5 mb-7 border-b" :style="{ borderColor: 'var(--color-surface-strong, #e5e7eb)' }">
            <h2 v-if="section.title" class="text-2xl md:text-3xl mb-2" :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-text-muted)' }">
              {{ section.title }}
            </h2>
            <p v-if="section.description" class="text-sm" :style="{ color: 'var(--color-text-muted)' }">{{ section.description }}</p>
          </header>

          <div class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-6">
            <div
              v-for="field in visibleFields(section)"
              :key="field.key"
              :class="[widthClass(field.width), field.new_row ? 'md:col-start-1' : '']"
            >
              <label v-if="field.type !== 'checkbox'" :for="inputId(field)" class="block text-sm mb-2" :style="{ color: 'var(--color-text-muted)' }">
                {{ field.label }}<span v-if="field.required" class="text-red-600">*</span>
              </label>

              <!-- Membership type: sets the amount -->
              <select
                v-if="field.type === 'membership_type'"
                :id="inputId(field)"
                v-model="answers[field.key]"
                :class="inputClass(field)"
                :style="inputStyle"
              >
                <option value="" disabled>Select…</option>
                <option v-for="type in form.types" :key="type.id" :value="String(type.id)">{{ type.name }}</option>
              </select>

              <select
                v-else-if="field.type === 'select' || field.type === 'country'"
                :id="inputId(field)"
                v-model="answers[field.key]"
                :class="inputClass(field)"
                :style="inputStyle"
              >
                <option value="" disabled>{{ field.placeholder || 'Select…' }}</option>
                <option v-for="option in field.type === 'country' ? form.countries : field.options" :key="option" :value="option">{{ option }}</option>
              </select>

              <textarea
                v-else-if="field.type === 'textarea'"
                :id="inputId(field)"
                v-model="answers[field.key]"
                rows="4"
                :placeholder="field.placeholder"
                :class="inputClass(field)"
                :style="inputStyle"
              />

              <div v-else-if="field.type === 'radio'" class="flex flex-wrap gap-x-6 gap-y-2 pt-1">
                <label v-for="option in field.options" :key="option" class="inline-flex items-center gap-2" :style="{ color: 'var(--color-text)' }">
                  <input v-model="answers[field.key]" type="radio" :name="field.key" :value="option" class="accent-current text-primary" />
                  {{ option }}
                </label>
              </div>

              <div v-else-if="field.type === 'checkboxes'" class="flex flex-wrap gap-x-6 gap-y-2 pt-1">
                <label v-for="option in field.options" :key="option" class="inline-flex items-center gap-2" :style="{ color: 'var(--color-text)' }">
                  <input v-model="answers[field.key]" type="checkbox" :value="option" />
                  {{ option }}
                </label>
              </div>

              <label v-else-if="field.type === 'checkbox'" class="inline-flex items-start gap-3" :style="{ color: 'var(--color-text)' }">
                <input :id="inputId(field)" v-model="answers[field.key]" type="checkbox" class="mt-1" />
                <span>{{ field.label }}<span v-if="field.required" class="text-red-600">*</span></span>
              </label>

              <input
                v-else
                :id="inputId(field)"
                v-model="answers[field.key]"
                :type="htmlType(field.type)"
                :placeholder="field.placeholder"
                :autocomplete="autocomplete(field)"
                :class="inputClass(field)"
                :style="inputStyle"
              />

              <p v-if="field.help" class="mt-1 text-xs" :style="{ color: 'var(--color-text-muted)' }">{{ field.help }}</p>
              <p v-if="errors[field.key]" class="mt-1 text-sm text-red-600">{{ errors[field.key] }}</p>
            </div>
          </div>
        </div>

        <!-- Amount for the chosen membership type -->
        <div
          v-if="form.show_amount && selectedType"
          class="flex items-center justify-between gap-4 px-6 md:px-9 py-6 mb-10"
          :style="{ backgroundColor: 'var(--color-surface-muted, #f3f4f6)', borderRadius: 'var(--radius-button, 8px)' }"
        >
          <span class="text-2xl md:text-3xl font-bold" :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-text)' }">Amount:</span>
          <span class="text-2xl md:text-3xl" :style="{ color: 'var(--color-text)' }">
            <template v-if="selectedType.price > 0">{{ formatMoney(selectedType.price) }} {{ selectedType.period_suffix }}</template>
            <template v-else>Free</template>
          </span>
        </div>

        <p v-if="formError" class="mb-6 text-center text-red-600">{{ formError }}</p>

        <div class="flex justify-center gap-3">
          <button
            type="button"
            class="px-5 py-2.5 border transition hover:opacity-80"
            :style="{ borderColor: 'var(--color-surface-strong, #d1d5db)', color: 'var(--color-text-muted)', borderRadius: 'var(--radius-button, 8px)' }"
            @click="reset"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="inline-flex items-center gap-2 px-5 py-2.5 font-medium text-white bg-primary hover:opacity-90 transition disabled:opacity-60"
            :style="{ borderRadius: 'var(--radius-button, 8px)' }"
          >
            {{ submitting ? 'Please wait…' : form.submit_text }}
            <ThemeIcon name="arrow-right" class="w-4 h-4" />
          </button>
        </div>
        <p v-if="selectedType && selectedType.price > 0 && form.online_payment" class="mt-4 text-center text-xs" :style="{ color: 'var(--color-text-muted)' }">
          You will be taken to Paystack to pay securely by card or mobile money.
        </p>
      </form>
    </div>
  </section>
</template>

<script setup lang="ts">
// Public registration form for the Membership plugin. Fields are configured
// in the admin (Membership → Registration form), not in the page editor.
interface Field {
  key: string
  type: string
  label: string
  required: boolean
  width: string
  new_row: boolean
  placeholder?: string
  help?: string
  options: string[]
  show_for_types: number[]
}

interface MembershipFormData {
  sections: { title?: string; description?: string; fields: Field[] }[]
  submit_text: string
  show_amount: boolean
  currency: string
  online_payment: boolean
  types: { id: number; name: string; price: number; period_suffix: string }[]
  countries: string[]
}

const props = defineProps<{
  data: { heading?: string; text?: string }
  blockId: string
}>()

const { fetchMembershipForm, submitMembership } = useApi()

const { data: formData, pending } = await useAsyncData(`membership-form-${props.blockId}`, async () => {
  try {
    return (await fetchMembershipForm()) as MembershipFormData
  } catch {
    // Plugin switched off or unreachable
    return null
  }
})

const form = computed(() => formData.value)
const answers = reactive<Record<string, any>>({})
const errors = reactive<Record<string, string>>({})
const formError = ref('')
const submitting = ref(false)
const done = ref<{ message: string; reference: string } | null>(null)

const allFields = computed<Field[]>(() => (form.value?.sections || []).flatMap((s) => s.fields))
const typeField = computed(() => allFields.value.find((f) => f.type === 'membership_type'))
const selectedTypeId = computed(() => (typeField.value ? Number(answers[typeField.value.key]) || null : null))
const selectedType = computed(() => form.value?.types.find((t) => t.id === selectedTypeId.value) || null)

const blank = (field: Field) => (field.type === 'checkboxes' ? [] : field.type === 'checkbox' ? false : '')

const reset = () => {
  for (const field of allFields.value) answers[field.key] = blank(field)
  for (const key of Object.keys(errors)) delete errors[key]
  formError.value = ''
}

watch(form, reset, { immediate: true })

// Fields limited to certain membership types appear once one of those is chosen
const applies = (field: Field) => !field.show_for_types?.length || (selectedTypeId.value !== null && field.show_for_types.includes(selectedTypeId.value))
const visibleFields = (section: { fields: Field[] }) => section.fields.filter(applies)

const WIDTHS: Record<string, string> = {
  full: 'md:col-span-12',
  half: 'md:col-span-6',
  third: 'md:col-span-4',
  two_thirds: 'md:col-span-8',
  quarter: 'md:col-span-6 lg:col-span-3',
}
const widthClass = (width: string) => WIDTHS[width] || WIDTHS.full

const htmlType = (type: string) => ({ email: 'email', tel: 'tel', number: 'number', date: 'date' } as Record<string, string>)[type] || 'text'
const autocomplete = (field: Field) => ({ email: 'email', tel: 'tel' } as Record<string, string>)[field.type] || 'on'
const inputId = (field: Field) => `${props.blockId}-${field.key}`

const inputStyle = { borderRadius: 'var(--radius-button, 6px)', color: 'var(--color-text)', backgroundColor: 'var(--color-surface, #fff)' }
const inputClass = (field: Field) => [
  'w-full px-4 py-3 border focus:outline-none focus:ring-2 focus:ring-primary/40 transition',
  errors[field.key] ? 'border-red-500' : 'border-gray-400',
]

const formatMoney = (amount: number) =>
  new Intl.NumberFormat('en', { style: 'currency', currency: form.value?.currency || 'GHS', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(amount)

const submit = async () => {
  for (const key of Object.keys(errors)) delete errors[key]
  formError.value = ''
  submitting.value = true

  // Only send what applies to the chosen membership type
  const payload: Record<string, any> = {}
  for (const field of allFields.value.filter(applies)) payload[field.key] = answers[field.key]

  try {
    const result: any = await submitMembership(payload)
    if (result.payment_url) {
      window.location.href = result.payment_url
      return
    }
    done.value = { message: result.message, reference: result.reference }
  } catch (error: any) {
    const fieldErrors = error?.data?.errors
    if (error?.status === 422 && fieldErrors) {
      for (const [key, messages] of Object.entries(fieldErrors)) {
        errors[key.replace(/^answers\./, '')] = (messages as string[])[0]
      }
      formError.value = 'Please check the highlighted fields.'
      // Bring the first problem into view
      const first = allFields.value.find((f) => errors[f.key])
      if (first) document.getElementById(inputId(first))?.scrollIntoView({ behavior: 'smooth', block: 'center' })
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
