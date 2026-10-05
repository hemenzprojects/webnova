<template>
  <div class="space-y-4">
    <ElementorSchemaField
      v-for="field in schema"
      :key="field.key"
      :field="field"
      :model-value="data?.[field.key]"
      @update:model-value="emit('update', { [field.key]: $event })"
    />
  </div>
</template>

<script setup lang="ts">
import type { BlockField } from '~/utils/blockSchemas'

// Form for a block described by a schema in utils/blockSchemas.ts
defineProps<{
  schema: BlockField[]
  data: Record<string, any>
}>()

const emit = defineEmits<{ update: [patch: Record<string, any>] }>()
</script>
