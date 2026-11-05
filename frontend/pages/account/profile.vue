<template>
  <AccountShell title="My profile">
    <p v-if="!profile" class="text-gray-600">Loading…</p>
    <form v-else novalidate class="space-y-6" @submit.prevent="save">
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Contact details</h2>
        <div class="grid gap-x-4 sm:grid-cols-2">
          <AccountSelect v-model="profile.title" label="Title" :options="options.titles" :error="errors.title" />
          <AccountField v-model="text.phone" label="Phone" type="tel" autocomplete="tel" :error="errors.phone" />
          <AccountSelect v-model="profile.country" label="Country" :options="options.countries" :error="errors.country" />
          <AccountField v-model="text.city" label="City or town" autocomplete="address-level2" :error="errors.city" />
        </div>
        <p class="text-xs text-gray-500">Your name and sign-in email ({{ member?.email }}) are changed by the secretariat.</p>
      </section>

      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Work</h2>
        <div class="grid gap-x-4 sm:grid-cols-2">
          <AccountField v-model="text.institution" label="Institution or organisation" :error="errors.institution" />
          <AccountSelect v-model="profile.institution_type" label="Type of institution" :options="institutionTypes" :error="errors.institution_type" />
          <AccountField v-model="text.position" label="Position" hint="e.g. Senior Research Scientist" :error="errors.position" />
          <AccountField v-model="text.orcid" label="ORCID iD" hint="e.g. 0000-0002-1825-0097" :error="errors.orcid" />
          <AccountField v-model="text.website" label="Website or profile page" type="url" :error="errors.website" />
        </div>
      </section>

      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-1">Crops you work on</h2>
        <p class="text-sm text-gray-600 mb-4">Choose all that apply.</p>
        <AccountChips v-model="profile.crops" :options="options.crops" />
        <p v-if="errors.crops" class="mt-2 text-sm text-red-600">{{ errors.crops }}</p>
      </section>

      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-1">Areas of expertise</h2>
        <p class="text-sm text-gray-600 mb-4">Choose all that apply.</p>
        <AccountChips v-model="profile.expertise" :options="options.expertise" />
        <p v-if="errors.expertise" class="mt-2 text-sm text-red-600">{{ errors.expertise }}</p>
      </section>

      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <label class="block">
          <span class="block text-sm font-semibold uppercase tracking-wider text-gray-500 mb-2">About you</span>
          <textarea
            v-model="text.bio"
            rows="5"
            maxlength="2000"
            placeholder="Your research, current projects and interests."
            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary"
          />
        </label>
        <p v-if="errors.bio" class="mt-1 text-sm text-red-600">{{ errors.bio }}</p>
      </section>

      <div class="flex flex-wrap items-center gap-4">
        <button type="submit" :disabled="busy" class="bg-primary text-white font-semibold px-6 py-3 rounded-lg hover:opacity-90 transition disabled:opacity-60">
          {{ busy ? 'Saving…' : 'Save profile' }}
        </button>
        <p v-if="saved" class="text-sm text-green-700" role="status">{{ saved }}</p>
        <p v-if="formError" class="text-sm text-red-600" role="alert">{{ formError }}</p>
      </div>
    </form>
  </AccountShell>
</template>

<script setup lang="ts">
import type { MemberProfile, MemberProfileOptions } from '~/composables/useApi'

definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: 'My profile' })

const { member, withXsrf } = useMember()
const { fetchMemberProfile, saveMemberProfile } = useApi()
const { data } = await useAsyncData('member-profile', () => fetchMemberProfile().catch(() => null))

const profile = ref<MemberProfile | null>(data.value ? { ...data.value.profile } : null)
const options = computed<MemberProfileOptions>(() => data.value?.options || { titles: [], institution_types: {}, crops: [], expertise: [], countries: [] })
const institutionTypes = computed(() => Object.entries(options.value.institution_types).map(([value, label]) => ({ value, label })))

// Text inputs edit strings; null fields start empty
const textFields = ['phone', 'city', 'institution', 'position', 'orcid', 'website', 'bio'] as const
const text = reactive<Record<string, string>>(Object.fromEntries(textFields.map((f) => [f, (profile.value?.[f] as string) || ''])))

const busy = ref(false)
const saved = ref('')
const formError = ref('')
const errors = reactive<Record<string, string>>({})

const save = async () => {
  if (!profile.value) return
  for (const key of Object.keys(errors)) delete errors[key]
  saved.value = ''
  formError.value = ''
  busy.value = true
  try {
    const body = { ...profile.value, ...Object.fromEntries(textFields.map((f) => [f, text[f].trim() || null])) } as MemberProfile
    const result = await withXsrf(() => saveMemberProfile(body))
    profile.value = { ...result.profile }
    saved.value = result.message
  } catch (e: any) {
    for (const [key, messages] of Object.entries((e?.data?.errors || {}) as Record<string, string[]>)) {
      errors[key.split('.')[0]!] ??= messages[0]!
    }
    formError.value = Object.keys(errors).length ? 'Please check the highlighted fields.' : apiErrorMessage(e)
  } finally {
    busy.value = false
  }
}
</script>
