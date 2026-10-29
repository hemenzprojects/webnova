<template>
  <label class="block mb-4">
    <span class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</span>
    <input
      v-bind="$attrs"
      :value="modelValue"
      class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    >
    <span v-if="hint" class="block text-xs text-gray-500 mt-1">{{ hint }}</span>
  </label>
</template>

<script setup lang="ts">
defineOptions({ inheritAttrs: false })
defineProps<{ label: string; modelValue: string; hint?: string }>()
defineEmits<{ 'update:modelValue': [value: string] }>()
</script>
