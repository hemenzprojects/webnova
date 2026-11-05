<template>
  <AccountShell title="My submissions">
    <p class="text-gray-600 mb-6 -mt-2">
      Share a new publication, a milestone or award, or other news with fellow members. The editorial team reviews each submission before it
      appears on the website. Add a link to where your publication is hosted (journal, repository, Google Drive…) and, if you like, a cover image:
      a screenshot of the publication's first page works well.
    </p>

    <p v-if="data && !data.can_submit" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
      Only members in good standing can submit updates.
      <NuxtLink v-if="member?.standing?.renew_year" to="/account/payments" class="font-medium underline">Renew your membership</NuxtLink>
    </p>

    <!-- New submission, or changes to one -->
    <section v-if="data?.can_submit" class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
      <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">{{ editing ? 'Change your submission' : 'Submit an update' }}</h2>
      <form novalidate class="grid gap-x-4 sm:grid-cols-2" @submit.prevent="save">
        <AccountSelect v-model="form.kind" label="Type" :options="kindOptions" :error="errors.kind" />
        <AccountField v-model="form.title" label="Title" maxlength="200" :error="errors.title" />
        <label class="block mb-4 sm:col-span-2">
          <span class="block text-sm font-medium text-gray-700 mb-1">Short summary <span class="text-gray-400 font-normal">(optional, {{ 600 - (form.summary?.length || 0) }} characters left)</span></span>
          <textarea
            v-model="form.summary"
            rows="3"
            maxlength="600"
            :class="['w-full rounded-lg border px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary', errors.summary ? 'border-red-500' : 'border-gray-300']"
          />
          <span v-if="errors.summary" class="block text-sm text-red-600 mt-1">{{ errors.summary }}</span>
        </label>
        <div class="sm:col-span-2">
        <AccountField
          v-model="form.link"
          type="url"
          :label="form.kind === 'publication' ? 'Link to the publication' : 'Link (optional)'"
          placeholder="https://"
          :error="errors.link"
          :hint="form.kind === 'publication' ? 'Where readers can find it: the journal, a repository, Google Drive…' : undefined"
        />
        </div>
        <div class="mb-4 sm:col-span-2">
          <span class="block text-sm font-medium text-gray-700 mb-1">Cover image <span class="text-gray-400 font-normal">(optional, JPG, PNG or WebP, up to 2 MB)</span></span>
          <div class="flex flex-wrap items-center gap-4">
            <img v-if="preview" :src="preview" alt="" class="w-28 h-20 object-cover rounded-lg border border-gray-200">
            <input
              ref="fileInput"
              type="file"
              accept=".jpg,.jpeg,.png,.webp"
              class="text-sm text-gray-700 file:mr-4 file:px-4 file:py-2 file:border-0 file:rounded-lg file:bg-primary file:text-white file:cursor-pointer"
              @change="pick"
            >
            <button v-if="preview" type="button" class="text-sm text-gray-600 hover:underline" @click="clearImage">Remove image</button>
          </div>
          <span v-if="errors.image" class="block text-sm text-red-600 mt-1">{{ errors.image }}</span>
        </div>
        <div class="sm:col-span-2 flex flex-wrap items-center gap-4">
          <AccountSubmitButton :label="editing ? 'Save and send for review' : 'Send for review'" :busy="busy" />
          <button v-if="editing" type="button" class="text-sm text-gray-600 hover:underline" @click="reset">Cancel</button>
        </div>
      </form>
      <p v-if="message" class="mt-4 text-sm text-green-700" role="status">{{ message }}</p>
    </section>

    <!-- What they have sent -->
    <section class="bg-white rounded-xl border border-gray-200">
      <h2 class="px-6 pt-6 pb-4 text-sm font-semibold uppercase tracking-wider text-gray-500">Sent so far</h2>
      <p v-if="!submissions.length" class="px-6 pb-6 text-gray-600">Nothing yet.</p>
      <ul v-else class="divide-y divide-gray-100">
        <li v-for="item in submissions" :key="item.id" class="px-6 py-4 flex gap-4">
          <img v-if="item.image" :src="getImageUrl(item.image)" alt="" class="w-20 h-14 object-cover rounded border border-gray-200 shrink-0">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span :class="['text-xs font-medium rounded-full px-2 py-0.5', badge(item.status)]">{{ item.status_label }}</span>
              <span class="text-xs text-gray-500">{{ item.kind_label }} · sent {{ formatDate(item.submitted_at) }}</span>
            </div>
            <p class="font-medium text-gray-900 mt-1">{{ item.title }}</p>
            <p v-if="item.review_note" class="text-sm text-red-700 mt-1">Editorial team: {{ item.review_note }}</p>
            <a v-if="item.link" :href="item.link" target="_blank" rel="noopener noreferrer" class="text-sm text-primary hover:underline break-all">{{ item.link }}</a>
          </div>
          <div v-if="item.editable" class="shrink-0 text-sm flex flex-col items-end gap-1">
            <button type="button" class="text-primary hover:underline" @click="edit(item)">Change</button>
            <button v-if="confirming !== item.id" type="button" class="text-red-600 hover:underline" @click="confirming = item.id">Withdraw</button>
            <span v-else class="flex gap-2">
              <button type="button" class="text-red-600 font-medium hover:underline" @click="withdraw(item.id)">Yes, withdraw</button>
              <button type="button" class="text-gray-600 hover:underline" @click="confirming = null">Keep</button>
            </span>
          </div>
        </li>
      </ul>
    </section>
  </AccountShell>
