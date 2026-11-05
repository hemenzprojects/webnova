<template>
  <AccountShell title="Our seats">
    <p class="text-gray-600 mb-6 -mt-2">
      Your membership includes {{ seats?.included ?? 0 }} {{ seats?.type ? `${seats.type} ` : '' }}seat{{ seats?.included === 1 ? '' : 's' }}
      for people in your organisation. Each gets their own member login, covered by your membership fee.
    </p>

    <section class="bg-white rounded-xl border border-gray-200 mb-6">
      <h2 class="px-6 pt-6 pb-4 text-sm font-semibold uppercase tracking-wider text-gray-500">
        Seats in use: {{ seats?.used ?? 0 }} of {{ seats?.included ?? 0 }}
      </h2>
      <p v-if="!holders.length" class="px-6 pb-6 text-gray-600">No seats given yet.</p>
      <ul v-else class="divide-y divide-gray-100">
        <li v-for="seat in holders" :key="seat.id" class="px-6 py-4 flex flex-wrap items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="font-medium text-gray-900">{{ seat.name }} <span class="font-mono text-sm text-gray-500">{{ seat.member_number }}</span></p>
            <p class="text-sm text-gray-600 break-all">{{ seat.email }} · {{ seat.signed_up ? 'has signed in' : 'has not set a password yet' }}</p>
          </div>
          <button
            v-if="confirming !== seat.id"
            type="button"
            class="text-sm text-red-600 hover:underline"
            @click="confirming = seat.id"
          >
            Free this seat
          </button>
          <span v-else class="text-sm flex items-center gap-3">
            <span class="text-gray-600">They keep their login but pay for themselves.</span>
            <button type="button" class="text-red-600 font-medium hover:underline" @click="remove(seat.id)">Free it</button>
            <button type="button" class="text-gray-600 hover:underline" @click="confirming = null">Keep</button>
          </span>
        </li>
      </ul>
    </section>

    <section v-if="seats && seats.used < seats.included" class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Give a seat</h2>
      <form novalidate class="grid gap-x-4 sm:grid-cols-2" @submit.prevent="add">
        <AccountField v-model="name" label="Full name" autocomplete="off" :error="errors.name" />
        <AccountField v-model="email" label="Email" type="email" autocomplete="off" :error="errors.email" />
        <p class="sm:col-span-2 text-sm text-gray-500 mb-4">They receive an email with their Member ID and a link to set their password.</p>
        <div class="sm:col-span-2"><AccountSubmitButton label="Give the seat" :busy="busy" /></div>
      </form>
    </section>
    <p v-if="message" class="mt-4 text-sm text-green-700" role="status">{{ message }}</p>
  </AccountShell>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: 'Our seats' })

const { member, withXsrf } = useMember()
if (!member.value?.seats_included) {
  await navigateTo('/account', { replace: true })
}

const { fetchSeats, addSeat, removeSeat } = useApi()
const { data, refresh } = await useAsyncData('member-seats', () => fetchSeats().catch(() => null))
const seats = computed(() => data.value?.seats ?? null)
const holders = computed(() => data.value?.holders ?? [])

const name = ref('')
const email = ref('')
const busy = ref(false)
const message = ref('')
const errors = reactive<Record<string, string>>({})
const confirming = ref<number | null>(null)

const add = async () => {
  for (const key of Object.keys(errors)) delete errors[key]
  message.value = ''
  if (!name.value.trim()) errors.name = 'Enter their name.'
  if (!email.value.trim()) errors.email = 'Enter their email.'
  if (Object.keys(errors).length) return
  busy.value = true
  try {
    message.value = (await withXsrf(() => addSeat(name.value, email.value))).message
    name.value = ''
    email.value = ''
    await refresh()
  } catch (e: any) {
    const fieldErrors = e?.data?.errors
    if (fieldErrors) for (const [key, messages] of Object.entries(fieldErrors)) errors[key] = (messages as string[])[0]!
    else errors.email = apiErrorMessage(e)
  } finally {
    busy.value = false
  }
}

const remove = async (id: number) => {
  message.value = (await withXsrf(() => removeSeat(id))).message
  confirming.value = null
  await refresh()
}
</script>
