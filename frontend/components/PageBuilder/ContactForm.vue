<template>
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="max-w-2xl mx-auto">
        <h2 v-if="data.heading" class="text-4xl font-bold text-gray-900 text-center mb-4">
          {{ data.heading }}
        </h2>
        <p v-if="data.description" class="text-gray-600 text-center mb-8">
          {{ data.description }}
        </p>

        <form @submit.prevent="handleSubmit" class="bg-white rounded-2xl shadow-lg p-8">
          <!-- Success Message -->
          <div v-if="submitted" class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ successMessage }}
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ errorMessage }}
          </div>

          <div v-if="!submitted" class="space-y-6">
            <!-- Name Field -->
            <div>
              <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Name *</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent"
                placeholder="Your name"
              />
            </div>

            <!-- Email Field -->
            <div>
              <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent"
                placeholder="your@email.com"
              />
            </div>

            <!-- Phone Field (Optional) -->
            <div v-if="data.showPhone">
              <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent"
                placeholder="+233 XX XXX XXXX"
              />
            </div>

            <!-- Company Field (Optional) -->
            <div v-if="data.showCompany">
              <label for="company" class="block text-sm font-semibold text-gray-700 mb-2">Organization</label>
              <input
                id="company"
                v-model="form.company"
                type="text"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent"
                placeholder="Your organization"
              />
            </div>

            <!-- Message Field -->
            <div>
              <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">Message *</label>
              <textarea
                id="message"
                v-model="form.message"
                required
                rows="5"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent resize-none"
                placeholder="Your message..."
              ></textarea>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full py-4 rounded-lg font-semibold text-white transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105"
              :style="{ backgroundColor: 'var(--color-accent)' }"
            >
              <span v-if="loading">Sending...</span>
              <span v-else>{{ data.submitText || 'Send Message' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    heading?: string
    description?: string
    submitText?: string
    emailTo: string
    showPhone: boolean
    showCompany: boolean
  }
  blockId: string
}>()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  company: '',
  message: '',
  emailTo: props.data.emailTo,
})

const loading = ref(false)
const submitted = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const config = useRuntimeConfig()
    const response = await $fetch(`${config.public.apiBase}/contact-form`, {
      method: 'POST',
      body: form,
    })

    if (response.success) {
      submitted.value = true
      successMessage.value = response.message || 'Thank you for your message!'
      // Reset form
      form.name = ''
      form.email = ''
      form.phone = ''
      form.company = ''
      form.message = ''
    }
  } catch (error: any) {
    errorMessage.value = error.data?.message || 'Failed to send message. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
