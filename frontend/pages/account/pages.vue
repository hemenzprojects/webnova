<template>
  <AccountShell title="Members' area">
    <p v-if="!member?.is_active" class="bg-white rounded-xl border border-gray-200 p-6 text-sm text-gray-600">
      The members' area opens again once your membership is active.
    </p>
    <template v-else>
      <section class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Pages</h2>
        <AccountPageList :pages="pages" />
      </section>

      <section v-for="group in fileGroups" :key="group.name" class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">{{ group.name }}</h2>
        <ul class="divide-y divide-gray-100">
          <li v-for="file in group.files" :key="file.id">
            <a :href="`/api/v1/membership/account/resources/${file.id}/download`" class="flex items-start justify-between gap-4 py-3 group">
              <span>
                <span class="block font-medium text-gray-900 group-hover:text-primary">{{ file.title }}</span>
                <span v-if="file.description" class="block text-sm text-gray-600 mt-0.5">{{ file.description }}</span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ file.name }} · {{ fileSize(file.size) }} · {{ formatDate(file.updated_at) }}</span>
              </span>
              <span class="text-sm text-primary shrink-0">Download</span>
            </a>
          </li>
        </ul>
      </section>
    </template>
  </AccountShell>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: "Members' area" })

const { member } = useMember()
const { fetchMemberPages, fetchMemberResources } = useApi()
const { data } = await useAsyncData('member-pages', () => fetchMemberPages().catch(() => ({ pages: [] })))
const { data: resources } = await useAsyncData('member-resources', () => fetchMemberResources().catch(() => ({ resources: [] })))
const pages = computed(() => data.value?.pages || [])

// Files for members, grouped by their category
const fileGroups = computed(() => {
  const groups: Record<string, any[]> = {}
  for (const file of resources.value?.resources || []) (groups[file.category || 'Files'] ??= []).push(file)
  return Object.entries(groups).map(([name, files]) => ({ name, files }))
})
</script>