</template>

<script setup lang="ts">
import type { MemberSubmission } from '~/composables/useApi'

definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: 'My submissions' })

const { member, withXsrf } = useMember()
if (!member.value?.features?.publications) {
  await navigateTo('/account', { replace: true })
}

const { fetchSubmissions, saveSubmission, deleteSubmission } = useApi()
const { getImageUrl } = useImageUrl()
const { data, refresh } = await useAsyncData('member-submissions', () => fetchSubmissions().catch(() => null))
const submissions = computed<MemberSubmission[]>(() => data.value?.submissions ?? [])
const kindOptions = computed(() => Object.entries(data.value?.kinds ?? {}).map(([value, label]) => ({ value, label })))

const blank = () => ({ kind: 'publication' as string | null, title: '', summary: '', link: '' })
const form = reactive(blank())
const editing = ref<number | null>(null)
const file = ref<File | null>(null)
const preview = ref<string | null>(null)
const removeImage = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const errors = reactive<Record<string, string>>({})
const busy = ref(false)
const message = ref('')
const confirming = ref<number | null>(null)

const pick = (event: Event) => {
  const chosen = (event.target as HTMLInputElement).files?.[0] ?? null
  delete errors.image
  if (chosen && chosen.size > 2 * 1024 * 1024) {
    errors.image = 'The image must not be larger than 2 MB.'
    return
  }
  file.value = chosen
  preview.value = chosen ? URL.createObjectURL(chosen) : null
  removeImage.value = false
}
const clearImage = () => {
  file.value = null
  preview.value = null
  removeImage.value = true
  if (fileInput.value) fileInput.value.value = ''
}

const reset = () => {
  Object.assign(form, blank())
  editing.value = null
  clearImage()
  removeImage.value = false
  for (const key of Object.keys(errors)) delete errors[key]
}

const edit = (item: MemberSubmission) => {
  reset()
  Object.assign(form, { kind: item.kind, title: item.title, summary: item.summary ?? '', link: item.link ?? '' })
  editing.value = item.id
  preview.value = item.image ? getImageUrl(item.image) : null
  message.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const save = async () => {
  for (const key of Object.keys(errors)) delete errors[key]
  message.value = ''
  if (!form.title.trim()) errors.title = 'Give it a title.'
  if (form.kind === 'publication' && !form.link.trim()) errors.link = 'Add the link to where the publication is hosted.'
  if (Object.keys(errors).length) return

  const body = new FormData()
  body.append('kind', form.kind || 'publication')
  body.append('title', form.title)
  body.append('summary', form.summary)
  body.append('link', form.link)
  body.append('remove_image', removeImage.value ? '1' : '0')
  if (file.value) body.append('image', file.value)

  busy.value = true
  try {
    message.value = (await withXsrf(() => saveSubmission(body, editing.value ?? undefined))).message
    reset()
    await refresh()
  } catch (e: any) {
    const fieldErrors = e?.data?.errors
    if (fieldErrors) for (const [key, messages] of Object.entries(fieldErrors)) errors[key] = (messages as string[])[0]!
    else errors.title = apiErrorMessage(e)
  } finally {
    busy.value = false
  }
}

const withdraw = async (id: number) => {
  message.value = (await withXsrf(() => deleteSubmission(id))).message
  confirming.value = null
  if (editing.value === id) reset()
  await refresh()
}

const badge = (status: string) =>
  status === 'approved' ? 'bg-green-100 text-green-800' : status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-900'
</script>
