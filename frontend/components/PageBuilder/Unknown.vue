<template>
  <section v-if="isDevelopment" class="py-8 bg-yellow-50 border-l-4 border-yellow-400">
    <div class="container mx-auto px-4">
      <div class="flex items-start gap-4">
        <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <h3 class="text-lg font-bold text-yellow-800 mb-2">Unknown Block Type</h3>
          <p class="text-yellow-700 mb-2">
            The block type <code class="px-2 py-1 bg-yellow-100 rounded">{{ unknownType }}</code> is not recognized.
          </p>
          <p class="text-sm text-yellow-600">
            This message is only visible in development mode.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: any
  blockId: string
}>()

const config = useRuntimeConfig()
const isDevelopment = config.public.dev || process.env.NODE_ENV === 'development'

// Try to extract block type from data or blockId
const unknownType = computed(() => {
  return props.data?.type || props.blockId || 'unknown'
})
</script>
