<template>
  <section class="py-12">
    <div class="container mx-auto px-4">
      <div class="max-w-3xl mx-auto">
        <form @submit.prevent="handleSearch" class="relative">
          <div class="relative" :class="sizeClass">
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="data.placeholder || 'Search...'"
              class="w-full pl-12 pr-4 border-2 border-gray-300 rounded-full focus:ring-2 focus:ring-accent focus:border-accent transition-all"
              :class="inputSizeClass"
            />
            <button
              type="submit"
              class="absolute inset-y-0 right-0 px-6 rounded-r-full font-semibold text-white transition-all hover:opacity-90"
              :style="{ backgroundColor: 'var(--color-accent)' }"
            >
              Search
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
    placeholder?: string
    searchScope: 'pages' | 'news' | 'events' | 'all'
    size: 'sm' | 'md' | 'lg'
  }
  blockId: string
}>()

const router = useRouter()
const searchQuery = ref('')

const sizeClass = computed(() => {
  const sizeMap: Record<string, string> = {
    sm: '',
    md: '',
    lg: '',
  }
  return sizeMap[props.data.size] || ''
})

const inputSizeClass = computed(() => {
  const sizeMap: Record<string, string> = {
    sm: 'py-2 text-sm',
    md: 'py-3 text-base',
    lg: 'py-4 text-lg',
  }
  return sizeMap[props.data.size] || sizeMap.md
})

const handleSearch = () => {
  if (!searchQuery.value.trim()) return

  router.push({
    path: '/search',
    query: {
      q: searchQuery.value,
      scope: props.data.searchScope,
    },
  })
}
</script>
