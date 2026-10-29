<template>
  <AccountShell title="My documents">
    <p class="text-gray-600 mb-6 -mt-2">Keep your CV, certificates and licences up to date. Only you and the secretariat can see them.</p>

    <!-- Upload -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
      <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Add a document</h2>
      <form novalidate class="grid gap-x-4 sm:grid-cols-2" @submit.prevent="upload">
        <AccountField v-model="title" label="Title" placeholder="e.g. CV, October 2026" :error="errors.title" />
        <AccountSelect v-model="category" label="Type" :options="categoryOptions" :error="errors.category" />
        <label class="block mb-4 sm:col-span-2">
          <span class="block text-sm font-medium text-gray-700 mb-1">File</span>
          <input
            ref="fileInput"
            type="file"
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
            class="block w-full text-sm text-gray-700 file:mr-4 file:px-4 file:py-2 file:border-0 file:rounded-lg file:bg-primary file:text-white file:cursor-pointer"
            @change="pick"
          >
          <span v-if="errors.file" class="block text-sm text-red-600 mt-1">{{ errors.file }}</span>
          <span v-else class="block text-xs text-gray-500 mt-1">{{ data?.hint }}</span>
        </label>
        <div class="sm:col-span-2 flex flex-wrap items-center gap-4">
          <button type="submit" :disabled="busy || atLimit" class="bg-primary text-white font-semibold px-6 py-2.5 rounded-lg hover:opacity-90 transition disabled:opacity-60">
            {{ busy ? 'Uploading…' : 'Upload' }}
          </button>
          <p v-if="message" class="text-sm text-green-700" role="status">{{ message }}</p>
          <p v-if="atLimit" class="text-sm text-amber-700">You have {{ data?.max }} documents, the most you can keep. Remove one first.</p>
        </div>
      </form>
    </section>

    <!-- List -->
    <section class="bg-white rounded-xl border border-gray-200">
      <p v-if="!documents.length" class="p-6 text-sm text-gray-600">You have no documents yet.</p>
      <ul v-else class="divide-y divide-gray-100">
        <li v-for="doc in documents" :key="doc.id" class="flex flex-col sm:flex-row sm:items-center gap-3 px-6 py-4">
          <div class="flex-1 min-w-0">
            <a :href="downloadUrl(doc.id)" class="font-medium text-gray-900 hover:text-primary break-words">{{ doc.title }}</a>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ doc.category_label }} · {{ doc.name }} · {{ fileSize(doc.size) }} · {{ formatDate(doc.uploaded_at) }}
              <span v-if="doc.source !== 'member'"> · {{ doc.source_label }}</span>
            </p>
          </div>
          <div class="flex gap-2 shrink-0">
            <a :href="downloadUrl(doc.id)" class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:border-primary">Download</a>
            <button
              v-if="doc.can_delete"
              type="button"
              class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 text-red-700 hover:border-red-400"
              @click="remove(doc)"
            >
              {{ removing === doc.id ? 'Click again to remove' : 'Remove' }}
            </button>
          </div>
        </li>
      </ul>
    </section>
  </AccountShell>
</template>

<script setup lang="ts">
import type { MemberDocument } from '~/composables/useApi'

definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: 'My documents' })

const { member, withXsrf } = useMember()
// The site has switched member documents off
if (!member.value?.features?.documents) {
  await navigateTo('/account', { replace: true })
}
const { fetchMemberDocuments, uploadMemberDocument, deleteMemberDocument } = useApi()
const { data, refresh } = await useAsyncData('member-documents', () => fetchMemberDocuments().catch(() => null))

const documents = computed<MemberDocument[]>(() => data.value?.documents || [])
const categoryOptions = computed(() => Object.entries(data.value?.categories || {}).map(([value, label]) => ({ value, label })))
const atLimit = computed(() => !!data.value && documents.value.length >= data.value.max)
const downloadUrl = (id: number) => `/api/v1/membership/account/documents/${id}/download`

const title = ref('')
const category = ref<string | null>(null)
const file = ref<File | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const busy = ref(false)
const message = ref('')
const errors = reactive<Record<string, string>>({})

const pick = (event: Event) => {
  file.value = (event.target as HTMLInputElement).files?.[0] || null
  delete errors.file
  if (file.value && file.value.size > 5 * 1024 * 1024) errors.file = 'This file is larger than 5 MB.'
  // Start the title from the file name
  if (file.value && !title.value) title.value = file.value.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ')
}

const upload = async () => {
  for (const key of Object.keys(errors)) delete errors[key]
  message.value = ''
  if (!file.value) errors.file = 'Choose a file.'
  if (!title.value.trim()) errors.title = 'Give the document a title.'
  if (!category.value) errors.category = 'Choose a type.'
  if (Object.keys(errors).length) return

  busy.value = true
  try {
    message.value = (await withXsrf(() => uploadMemberDocument(file.value!, title.value.trim(), category.value!))).message
    title.value = ''
    category.value = null
    file.value = null
    if (fileInput.value) fileInput.value.value = ''
    await refresh()
  } catch (e: any) {
    for (const [key, messages] of Object.entries((e?.data?.errors || {}) as Record<string, string[]>)) errors[key] = messages[0]!
    if (!Object.keys(errors).length) errors.file = apiErrorMessage(e)
  } finally {
    busy.value = false
  }
}

// Two clicks to remove, instead of a browser confirm dialog
const removing = ref<number | null>(null)
const remove = async (doc: MemberDocument) => {
  if (removing.value !== doc.id) {
    removing.value = doc.id
    return
  }
  removing.value = null
  try {
    message.value = (await withXsrf(() => deleteMemberDocument(doc.id))).message
    await refresh()
  } catch (e) {
    message.value = apiErrorMessage(e)
  }
}
</script>
