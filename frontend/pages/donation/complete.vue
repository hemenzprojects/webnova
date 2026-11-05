<template>
  <section class="py-16 md:py-24" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4 max-w-xl">
      <div
        class="text-center px-6 py-12"
        :style="{ backgroundColor: 'var(--color-surface-muted, #f3f4f6)', borderRadius: 'var(--radius-card, 12px)' }"
      >
        <p v-if="pending" :style="{ color: 'var(--color-text-muted)' }">Checking your payment…</p>

        <template v-else-if="!donation">
          <h1 class="text-2xl mb-3" :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-text)' }">Donation not found</h1>
          <p :style="{ color: 'var(--color-text-muted)' }">We could not find this donation. If you have paid, please contact us with your payment receipt.</p>
        </template>

        <template v-else>
          <ThemeIcon :name="isPaid ? 'check' : 'clock'" class="w-14 h-14 mx-auto mb-4 text-primary" />
          <h1 class="text-2xl md:text-3xl mb-3" :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-text)' }">
            {{ heading }}
          </h1>
          <p class="mb-6" :style="{ color: 'var(--color-text-muted)' }">
            <template v-if="isPaid">{{ donation.message }}</template>
            <template v-else-if="donation.payment_status === 'failed'">Your payment did not go through. No money was taken; you can try again.</template>
            <template v-else>We have not received your payment yet. If you have just paid, it can take a moment: check again shortly.</template>
          </p>

          <dl class="text-sm mb-8 space-y-1" :style="{ color: 'var(--color-text-muted)' }">
            <div>Reference: <strong class="font-mono" :style="{ color: 'var(--color-text)' }">{{ donation.reference }}</strong></div>
            <div>Amount: {{ donation.currency }} {{ donation.amount.toFixed(2) }}</div>
          </dl>

          <div v-if="!isPaid" class="flex flex-wrap justify-center gap-3">
            <button
              type="button"
              class="px-5 py-2.5 border"
              :style="{ borderRadius: 'var(--radius-button, 8px)', borderColor: 'var(--color-primary)', color: 'var(--color-primary)' }"
              @click="refresh()"
            >
              Check again
            </button>
            <button
              type="button"
              :disabled="retrying"
              class="px-5 py-2.5 text-white bg-primary disabled:opacity-60"
              :style="{ borderRadius: 'var(--radius-button, 8px)' }"
              @click="retry"
            >
              {{ retrying ? 'Please wait…' : 'Pay now' }}
            </button>
          </div>
          <p v-if="retryError" class="mt-4 text-sm text-red-600">{{ retryError }}</p>
        </template>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
// Paystack returns donors here after paying (Donations plugin)
const route = useRoute()
const reference = computed(() => String(route.query.reference || ''))
const { fetchDonationStatus, retryDonationPayment } = useApi()

const { data: donation, pending, refresh } = await useAsyncData(
  () => `donation-status-${reference.value}`,
  async () => {
    if (!reference.value) return null
    try {
      return (await fetchDonationStatus(reference.value)) as any
    } catch {
      return null
    }
  }
)

const isPaid = computed(() => donation.value?.payment_status === 'paid')
const heading = computed(() =>
  isPaid.value ? `Thank you, ${donation.value.first_name}!` : donation.value?.payment_status === 'failed' ? 'Payment not completed' : 'Waiting for payment'
)

const retrying = ref(false)
const retryError = ref('')
const retry = async () => {
  retrying.value = true
  retryError.value = ''
  try {
    const result: any = await retryDonationPayment(reference.value)
    if (result.payment_url) {
      window.location.href = result.payment_url
    } else {
      retryError.value = 'Online payment is not available right now. Please contact us to give another way.'
    }
  } catch {
    retryError.value = 'Payment could not be started. Please try again in a moment.'
  } finally {
    retrying.value = false
  }
}

useHead({ title: 'Donation' })
</script>
